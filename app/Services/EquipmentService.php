<?php

namespace App\Services;

use App\Models\CraftingRecipe;
use App\Models\InventoryItem;
use App\Models\Item;
use InvalidArgumentException;

class EquipmentService
{
    public function computeBsp(Item $item, string $quality, int $level, string $source): int
    {
        $qualityMultiplier = config("equipment.qualities.{$quality}.multiplier", 1.0);
        $sourceMultiplier = config("equipment.sources.{$source}.stat_multiplier", 1.0);

        return max(1, (int) floor($level * $qualityMultiplier * $sourceMultiplier));
    }

    /**
     * @return array{strength: int, defense: int, max_hp: int, bsp: int}
     */
    public function computeStats(Item $item, string $quality, int $level, string $source): array
    {
        if (! empty($item->stats['fixed'])) {
            $strength = (int) ($item->stats['strength'] ?? 0);
            $defense = (int) ($item->stats['defense'] ?? 0);
            $maxHp = (int) ($item->stats['max_hp'] ?? 0);

            if (! empty($item->stats['fixed_per_quality'])) {
                $qualityTier = config("equipment.qualities.{$quality}.index", 0) + 1;
                $strength *= $qualityTier;
                $defense *= $qualityTier;
                $maxHp *= $qualityTier;
            }

            return [
                'bsp' => 0,
                'strength' => $strength,
                'defense' => $defense,
                'max_hp' => $maxHp,
            ];
        }

        if ($this->usesIncrementalQualityBonus($item->slot ?? '')) {
            return $this->computeStatsWithIncrementalQuality($item, $quality, $level, $source);
        }

        $bsp = $this->computeBsp($item, $quality, $level, $source);

        return $this->statsFromBsp($item, $bsp);
    }

    private function usesIncrementalQualityBonus(string $slot): bool
    {
        return ! in_array($slot, config('equipment.jewelry_cloak_slots', []), true);
    }

    private function computeStatsWithIncrementalQuality(Item $item, string $quality, int $level, string $source): array
    {
        $whiteBsp = $this->computeBsp($item, 'white', $level, $source);
        $stats = $this->statsFromBsp($item, $whiteBsp);
        $qualityIndex = config("equipment.qualities.{$quality}.index", 0);

        if ($qualityIndex <= 0) {
            return $stats;
        }

        $percent = config('equipment.quality_increment.percent', 0.20);
        $minPerTier = config('equipment.quality_increment.min_per_tier', 1);

        foreach (['strength', 'defense', 'max_hp'] as $stat) {
            if ($stats[$stat] <= 0) {
                continue;
            }

            $bonus = max(
                $minPerTier * $qualityIndex,
                (int) floor($stats[$stat] * $percent * $qualityIndex)
            );
            $stats[$stat] += $bonus;
        }

        return $stats;
    }

    /**
     * @return array{strength: int, defense: int, max_hp: int, bsp: int}
     */
    private function statsFromBsp(Item $item, int $bsp): array
    {
        $profile = config("equipment.slot_profiles.{$item->slot}", []);

        return [
            'bsp' => $bsp,
            'strength' => $this->applyProfileStat($bsp, $profile['strength'] ?? 0, true),
            'defense' => $this->applyProfileStat($bsp, $profile['defense'] ?? 0, false),
            'max_hp' => $this->applyProfileStat($bsp, $profile['max_hp'] ?? 0, false),
        ];
    }

    public function computeStatsForInventoryItem(InventoryItem $inventoryItem): array
    {
        $item = $inventoryItem->item;

        if ($item->type !== 'equipment') {
            return ['strength' => 0, 'defense' => 0, 'max_hp' => 0, 'bsp' => 0];
        }

        return $this->computeStats(
            $item,
            $inventoryItem->quality ?? 'white',
            $inventoryItem->equipment_level ?? $item->item_level ?? 1,
            $inventoryItem->equipment_source ?? $item->equipment_source ?? 'vendor',
        );
    }

    public function vendorBuyPrice(Item $item): int
    {
        $bsp = $this->computeBsp($item, 'white', $item->item_level ?? 1, 'vendor');

        return (int) floor($bsp * $bsp * config('equipment.price_formula_multiplier'));
    }

    public function instanceSalePrice(InventoryItem $inventoryItem): int
    {
        $item = $inventoryItem->item;

        if ($item->type !== 'equipment') {
            return $item->merchantSalePrice();
        }

        $source = $inventoryItem->equipment_source ?? $item->equipment_source ?? 'vendor';
        $quality = $inventoryItem->quality ?? 'white';
        $level = $inventoryItem->equipment_level ?? $item->item_level ?? 1;
        $stats = $this->computeStats($item, $quality, $level, $source);
        $bsp = $stats['bsp'];
        $base = (int) floor($bsp * $bsp * config('equipment.price_formula_multiplier'));

        return (int) floor($base * config('game.merchant.sell_ratio'));
    }

    public function qualityEmoji(string $quality): string
    {
        return config("equipment.qualities.{$quality}.emoji", '⚪');
    }

    public function qualityLabel(string $quality): string
    {
        return $this->qualityEmoji($quality);
    }

    public function blacksmithMasteryLevel(Character $character): int
    {
        return config('equipment.blacksmith_mastery.'.$character->blacksmith_rank, 1);
    }

    public function rollCraftInitialQuality(Character $character): string
    {
        $mastery = $this->blacksmithMasteryLevel($character);
        $cfg = config('equipment.craft_color_roll');
        $chance = $cfg['base_percent'] + ($cfg['per_mastery_percent'] * $mastery);

        return random_int(1, 100) <= $chance ? 'green' : 'white';
    }

