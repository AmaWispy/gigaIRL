<?php

return [
    'timezone' => env('GAME_TIMEZONE', 'Europe/Moscow'),

    'financial_rates' => [
        'primary' => 500,
        'secondary' => 350,
    ],

    'starting' => [
        'hp' => 30,
        'energy' => 0,
        'money' => 50,
        'level' => 1,
        'xp' => 0,
        'strength' => 1,
        'defense' => 1,
    ],

    'level' => [
        'xp_per_level' => 100,
        'xp_level_multiplier' => 1.2,
        'max_level' => 30,
        'hp_bonus' => 5,
        'stat_bonus' => 1,
        'stat_bonus_every_n_levels' => 2,
    ],

    'power' => [
        'armor_multiplier' => 2,
        'damage_multiplier' => 3,
        'hp_divisor' => 10,
    ],

    'exploration' => [
        'look_around_energy' => 1,
        'ambush_chance' => 0.15,
        'actions_min' => 2,
        'actions_max' => 4,
        'monster_energy' => 1,
        'gather_energy' => 1,
        'treasure_energy' => 2,
        'resource_cache_energy' => 2,
        'rare_monster_energy' => 3,
        'boss_energy' => 5,
    ],

    'combat' => [
        'energy_cost' => 1,
        'defense' => [
            // reduction = scale × defense / (defense + pivot); caps at scale (60%)
            'scale' => 0.6,
            'pivot' => 10,
            'min_damage' => 1,
        ],
    ],

    'merchant' => [
        'sell_ratio' => 0.7,
    ],

    'travel' => [
        'default_energy_cost' => 1,
    ],

    'inn' => [
        'hp_restore_percent' => 25,
        'cost' => 150,
    ],

    'potion' => [
        'hp_restore' => 15,
    ],

    'equipment_slots' => [
        'boots',
        'pants',
        'weapon',
        'gloves',
        'helmet',
        'belt',
        'ring1',
        'ring2',
        'necklace',
        'armor',
        'cloak',
    ],

    'professions' => [
        'none',
        'blacksmith',
    ],

    'blacksmith_ranks' => [
        'apprentice' => ['label' => 'Подмастерье', 'min_level' => 1, 'cost' => 0, 'tier' => 'green'],
        'journeyman' => ['label' => 'Ремесленник', 'min_level' => 10, 'cost' => 5000, 'tier' => 'blue'],
        'master' => ['label' => 'Мастер', 'min_level' => 20, 'cost' => 25000, 'tier' => 'purple'],
        'grandmaster' => ['label' => 'Грандмастер', 'min_level' => 28, 'cost' => 100000, 'tier' => 'red'],
    ],

    'item_tiers' => [
        'green' => ['emoji' => '🟢', 'multiplier' => 1.0],
        'blue' => ['emoji' => '🔵', 'multiplier' => 2.5],
        'purple' => ['emoji' => '🟣', 'multiplier' => 4.5],
        'red' => ['emoji' => '🔴', 'multiplier' => 7.0],
        'boss' => ['emoji' => '⭐', 'multiplier' => 0],
    ],
];
