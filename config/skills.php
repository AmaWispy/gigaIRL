<?php

return [
    'slots' => [
        ['min_level' => 1, 'max_level' => 9, 'count' => 1],
        ['min_level' => 10, 'max_level' => 19, 'count' => 2],
        ['min_level' => 20, 'max_level' => 30, 'count' => 3],
    ],

    'effects' => [
        'stealth_strike' => [
            'damage_multiplier' => 2,
            'trigger' => 'first_attack',
        ],
        'power_strike' => [
            'damage_multiplier' => 2,
            'trigger' => 'every_nth_attack',
            'n' => 3,
        ],
        'retribution' => [
            'reflect_multiplier' => 2,
            'trigger' => 'every_nth_monster_attack',
            'n' => 5,
        ],
        'cleaving_strike' => [
            'damage_multiplier' => 2,
            'ignore_armor' => true,
            'trigger' => 'every_nth_attack',
            'n' => 4,
        ],
        'stunning_strike' => [
            'stun' => true,
            'trigger' => 'every_nth_attack',
            'n' => 3,
        ],
        'vampire_strike' => [
            'lifesteal_ratio' => 0.4,
            'bonus_damage' => 3,
            'trigger' => 'every_nth_attack',
            'n' => 3,
        ],
        // Каменная кожа: каждый третий удар противника наносит на 20 брони меньше урона.
        'stone_skin' => [
            'bonus_defense' => 20,
            'trigger' => 'every_nth_monster_attack',
            'n' => 3,
        ],
        // Поиск бреши: каждый второй удар срезает 2 брони. Когда броня <= 0, цель получает +25% урона.
        'find_the_gap' => [
            'armor_shred' => 2,
            'trigger' => 'every_nth_attack',
            'n' => 2,
            'broken_armor_bonus_percent' => 25,
        ],
        // Второе дыхание: при HP <= 10% разово лечит на 20% от макс. HP.
        'second_wind' => [
            'hp_threshold_percent' => 10,
            'heal_percent' => 20,
            'once_per_combat' => true,
        ],
        // Палач: пока HP цели ниже 30%, атаки наносят +50% урона.
        'executioner' => [
            'hp_threshold_percent' => 30,
            'damage_bonus_percent' => 50,
        ],
    ],
];
