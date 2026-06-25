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
            'lifesteal_ratio' => 0.5,
            'trigger' => 'every_nth_attack',
            'n' => 3,
        ],
    ],
];