    public function rollCraftUpgradeQuality(Character $character, string $currentQuality): ?string
    {
        $order = array_keys(config('equipment.qualities'));
        $index = array_search($currentQuality, $order, true);

        if ($index === false || $index >= count($order) - 1) {
            return null;
        }

        $cfg = config('equipment.craft_upgrade_color_roll');
        $mastery = $this->blacksmithMasteryLevel($character);
        $chance = ($cfg['base_percent'] - ($cfg['base_per_quality_index'] * $index))
            + (($cfg['mastery_percent'] - ($cfg['mastery_per_quality_index'] * $index)) * $mastery);
        $chance = min($cfg['max_percent'], max(0, $chance));

        if (random_int(1, 100) > $chance) {
            return null;
        }

        return $order[$index + 1];
    }

    public function nextQuality(string $quality): ?string
    {
        $order = array_keys(config('equipment.qualities'));
        $index = array_search($quality, $order, true);

        if ($index === false || $index >= count($order) - 1) {
            return null;
        }

        return $order[$index + 1];
    }

    public function sealsForVendorQualityUpgrade(string $fromQuality): int
    {
        return config("equipment.vendor_quality_upgrade_seals.{$fromQuality}", 0);
    }

    public function sealsForVendorLevelUpgrade(int $level): int
    {
        return $this->sealsForSourceLevelUpgrade($level, 'vendor');
    }

    public function sealsForSourceLevelUpgrade(int $level, string $source): int
    {
        $step = config("equipment.sources.{$source}.level_step", 5);

        return (int) ceil($level / $step);
    }

    public function spheresForDungeonLevelUpgrade(int $level, int $upgradeCount): int
    {
        return (int) ceil($level / config('equipment.sources.dungeon.level_step')) + $upgradeCount;
    }

    /**
     * @return array{level: int, quality: string, quality_label: string, stats: array}|null
     */
    public function previewItem(Item $item, string $source, string $quality = 'white'): ?array
    {
        if ($item->type !== 'equipment') {
            return null;
        }

        $level = $item->item_level ?? 1;

        return [
            'level' => $level,
            'quality' => $quality,
            'quality_label' => $this->qualityLabel($quality),
            'stats' => $this->computeStats($item, $quality, $level, $source),
        ];
    }

    /**
     * @return array{level: int, variants: array<int, array{quality_label: string, stats: array, note?: string}>}|null
     */
    public function previewCraftResult(Item $item, ?CraftingRecipe $recipe = null): ?array
    {
        if ($item->type !== 'equipment') {
            return null;
        }

        if (! empty($item->stats['fixed'])) {
            $quality = $recipe?->fixed_result_quality ?? 'blue';

            return [
                'level' => $item->item_level ?? 1,
                'variants' => [
                    [
                        'quality' => $quality,
                        'quality_label' => $this->qualityEmoji($quality),
                        'stats' => $this->computeStats($item, $quality, $item->item_level ?? 1, 'crafted'),
                    ],
                ],
            ];
        }

        $level = $item->item_level ?? 1;

        return [
            'level' => $level,
            'variants' => [
                [
                    'quality' => 'white',
                    'quality_label' => $this->qualityEmoji('white'),
                    'stats' => $this->computeStats($item, 'white', $level, 'crafted'),
                ],
                [
                    'quality' => 'green',
                    'quality_label' => $this->qualityEmoji('green'),
                    'stats' => $this->computeStats($item, 'green', $level, 'crafted'),
                    'note' => 'шанс при крафте',
                ],
            ],
        ];
    }

    private function applyProfileStat(int $bsp, float $share, bool $minOne): int
    {
        if ($share <= 0) {
            return 0;
        }

        $value = (int) round($bsp * $share);

        return $minOne ? max(1, $value) : $value;
    }

    public function rollDungeonLootQuality(): string
    {
        $weights = [
            'white' => 40,
            'green' => 35,
            'blue' => 18,
            'purple' => 5,
            'red' => 2,
        ];
        $roll = random_int(1, 100);
        $cumulative = 0;

        foreach ($weights as $quality => $weight) {
            $cumulative += $weight;
            if ($roll <= $cumulative) {
                return $quality;
            }
        }

        return 'white';
    }

    /**
     * @return array{
     *     key: string,
     *     name: string,
     *     item_level: int,
     *     items: list<array{name: string, slot: string|null}>,
     * }|null
     */
    public function getSetInfo(string $setKey, int $itemLevel = 1): ?array
    {
        $config = config("equipment.sets.{$setKey}");

        if (! $config) {
            return null;
        }

        $slotOrder = config('game.equipment_slots', []);

        $items = Item::query()
            ->where('set_key', $setKey)
            ->where('type', 'equipment')
            ->get()
            ->sortBy(fn (Item $item) => array_search($item->slot, $slotOrder, true) ?? 999)
            ->values()
            ->map(fn (Item $item) => [
                'name' => $item->name,
                'slot' => $item->slot,
            ])
            ->all();

        return [
            'key' => $setKey,
            'name' => $config['name'],
            'item_level' => $itemLevel,
            'items' => $items,
        ];
    }

    public function assertEquipmentInstance(InventoryItem $inventoryItem): void
    {
        if ($inventoryItem->item->type !== 'equipment') {
            throw new InvalidArgumentException('Это не экипировка.');
        }
    }
}
