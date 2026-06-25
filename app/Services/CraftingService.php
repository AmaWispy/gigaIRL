<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CraftingRecipe;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CraftingService
{
    public function __construct(
        private EnergyService $energyService,
        private InventoryService $inventoryService,
        private EquipmentService $equipmentService,
    ) {}

    /**
     * @return array{basic: list<array>, rare: list<array>}
     */
    public function getRecipesByCategory(Character $character): array
    {
        $recipes = CraftingRecipe::with(['resultItem', 'recipeScrollItem'])
            ->get()
            ->filter(fn (CraftingRecipe $recipe) => $this->matchesProfession($character, $recipe));

        $basic = $recipes
            ->where('category', 'basic')
            ->map(fn (CraftingRecipe $recipe) => $this->formatRecipe($character, $recipe))
            ->values()
            ->all();

        $rare = $recipes
            ->where('category', 'rare')
            ->filter(fn (CraftingRecipe $recipe) => $this->hasRecipeScroll($character, $recipe))
            ->map(fn (CraftingRecipe $recipe) => $this->formatRecipe($character, $recipe))
            ->values()
            ->all();

        return [
            'basic' => $basic,
            'rare' => $rare,
        ];
    }

    public function getAvailableRecipes(Character $character): array
    {
        $grouped = $this->getRecipesByCategory($character);

        return array_merge($grouped['basic'], $grouped['rare']);
    }

    public function craft(Character $character, CraftingRecipe $recipe): void
    {
        if (! $this->matchesProfession($character, $recipe)) {
            throw new InvalidArgumentException('Недостаточная профессия для этого рецепта.');
        }

        if ($recipe->category === 'rare' && ! $this->hasRecipeScroll($character, $recipe)) {
            throw new InvalidArgumentException('Нужен свиток рецепта в инвентаре.');
        }

        if (! $this->canCraft($character, $recipe)) {
            throw new InvalidArgumentException('Недостаточно ресурсов для крафта.');
        }

        DB::transaction(function () use ($character, $recipe) {
            foreach ($recipe->ingredients as $ingredient) {
                $item = Item::findOrFail($ingredient['item_id']);
                $this->inventoryService->removeItem($character, $item, $ingredient['quantity']);
            }

            if ($recipe->recipe_scroll_item_id) {
                $this->inventoryService->removeItem($character, $recipe->recipeScrollItem, 1);
            }

            $this->energyService->spend($character, $recipe->energy_cost, 'crafting');

            $resultItem = $recipe->resultItem;

            if ($resultItem->type === 'equipment') {
                for ($i = 0; $i < $recipe->result_quantity; $i++) {
                    $quality = $recipe->fixed_result_quality
                        ?? $this->equipmentService->rollCraftInitialQuality($character);

                    $this->inventoryService->addEquipment($character, $resultItem, [
                        'quality' => $quality,
                        'source' => 'crafted',
                        'level' => $resultItem->item_level ?? 1,
                    ]);
                }
            } else {
                $this->inventoryService->addItem($character, $resultItem, $recipe->result_quantity);
            }
        });
    }

    public function learnProfession(Character $character, string $profession): Character
    {
        if (! in_array($profession, config('game.professions'))) {
            throw new InvalidArgumentException('Неизвестная профессия.');
        }

        $character->update([
            'profession' => $profession,
            'blacksmith_rank' => 'apprentice',
        ]);

        return $character->fresh();
    }

    public function upgradeBlacksmithRank(Character $character, string $rank): Character
    {
        $ranks = config('game.blacksmith_ranks');

        if (! isset($ranks[$rank])) {
            throw new InvalidArgumentException('Неизвестный ранг кузнеца.');
        }

        $req = $ranks[$rank];

        if ($character->profession !== 'blacksmith') {
            throw new InvalidArgumentException('Сначала станьте кузнецом.');
        }

        if ($character->level < $req['min_level']) {
            throw new InvalidArgumentException("Нужен уровень {$req['min_level']}.");
        }

        if ($character->money < $req['cost']) {
            throw new InvalidArgumentException("Нужно {$req['cost']} золота на обучение и инструменты.");
        }

        $character->money -= $req['cost'];
        $character->blacksmith_rank = $rank;
        $character->save();

        return $character->fresh();
    }

    public function getBlacksmithRanks(Character $character): array
    {
        return collect(config('game.blacksmith_ranks'))
            ->map(fn ($data, $key) => [
                'key' => $key,
                'label' => $data['label'],
                'min_level' => $data['min_level'],
                'cost' => $data['cost'],
                'tier' => $data['tier'],
                'is_current' => ($character->blacksmith_rank ?? 'apprentice') === $key,
                'can_upgrade' => $character->profession === 'blacksmith'
                    && $character->level >= $data['min_level']
                    && $character->money >= $data['cost']
                    && ($character->blacksmith_rank ?? 'apprentice') !== $key,
            ])
            ->values()
            ->all();
    }

    private function matchesProfession(Character $character, CraftingRecipe $recipe): bool
    {
        return $recipe->required_profession === 'none'
            || $character->profession === $recipe->required_profession;
    }

    private function hasRecipeScroll(Character $character, CraftingRecipe $recipe): bool
    {
        if (! $recipe->recipe_scroll_item_id) {
            return true;
        }

        return $character->inventoryItems()
            ->where('item_id', $recipe->recipe_scroll_item_id)
            ->whereNull('quality')
            ->where('quantity', '>', 0)
            ->exists();
    }

    private function formatRecipe(Character $character, CraftingRecipe $recipe): array
    {
        return [
            'id' => $recipe->id,
            'name' => $recipe->name,
            'category' => $recipe->category,
            'energy_cost' => $recipe->energy_cost,
            'required_profession' => $recipe->required_profession,
            'upgradable' => $recipe->upgradable,
            'result' => [
                'name' => $recipe->resultItem->name,
                'quantity' => $recipe->result_quantity,
                'type' => $recipe->resultItem->type,
                'description' => $recipe->resultItem->description,
                'equipment_preview' => $this->equipmentService->previewCraftResult($recipe->resultItem, $recipe),
            ],
            'ingredients' => $this->formatIngredients($character, $recipe),
            'energy' => [
                'required' => $recipe->energy_cost,
                'owned' => $character->energy,
                'has_enough' => $this->energyService->hasEnough($character, $recipe->energy_cost),
            ],
            'can_craft' => $this->canCraft($character, $recipe),
        ];
    }

    private function canCraft(Character $character, CraftingRecipe $recipe): bool
    {
        if (! $this->energyService->hasEnough($character, $recipe->energy_cost)) {
            return false;
        }

        if ($recipe->recipe_scroll_item_id && ! $this->hasRecipeScroll($character, $recipe)) {
            return false;
        }

        foreach ($recipe->ingredients as $ingredient) {
            $inv = $character->inventoryItems()
                ->where('item_id', $ingredient['item_id'])
                ->whereNull('quality')
                ->first();

            if (! $inv || $inv->quantity < $ingredient['quantity']) {
                return false;
            }
        }

        return true;
    }

    private function formatIngredients(Character $character, CraftingRecipe $recipe): array
    {
        $ownedCounts = $character->inventoryItems()
            ->whereNull('quality')
            ->pluck('quantity', 'item_id');

        $ingredients = collect($recipe->ingredients)->map(function ($ing) use ($ownedCounts) {
            $item = Item::find($ing['item_id']);
            $required = $ing['quantity'];
            $owned = (int) ($ownedCounts[$ing['item_id']] ?? 0);
            $missing = max(0, $required - $owned);

            return [
                'item_id' => $ing['item_id'],
                'name' => $item?->name ?? 'Неизвестно',
                'required' => $required,
                'owned' => $owned,
                'missing' => $missing,
                'has_enough' => $owned >= $required,
            ];
        });

        if ($recipe->recipe_scroll_item_id && $recipe->recipeScrollItem) {
            $scrollId = $recipe->recipe_scroll_item_id;
            $owned = (int) ($ownedCounts[$scrollId] ?? 0);

            $ingredients->prepend([
                'item_id' => $scrollId,
                'name' => $recipe->recipeScrollItem->name,
                'required' => 1,
                'owned' => $owned,
                'missing' => max(0, 1 - $owned),
                'has_enough' => $owned >= 1,
                'consumed' => true,
            ]);
        }

        return $ingredients->values()->all();
    }
}
