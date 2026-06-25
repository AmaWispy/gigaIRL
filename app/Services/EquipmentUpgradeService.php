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

        $recipe = CraftingRecipe::where('result_item_id', $inventoryItem->item_id)->first();

        if (! $recipe) {
            throw new InvalidArgumentException('Рецепт для этого предмета не найден.');
        }

        if (! $recipe->upgradable) {
            throw new InvalidArgumentException('Эту вещь нельзя улучшать.');
        }

        $config = config('equipment.crafted_t2_upgrade');
        $targetLevel = (int) $config['target_level'];
        $level = $inventoryItem->equipment_level ?? 1;

        if ($level >= $targetLevel) {
            throw new InvalidArgumentException("Вещь уже улучшена до уровня {$targetLevel}.");
        }

        $energyCost = (int) $config['energy'];

        if (! $this->energyService->hasEnough($character, $energyCost)) {
            throw new InvalidArgumentException("Недостаточно энергии (нужно {$energyCost}).");
        }

        $sealItem = Item::where('catalog_key', 'craftsman_seal')->first();
        $sealCost = (int) $config['craftsman_seal'];

        $resources = [];

        foreach ($config['resources'] as $catalogKey => $quantity) {
            $resourceItem = Item::where('catalog_key', $catalogKey)->first();

            if (! $resourceItem) {
                throw new InvalidArgumentException("Ресурс {$catalogKey} не найден в базе.");
            }

            $resources[] = ['item' => $resourceItem, 'quantity' => (int) $quantity];
        }

        DB::transaction(function () use ($character, $inventoryItem, $sealItem, $sealCost, $resources, $targetLevel, $energyCost) {
            $this->inventoryService->removeItem($character, $sealItem, $sealCost);

            foreach ($resources as $resource) {
                $this->inventoryService->removeItem($character, $resource['item'], $resource['quantity']);
            }

            $this->energyService->spend($character, $energyCost, 'equipment_upgrade');

            $inventoryItem->update([
                'equipment_level' => $targetLevel,
                'upgrade_count' => $inventoryItem->upgrade_count + 1,
            ]);
        });
    }

    public function upgradeCraftedQuality(Character $character, InventoryItem $inventoryItem): void
    {
        $this->equipmentService->assertEquipmentInstance($inventoryItem);

        if ($inventoryItem->equipment_source !== 'crafted') {
            throw new InvalidArgumentException('Это улучшение только для крафтовой экипировки.');
        }

        $recipe = CraftingRecipe::where('result_item_id', $inventoryItem->item_id)->first();

        if (! $recipe || ! $recipe->quality_upgradable) {
            throw new InvalidArgumentException('Качество этой вещи нельзя улучшать.');
        }

        $config = config('equipment.recipe_quality_upgrade');
        $order = array_keys(config('equipment.qualities'));
        $current = $inventoryItem->quality ?? 'white';
        $index = array_search($current, $order, true);

        if ($index === false || $index >= count($order) - 1) {
            throw new InvalidArgumentException('Качество уже максимальное.');
        }

        $next = $order[$index + 1];
        $cost = (int) ($config['cost_per_tier'][$next] ?? 0);

        if ($cost <= 0) {
            throw new InvalidArgumentException('Для этого качества улучшение недоступно.');
        }

        $currencyItem = Item::where('catalog_key', $config['currency'])->first();

        if (! $currencyItem) {
            throw new InvalidArgumentException('Ресурс для улучшения качества не найден в базе.');
        }

        $owned = (int) InventoryItem::where('character_id', $character->id)
            ->where('item_id', $currencyItem->id)
            ->sum('quantity');

        if ($owned < $cost) {
            throw new InvalidArgumentException("Недостаточно: нужно {$cost} × {$currencyItem->name}.");
        }

        DB::transaction(function () use ($character, $inventoryItem, $currencyItem, $cost, $next) {
            $this->inventoryService->removeItem($character, $currencyItem, $cost);
            $inventoryItem->update([
                'quality' => $next,
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

        $sphereItem = Item::where('catalog_key', 'transformation_sphere')->first();

        if (! $sphereItem) {
            throw new InvalidArgumentException('Сферы становления не найдены в базе.');
        }

        $config = config('equipment.dungeon_t2_upgrade');
        $targetLevel = (int) $config['target_level'];
        $level = $inventoryItem->equipment_level ?? 1;

        if ($level >= $targetLevel) {
            throw new InvalidArgumentException("Вещь уже улучшена до уровня {$targetLevel}.");
        }

        $sphereCost = (int) $config['transformation_sphere'];

        DB::transaction(function () use ($character, $inventoryItem, $sphereItem, $sphereCost, $targetLevel) {
            $this->inventoryService->removeItem($character, $sphereItem, $sphereCost);
            $inventoryItem->update([
                'equipment_level' => $targetLevel,
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
            ->filter(fn (InventoryItem $inv) => ($inv->equipment_source ?? 'vendor') !== 'vendor')
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

            if (! $recipe) {
                return null;
            }

            if ($recipe->upgradable) {
                $config = config('equipment.crafted_t2_upgrade');
                $targetLevel = (int) $config['target_level'];

                if ($level < $targetLevel) {
                    $requirements = [
                        $this->itemRequirement($character, 'craftsman_seal', 'Печать ремесленника', (int) $config['craftsman_seal']),
                    ];

                    foreach ($config['resources'] as $catalogKey => $quantity) {
                        $requirements[] = $this->itemRequirement($character, $catalogKey, $catalogKey, (int) $quantity);
                    }

                    $requirements[] = $this->energyRequirement($character, (int) $config['energy']);

                    $actions[] = [
                        'type' => 'crafted_level',
                        'label' => "До ур. {$targetLevel}",
                        'target_level' => $targetLevel,
                        'after_stats' => $this->equipmentService->computeStats($item, $quality, $targetLevel, $source),
                        'requirements' => $requirements,
                        'affordable' => $this->allMet($requirements),
                    ];
                }
            }

            if ($recipe->quality_upgradable) {
                $qConfig = config('equipment.recipe_quality_upgrade');
                $order = array_keys(config('equipment.qualities'));
                $qIndex = array_search($quality, $order, true);

                if ($qIndex !== false && $qIndex < count($order) - 1) {
                    $nextQuality = $order[$qIndex + 1];
                    $coreCost = (int) ($qConfig['cost_per_tier'][$nextQuality] ?? 0);

                    if ($coreCost > 0) {
                        $requirements = [
                            $this->itemRequirement($character, $qConfig['currency'], $qConfig['currency'], $coreCost),
                        ];

                        $actions[] = [
                            'type' => 'crafted_quality',
                            'label' => 'Улучшить качество',
                            'target_quality' => $nextQuality,
                            'target_quality_label' => $this->equipmentService->qualityLabel($nextQuality),
                            'after_stats' => $this->equipmentService->computeStats($item, $nextQuality, $level, $source),
                            'requirements' => $requirements,
                            'affordable' => $this->allMet($requirements),
                        ];
                    }
                }
            }
        }

        if ($source === 'dungeon') {
            $config = config('equipment.dungeon_t2_upgrade');
            $targetLevel = (int) $config['target_level'];

            if ($level < $targetLevel) {
                $requirements = [
                    $this->itemRequirement($character, 'transformation_sphere', 'Сфера становления', (int) $config['transformation_sphere']),
                ];

                $actions[] = [
                    'type' => 'dungeon_level',
                    'label' => "До ур. {$targetLevel}",
                    'target_level' => $targetLevel,
                    'after_stats' => $this->equipmentService->computeStats($item, $quality, $targetLevel, $source),
                    'requirements' => $requirements,
                    'affordable' => $this->allMet($requirements),
                ];
            }
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

    /**
     * @return array{name: string, required: int, owned: int, has_enough: bool, is_energy: bool}
     */
    private function itemRequirement(Character $character, string $catalogKey, string $fallbackName, int $required): array
    {
        $item = Item::where('catalog_key', $catalogKey)->first();

        $owned = $item
            ? (int) InventoryItem::where('character_id', $character->id)
                ->where('item_id', $item->id)
                ->whereNull('quality')
                ->sum('quantity')
            : 0;

        return [
            'name' => $item?->name ?? $fallbackName,
            'required' => $required,
            'owned' => $owned,
            'has_enough' => $owned >= $required,
            'is_energy' => false,
        ];
    }

    /**
     * @return array{name: string, required: int, owned: int, has_enough: bool, is_energy: bool}
     */
    private function energyRequirement(Character $character, int $required): array
    {
        $owned = (int) $character->energy;

        return [
            'name' => 'Энергия',
            'required' => $required,
            'owned' => $owned,
            'has_enough' => $owned >= $required,
            'is_energy' => true,
        ];
    }

    /**
     * @param  array<int, array{has_enough: bool}>  $requirements
     */
    private function allMet(array $requirements): bool
    {
        foreach ($requirements as $requirement) {
            if (! $requirement['has_enough']) {
                return false;
            }
        }

        return true;
    }
}
