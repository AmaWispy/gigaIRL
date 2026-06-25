<?php

namespace App\Services;

use App\Models\Dungeon;
use App\Models\DungeonLootRule;
use App\Models\DungeonResourcePool;
use App\Models\Item;

class DungeonConfigService
{
    /**
     * @param  array{
     *     entry_energy?: int,
     *     floor_energy?: int,
     *     combat_energy?: int,
     *     resource_energy?: int,
     *     treasure_energy?: int,
     *     floors_total?: int,
     *     rare_floor?: int,
     *     boss_floor?: int,
     *     pass_price?: int,
     *     resource_pile_chance?: int,
     *     treasure_chance?: int,
     *     resource_quantity_min?: int,
     *     resource_quantity_max?: int,
     *     rare_resource_from_pile_chance?: int,
     *     rare_resource_from_mob_chance?: int,
     *     treasure_money_min?: int,
     *     treasure_money_max?: int,
     *     mob_money_multiplier?: float,
     *     set_equipment_non_weapon_chance?: int,
     *     xp_multiplier_normal?: float,
     *     xp_multiplier_rare?: float,
     *     xp_multiplier_boss?: float,
     *     clear_bonus_xp_percent?: int,
     *     clear_bonus_money?: int,
     *     explorer_seal_on_clear?: int,
     *     exploration_entrance_energy?: int,
     *     exploration_entrance_chance?: int,
     * }  $settings
     * @param  list<array{source: string, reward_type: string, chance_percent: int}>  $lootRules
     * @param  array{common?: list<string>, rare?: list<string>}  $resourcePools
     */
    public function syncDungeon(Dungeon $dungeon, array $settings, array $lootRules, array $resourcePools): void
    {
        $dungeon->update($settings);

        $dungeon->lootRules()->delete();

        foreach ($lootRules as $rule) {
            DungeonLootRule::create([
                'dungeon_id' => $dungeon->id,
                'source' => $rule['source'],
                'reward_type' => $rule['reward_type'],
                'chance_percent' => $rule['chance_percent'],
            ]);
        }

        $dungeon->resourcePools()->delete();

        foreach (['common', 'rare'] as $pool) {
            foreach ($resourcePools[$pool] ?? [] as $catalogKey) {
                $item = Item::where('catalog_key', $catalogKey)->first();

                if (! $item) {
                    continue;
                }

                DungeonResourcePool::create([
                    'dungeon_id' => $dungeon->id,
                    'item_id' => $item->id,
                    'pool' => $pool,
                ]);
            }
        }
    }

    /**
     * Дефолты тира 1 (бывший config/dungeon.php + tier 1 overrides).
     *
     * @return array{settings: array<string, mixed>, loot_rules: list<array{source: string, reward_type: string, chance_percent: int}>, resource_pools: array{common: list<string>, rare: list<string>}}
     */
    public function tierOneDefaults(): array
    {
        return [
            'settings' => [
                'entry_energy' => 10,
                'floor_energy' => 10,
                'combat_energy' => 1,
                'resource_energy' => 1,
                'treasure_energy' => 1,
                'floors_total' => 10,
                'rare_floor' => 5,
                'boss_floor' => 10,
                'pass_price' => 500,
                'resource_pile_chance' => 35,
                'treasure_chance' => 20,
                'resource_quantity_min' => 2,
                'resource_quantity_max' => 4,
                'rare_resource_from_pile_chance' => 15,
                'rare_resource_from_mob_chance' => 8,
                'treasure_money_min' => 15,
                'treasure_money_max' => 75,
                'mob_money_multiplier' => 1.5,
                'set_equipment_non_weapon_chance' => 70,
                'xp_multiplier_normal' => 1.5,
                'xp_multiplier_rare' => 3.5,
                'xp_multiplier_boss' => 6.0,
                'clear_bonus_xp_percent' => 50,
                'clear_bonus_money' => 100,
                'explorer_seal_on_clear' => 1,
                'exploration_entrance_energy' => 2,
                'exploration_entrance_chance' => 8,
            ],
            'loot_rules' => [
                ['source' => 'normal', 'reward_type' => 'potion', 'chance_percent' => 5],
                ['source' => 'normal', 'reward_type' => 'set_equipment', 'chance_percent' => 8],
                ['source' => 'normal', 'reward_type' => 'craftsman_seal', 'chance_percent' => 10],
                ['source' => 'rare', 'reward_type' => 'potion', 'chance_percent' => 8],
                ['source' => 'rare', 'reward_type' => 'set_equipment', 'chance_percent' => 25],
                ['source' => 'rare', 'reward_type' => 'craftsman_seal', 'chance_percent' => 25],
                ['source' => 'boss', 'reward_type' => 'potion', 'chance_percent' => 10],
                ['source' => 'boss', 'reward_type' => 'set_equipment', 'chance_percent' => 60],
                ['source' => 'boss', 'reward_type' => 'set_equipment_extra', 'chance_percent' => 40],
                ['source' => 'boss', 'reward_type' => 'craftsman_seal', 'chance_percent' => 50],
                ['source' => 'treasure', 'reward_type' => 'potion', 'chance_percent' => 7],
                ['source' => 'treasure', 'reward_type' => 'set_equipment', 'chance_percent' => 15],
                ['source' => 'treasure', 'reward_type' => 'craftsman_seal', 'chance_percent' => 12],
                ['source' => 'treasure', 'reward_type' => 'transformation_sphere', 'chance_percent' => 3],
            ],
            'resource_pools' => [
                'common' => ['iron_ore', 'leather', 'wood'],
                'rare' => ['obsidian_shard', 'rune_dust'],
            ],
        ];
    }

    /**
     * Дефолты тира 2 («Пещера Арахнидов»).
     *
     * @return array{settings: array<string, mixed>, loot_rules: list<array{source: string, reward_type: string, chance_percent: int}>, resource_pools: array{common: list<string>, rare: list<string>}}
     */
    public function tierTwoDefaults(): array
    {
        $defaults = $this->tierOneDefaults();

        $defaults['settings'] = array_merge($defaults['settings'], [
            'entry_energy' => 14,
            'pass_price' => 800,
            'treasure_money_min' => 30,
            'treasure_money_max' => 130,
            'mob_money_multiplier' => 1.8,
            'clear_bonus_money' => 200,
            'exploration_entrance_chance' => 8,
            'exploration_entrance_energy' => 3,
        ]);

        $defaults['resource_pools'] = [
            'common' => ['monster_hide', 'mana_crystal', 'twilight_essence'],
            'rare' => ['spider_silk', 'venom_gland'],
        ];

        return $defaults;
    }
}
