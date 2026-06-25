<?php

namespace App\Admin;

use App\Models\Dungeon;

class DungeonConfigPresenter
{
  private const SOURCE_LABELS = [
    'normal' => 'Обычный моб',
    'rare' => 'Редкий моб',
    'boss' => 'Босс',
    'treasure' => 'Сокровище',
  ];

  private const REWARD_LABELS = [
    'potion' => 'Зелье',
    'set_equipment' => 'Экипировка сета',
    'set_equipment_extra' => 'Доп. экипировка сета',
    'craftsman_seal' => 'Печать мастера',
    'transformation_sphere' => 'Сфера превращения',
  ];

  /** @return array<string, mixed> */
  public function present(Dungeon $dungeon): array
  {
    $dungeon->loadMissing(['lootRules', 'resourcePools.item']);

    return [
      'id' => $dungeon->id,
      'name' => $dungeon->name,
      'catalog_key' => $dungeon->catalog_key,
      'tier' => $dungeon->tier,
      'setting_groups' => $this->settingGroups($dungeon),
      'loot_rules' => $dungeon->lootRules
        ->sortBy(fn ($rule) => [$rule->source, $rule->reward_type])
        ->values()
        ->map(fn ($rule) => [
          'source' => $rule->source,
          'source_label' => self::SOURCE_LABELS[$rule->source] ?? $rule->source,
          'reward_type' => $rule->reward_type,
          'reward_type_label' => self::REWARD_LABELS[$rule->reward_type] ?? $rule->reward_type,
          'chance_percent' => $rule->chance_percent,
        ])
        ->all(),
      'resource_pools' => [
        'common' => $dungeon->resourceItemsForPool('common')
          ->map(fn ($item) => ['name' => $item->name, 'catalog_key' => $item->catalog_key])
          ->values()
          ->all(),
        'rare' => $dungeon->resourceItemsForPool('rare')
          ->map(fn ($item) => ['name' => $item->name, 'catalog_key' => $item->catalog_key])
          ->values()
          ->all(),
      ],
    ];
  }

  /** @return list<array{title: string, fields: list<array{label: string, value: mixed}>}> */
  private function settingGroups(Dungeon $dungeon): array
  {
    return [
      [
        'title' => 'Энергия',
        'fields' => [
          ['label' => 'Вход в данж', 'value' => $dungeon->entry_energy],
          ['label' => 'Следующий этаж', 'value' => $dungeon->floor_energy],
          ['label' => 'Бой', 'value' => $dungeon->combat_energy],
          ['label' => 'Куча ресурсов', 'value' => $dungeon->resource_energy],
          ['label' => 'Сокровище', 'value' => $dungeon->treasure_energy],
          ['label' => 'Вход при осмотре', 'value' => $dungeon->exploration_entrance_energy],
        ],
      ],
      [
        'title' => 'Этажи',
        'fields' => [
          ['label' => 'Всего этажей', 'value' => $dungeon->floors_total],
          ['label' => 'Этаж редкого моба', 'value' => $dungeon->rare_floor],
          ['label' => 'Этаж босса', 'value' => $dungeon->boss_floor],
        ],
      ],
      [
        'title' => 'Экономика',
        'fields' => [
          ['label' => 'Цена пропуска', 'value' => $dungeon->pass_price],
          ['label' => 'Множитель денег с моба', 'value' => $dungeon->mob_money_multiplier],
          ['label' => 'Бонус XP за прохождение', 'value' => "{$dungeon->clear_bonus_xp_percent}%"],
          ['label' => 'Бонус денег за прохождение', 'value' => $dungeon->clear_bonus_money],
          ['label' => 'Печать исследователя', 'value' => $dungeon->explorer_seal_on_clear],
        ],
      ],
      [
        'title' => 'События этажа',
        'fields' => [
          ['label' => 'Шанс кучи ресурсов', 'value' => "{$dungeon->resource_pile_chance}%"],
          ['label' => 'Шанс сокровища', 'value' => "{$dungeon->treasure_chance}%"],
          ['label' => 'Ресурсов в куче (мин)', 'value' => $dungeon->resource_quantity_min],
          ['label' => 'Ресурсов в куче (макс)', 'value' => $dungeon->resource_quantity_max],
          ['label' => 'Редкий ресурс из кучи', 'value' => "{$dungeon->rare_resource_from_pile_chance}%"],
          ['label' => 'Редкий ресурс с моба', 'value' => "{$dungeon->rare_resource_from_mob_chance}%"],
          ['label' => 'Деньги в сокровище (мин)', 'value' => $dungeon->treasure_money_min],
          ['label' => 'Деньги в сокровище (макс)', 'value' => $dungeon->treasure_money_max],
        ],
      ],
      [
        'title' => 'Опыт и экипировка',
        'fields' => [
          ['label' => 'XP множитель (обычный)', 'value' => "×{$dungeon->xp_multiplier_normal}"],
          ['label' => 'XP множитель (редкий)', 'value' => "×{$dungeon->xp_multiplier_rare}"],
          ['label' => 'XP множитель (босс)', 'value' => "×{$dungeon->xp_multiplier_boss}"],
          ['label' => 'Не-оружие в сете', 'value' => "{$dungeon->set_equipment_non_weapon_chance}%"],
        ],
      ],
      [
        'title' => 'Исследование',
        'fields' => [
          ['label' => 'Шанс входа при осмотре', 'value' => "{$dungeon->exploration_entrance_chance}%"],
        ],
      ],
    ];
  }
}
