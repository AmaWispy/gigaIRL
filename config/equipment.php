<?php

return [
  'qualities' => [
    'white' => ['emoji' => '⚪', 'multiplier' => 0.6, 'index' => 0],
    'green' => ['emoji' => '🟢', 'multiplier' => 1.0, 'index' => 1],
    'blue' => ['emoji' => '🔵', 'multiplier' => 2.5, 'index' => 2],
    'purple' => ['emoji' => '🟣', 'multiplier' => 4.5, 'index' => 3],
    'red' => ['emoji' => '🔴', 'multiplier' => 7.0, 'index' => 4],
  ],

  'sources' => [
    'vendor' => ['stat_multiplier' => 1.0, 'level_step' => 5],
    'crafted' => ['stat_multiplier' => 1.15, 'level_step' => 7],
    'dungeon' => ['stat_multiplier' => 1.265, 'level_step' => 7],
  ],

  'blacksmith_mastery' => [
    'apprentice' => 1,
    'journeyman' => 2,
    'master' => 3,
    'grandmaster' => 4,
  ],

  'craft_color_roll' => [
    'base_percent' => 20,
    'per_mastery_percent' => 15,
  ],

  'craft_upgrade_color_roll' => [
    'base_percent' => 20,
    'base_per_quality_index' => 5,
    'mastery_percent' => 15,
    'mastery_per_quality_index' => 2,
    'max_percent' => 95,
  ],

  'vendor_quality_upgrade_seals' => [
    'white' => 1,
    'green' => 3,
    'blue' => 8,
    'purple' => 20,
  ],

  'price_formula_multiplier' => 6,

  'jewelry_cloak_slots' => ['cloak', 'ring1', 'ring2', 'necklace'],

  'quality_increment' => [
    'percent' => 0.20,
    'min_per_tier' => 1,
  ],

  'slot_profiles' => [
    'weapon' => ['strength' => 1.0, 'defense' => 0.1, 'max_hp' => 0.1],
    'armor' => ['strength' => 0, 'defense' => 1.0, 'max_hp' => 0.5],
    'pants' => ['strength' => 0, 'defense' => 1.0, 'max_hp' => 0.5],
    'helmet' => ['strength' => 0.2, 'defense' => 0.5, 'max_hp' => 0.3],
    'gloves' => ['strength' => 0.2, 'defense' => 0.5, 'max_hp' => 0.3],
    'boots' => ['strength' => 0.2, 'defense' => 0.5, 'max_hp' => 0.3],
    'belt' => ['strength' => 0, 'defense' => 0.4, 'max_hp' => 0.6],
    'cloak' => ['strength' => 0, 'defense' => 0.4, 'max_hp' => 0.6],
    'ring1' => ['strength' => 0.4, 'defense' => 0.3, 'max_hp' => 0.3],
    'ring2' => ['strength' => 0.4, 'defense' => 0.3, 'max_hp' => 0.3],
    'necklace' => ['strength' => 0.4, 'defense' => 0.3, 'max_hp' => 0.3],
  ],

  'drop' => [
    'craftsman_seal' => [
      'monster' => 3,
      'treasure' => 5,
      'rare_monster' => 12,
      'boss' => 25,
    ],
    'transformation_sphere' => [
      'treasure' => 6,
      'rare_monster' => 4,
      'boss' => 15,
    ],
  ],

  'sets' => [
    'mine_fury' => [
      'name' => 'Шахтёрский гнев',
      'bonuses' => [
        2 => ['type' => 'damage_percent', 'value' => 5],
        4 => ['type' => 'defense_percent', 'value' => 10],
        5 => ['type' => 'energy_on_hit', 'value' => 5],
      ],
    ],
  ],
];
