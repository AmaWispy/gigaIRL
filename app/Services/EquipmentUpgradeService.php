<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CraftingRecipe;
use App\Models\InventoryItem;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EquipmentUpgradeService
{
    public function __construct(
        private EquipmentService $equipmentService,
        private InventoryService $inventoryService,
        private EnergyService $energyService,
    ) {}

    public function upgradeCraftedLevel(Character $character, InventoryItem $inventoryItem): void
    {
        $this->equipmentService->assertEquipmentInstance($inventoryItem);

        if ($inventoryItem->equipment_source !== 'crafted') {
            throw new InvalidArgumentException('Это улучшение только для крафтовой экипировки.');
        }

        if ($inventoryItem->equippedSlot()->exists()) {
            throw new InvalidArgumentException('Снимите предмет перед улучшением.');
        }

        $recipe = CraftingRecipe::where('result_item_id', $inventoryItem->item_id)->first();

        if (! $recipe) {
            throw new InvalidArgumentException('Рецепт для этого предмета не найден.');
        }

        if (! $recipe->upgradable) {
            throw new InvalidArgumentException('Эту вещь нельзя улучшать.');
        }

        if (! $this->energyService->hasEnough($character, 2)) {
            throw new InvalidArgumentException('Недостаточно энергии (нужно 2).');
        }

        $level = $inventoryItem->equipment_level ?? 1;
        $sealItem = Item::where('catalog_key', 'craftsman_seal')->first();
        $sealCost = $this->equipmentService->sealsForSourceLevelUpgrade($level, 'crafted');
        $resourceMultiplier = 1 + ($inventoryItem->upgrade_count * 0.5);

        DB::transaction(function () use ($character, $inventoryItem, $recipe, $sealItem, $sealCost, $level, $resourceMultiplier) {
            foreach ($recipe->ingredients as $ingredient) {
                $item = Item::findOrFail($ingredient['item_id']);
                $qty = (int) ceil($ingredient['quantity'] * $resourceMultiplier);
                $this->inventoryService->removeItem($character, $item, $qty);
            }

            $this->inventoryService->removeItem($character, $sealItem, $sealCost);
            $this->energyService->spend($character, 2, 'equipment_upgrade');

            $newLevel = $level + 1;
            $quality = $inventoryItem->quality ?? 'white';

            if ($newLevel % 7 === 0 && $newLevel > 7) {
                $rolled = $this->equipmentService->rollCraftUpgradeQuality($character, $quality);
                if ($rolled) {
                    $quality = $rolled;
                }
            }

            $inventoryItem->update([
                'equipment_level' => $newLevel,
                'quality' => $quality,
                'upgrade_count' => $inventoryItem->upgrade_count + 1,
            ]);
        });
    }

    public function upgradeDungeonLevel(Character $character, InventoryItem $inventoryItem): void
    {
        $this->equipmentService->assertEquipmentInstance($inventoryItem);

        if ($inventoryItem->equipment_source !== 'dungeon') {
            throw new InvalidArgumentException('Это улучшение только для данжевой экипировки.');
        }

        if ($inventoryItem->equippedSlot()->exists()) {
            throw new InvalidArgumentException('Снимите предмет перед улучшением.');
        }

        $sphereItem = Item::where('catalog_key', 'transformation_sphere')->first();

        if (! $sphereItem) {
            throw new InvalidArgumentException('Сферы становления не найдены в базе.');
        }

        $level = $inventoryItem->equipment_level ?? 1;
        $sphereCost = $this->equipmentService->spheresForDungeonLevelUpgrade($level, $inventoryItem->upgrade_count);

        DB::transaction(function () use ($character, $inventoryItem, $sphereItem, $sphereCost, $level) {
            $this->inventoryService->removeItem($character, $sphereItem, $sphereCost);
            $inventoryItem->update([
                'equipment_level' => $level + 1,
                'upgrade_count' => $inventoryItem->upgrade_count + 1,
            ]);
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUpgradeOptions(Character $character): array
    {
        return $character->inventoryItems()
            ->with(['item', 'equippedSlot'])
            ->whereHas('item', fn ($q) => $q->where('type', 'equipment'))
            ->get()
            ->filter(fn (InventoryItem $inv) => ! $inv->equippedSlot && ($inv->equipment_source ?? 'vendor') !== 'vendor')
            ->map(fn (InventoryItem $inv) => $this->formatUpgradeOption($character, $inv))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatUpgradeOption(Character $character, InventoryItem $inv): ?array
    {
        $item = $inv->item;
        $quality = $inv->quality ?? 'white';
        $level = $inv->equipment_level ?? 1;
        $source = $inv->equipment_source ?? 'vendor';
        $stats = $this->equipmentService->computeStatsForInventoryItem($inv);
        $actions = [];

        if ($source === 'crafted') {
            $recipe = CraftingRecipe::where('result_item_id', $item->id)->first();

            if (! $recipe || ! $recipe->upgradable) {
                return null;
            }

            $resourceMultiplier = 1 + ($inv->upgrade_count * 0.5);
            $ingredients = collect($recipe?->ingredients ?? [])->map(function ($ing) use ($resourceMultiplier) {
                $ingredientItem = Item::find($ing['item_id']);

                return [
                    'name' => $ingredientItem?->name ?? '?',
                    'quantity' => (int) ceil($ing['quantity'] * $resourceMultiplier),
                ];
            })->all();

            $actions[] = [
                'type' => 'crafted_level',
                'label' => '+1 уровень',
                'cost' => [
                    'craftsman_seal' => $this->equipmentService->sealsForSourceLevelUpgrade($level, 'crafted'),
                    'energy' => 2,
                    'ingredients' => $ingredients,
                ],
            ];
        }

        if ($source === 'dungeon') {
            $actions[] = [
                'type' => 'dungeon_level',
                'label' => '+1 уровень',
                'cost' => [
                    'transformation_sphere' => $this->equipmentService->spheresForDungeonLevelUpgrade($level, $inv->upgrade_count),
                ],
            ];
        }

        if ($actions === []) {
            return null;
        }

        return [
            'inventory_item_id' => $inv->id,
            'name' => $item->name,
            'quality' => $quality,
            'quality_label' => $this->equipmentService->qualityLabel($quality),
            'equipment_level' => $level,
            'equipment_source' => $source,
            'stats' => $stats,
            'actions' => $actions,
        ];
    }
}
