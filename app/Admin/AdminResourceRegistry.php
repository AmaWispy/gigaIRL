<?php

namespace App\Admin;

use App\Models\AchievementCompletion;
use App\Models\AchievementTemplate;
use App\Models\Character;
use App\Models\CharacterDungeonUnlock;
use App\Models\CharacterSkill;
use App\Models\Combat;
use App\Models\CombatRound;
use App\Models\CraftingRecipe;
use App\Models\Dungeon;
use App\Models\DungeonFloorState;
use App\Models\DungeonMonster;
use App\Models\DungeonRun;
use App\Models\EnergyTransaction;
use App\Models\EquippedItem;
use App\Models\ExplorationAction;
use App\Models\ExplorationSession;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\Location;
use App\Models\LocationConnection;
use App\Models\LocationPoi;
use App\Models\MerchantOffer;
use App\Models\Monster;
use App\Models\Skill;
use App\Models\User;
use InvalidArgumentException;

class AdminResourceRegistry
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return [
            'items' => self::items(),
            'monsters' => self::monsters(),
            'skills' => self::skills(),
            'crafting-recipes' => self::craftingRecipes(),
            'achievement-templates' => self::achievementTemplates(),
            'locations' => self::locations(),
            'location-pois' => self::locationPois(),
            'location-connections' => self::locationConnections(),
            'merchant-offers' => self::merchantOffers(),
            'dungeons' => self::dungeons(),
            'dungeon-monsters' => self::dungeonMonsters(),
            'users' => self::users(),
            'characters' => self::characters(),
            'inventory-items' => self::inventoryItems(),
            'equipped-items' => self::equippedItems(),
            'character-skills' => self::characterSkills(),
            'combats' => self::combats(),
            'combat-rounds' => self::combatRounds(),
            'exploration-sessions' => self::explorationSessions(),
            'exploration-actions' => self::explorationActions(),
            'achievement-completions' => self::achievementCompletions(),
            'energy-transactions' => self::energyTransactions(),
            'dungeon-runs' => self::dungeonRuns(),
            'dungeon-floor-states' => self::dungeonFloorStates(),
            'character-dungeon-unlocks' => self::characterDungeonUnlocks(),
        ];
    }

    public static function get(string $key): array
    {
        $resources = self::all();

        if (! isset($resources[$key])) {
            throw new InvalidArgumentException("Unknown admin resource: {$key}");
        }

        return $resources[$key];
    }

    /** @return list<array{group: string, items: list<array{key: string, label: string}>}> */
    public static function navigation(): array
    {
        $groups = [];

        foreach (self::all() as $key => $resource) {
            $group = $resource['group'];
            $groups[$group][] = ['key' => $key, 'label' => $resource['label']];
        }

        $order = ['Контент', 'Мир', 'Данжи', 'Игроки', 'Логи'];

        return collect($order)
            ->filter(fn (string $group) => isset($groups[$group]))
            ->map(fn (string $group) => ['group' => $group, 'items' => $groups[$group]])
            ->values()
            ->all();
    }

    private static function items(): array
    {
        return [
            'label' => 'Предметы',
            'group' => 'Контент',
            'model' => Item::class,
            'editable' => true,
            'creatable' => true,
            'default_sort' => 'name',
            'columns' => ['id', 'name', 'type', 'tier', 'slot', 'item_level', 'buy_price', 'sell_price'],
            'fields' => [
                ['name' => 'name', 'label' => 'Название', 'type' => 'text', 'required' => true],
                ['name' => 'catalog_key', 'label' => 'Ключ каталога', 'type' => 'text'],
                ['name' => 'type', 'label' => 'Тип', 'type' => 'select', 'options' => self::itemTypes(), 'required' => true],
                ['name' => 'tier', 'label' => 'Тир', 'type' => 'select', 'options' => self::tiers()],
                ['name' => 'tier_emoji', 'label' => 'Эмодзи тира', 'type' => 'text'],
                ['name' => 'slot', 'label' => 'Слот', 'type' => 'select', 'options' => self::equipmentSlots()],
                ['name' => 'item_level', 'label' => 'Уровень предмета', 'type' => 'number'],
                ['name' => 'equipment_source', 'label' => 'Источник', 'type' => 'select', 'options' => ['vendor' => 'Торговец', 'crafted' => 'Крафт', 'dungeon' => 'Данж']],
                ['name' => 'set_key', 'label' => 'Ключ сета', 'type' => 'text'],
                ['name' => 'stats', 'label' => 'Статы', 'type' => 'keyvalue'],
                ['name' => 'buy_price', 'label' => 'Цена покупки', 'type' => 'number'],
                ['name' => 'sell_price', 'label' => 'Цена продажи', 'type' => 'number'],
                ['name' => 'description', 'label' => 'Описание', 'type' => 'textarea'],
            ],
        ];
    }

    private static function monsters(): array
    {
        return [
            'label' => 'Мобы и боссы',
            'group' => 'Контент',
            'model' => Monster::class,
            'editable' => true,
            'creatable' => true,
            'default_sort' => 'name',
            'columns' => ['id', 'name', 'tier', 'location_id', 'hp', 'attack', 'defense', 'xp_reward', 'money_reward'],
            'fields' => [
                ['name' => 'name', 'label' => 'Название', 'type' => 'text', 'required' => true],
                ['name' => 'location_id', 'label' => 'Локация', 'type' => 'location_select', 'required' => true],
                ['name' => 'tier', 'label' => 'Тир', 'type' => 'select', 'options' => ['normal' => 'Обычный', 'rare' => 'Редкий', 'boss' => 'Босс'], 'required' => true],
                ['name' => 'flavor_text', 'label' => 'Описание', 'type' => 'textarea'],
                ['name' => 'hp', 'label' => 'HP', 'type' => 'number', 'required' => true],
                ['name' => 'attack', 'label' => 'Атака', 'type' => 'number', 'required' => true],
                ['name' => 'defense', 'label' => 'Защита', 'type' => 'number', 'required' => true],
                ['name' => 'energy_cost', 'label' => 'Стоимость энергии', 'type' => 'number'],
                ['name' => 'xp_reward', 'label' => 'Опыт', 'type' => 'number', 'required' => true],
                ['name' => 'money_reward', 'label' => 'Деньги', 'type' => 'number', 'required' => true],
                ['name' => 'loot_table', 'label' => 'Таблица дропа', 'type' => 'loot_table'],
            ],
        ];
    }

    private static function skills(): array
    {
        return [
            'label' => 'Навыки',
            'group' => 'Контент',
            'model' => Skill::class,
            'editable' => true,
            'creatable' => true,
            'default_sort' => 'name',
            'columns' => ['id', 'catalog_key', 'name', 'type', 'min_learn_level', 'teach_price'],
            'fields' => [
                ['name' => 'catalog_key', 'label' => 'Ключ', 'type' => 'text'],
                ['name' => 'name', 'label' => 'Название', 'type' => 'text', 'required' => true],
                ['name' => 'type', 'label' => 'Тип', 'type' => 'select', 'options' => ['combat' => 'Боевой', 'craft' => 'Ремесло'], 'required' => true],
                ['name' => 'power', 'label' => 'Сила', 'type' => 'number', 'required' => true],
                ['name' => 'priority', 'label' => 'Приоритет', 'type' => 'number'],
                ['name' => 'min_learn_level', 'label' => 'Мин. уровень', 'type' => 'number'],
                ['name' => 'teach_price', 'label' => 'Цена обучения', 'type' => 'number'],
                ['name' => 'description', 'label' => 'Описание', 'type' => 'textarea'],
            ],
        ];
    }

    private static function craftingRecipes(): array
    {
        return [
            'label' => 'Рецепты крафта',
            'group' => 'Контент',
            'model' => CraftingRecipe::class,
            'editable' => true,
            'creatable' => true,
            'default_sort' => 'name',
            'columns' => ['id', 'name', 'result_item_id', 'energy_cost', 'required_profession'],
            'fields' => [
                ['name' => 'name', 'label' => 'Название', 'type' => 'text', 'required' => true],
                ['name' => 'result_item_id', 'label' => 'Результат', 'type' => 'item_select', 'required' => true],
                ['name' => 'result_quantity', 'label' => 'Количество', 'type' => 'number'],
                ['name' => 'energy_cost', 'label' => 'Стоимость энергии', 'type' => 'number'],
                ['name' => 'required_profession', 'label' => 'Профессия', 'type' => 'select', 'options' => ['none' => 'Нет', 'blacksmith' => 'Кузнец']],
                ['name' => 'ingredients', 'label' => 'Ингредиенты', 'type' => 'ingredients'],
            ],
        ];
    }

    private static function achievementTemplates(): array
    {
        return [
            'label' => 'Шаблоны достижений',
            'group' => 'Контент',
            'model' => AchievementTemplate::class,
            'editable' => true,
            'creatable' => true,
            'default_sort' => 'title',
            'columns' => ['id', 'title', 'type', 'reward_points', 'frequency', 'is_default'],
            'fields' => [
                ['name' => 'user_id', 'label' => 'Пользователь', 'type' => 'user_select'],
                ['name' => 'title', 'label' => 'Название', 'type' => 'text', 'required' => true],
                ['name' => 'type', 'label' => 'Тип', 'type' => 'select', 'options' => ['routine' => 'routine', 'financial' => 'financial', 'custom' => 'custom'], 'required' => true],
                ['name' => 'reward_points', 'label' => 'Награда (энергия)', 'type' => 'number'],
                ['name' => 'frequency', 'label' => 'Частота', 'type' => 'select', 'options' => ['once' => 'once', 'daily' => 'daily', 'unlimited' => 'unlimited']],
                ['name' => 'is_default', 'label' => 'По умолчанию', 'type' => 'boolean'],
                ['name' => 'difficulty', 'label' => 'Сложность', 'type' => 'number'],
                ['name' => 'financial_rate', 'label' => 'Финансовая ставка', 'type' => 'number'],
                ['name' => 'is_primary_income', 'label' => 'Основной доход', 'type' => 'boolean'],
            ],
        ];
    }

    private static function locations(): array
    {
        return [
            'label' => 'Локации',
            'group' => 'Мир',
            'model' => Location::class,
            'editable' => true,
            'creatable' => true,
            'default_sort' => 'name',
            'columns' => ['id', 'name', 'type', 'min_power', 'is_safe', 'world_tier'],
            'fields' => [
                ['name' => 'name', 'label' => 'Название', 'type' => 'text', 'required' => true],
                ['name' => 'type', 'label' => 'Тип', 'type' => 'select', 'options' => ['city' => 'Город', 'village' => 'Деревня', 'field' => 'Поле', 'forest' => 'Лес', 'river' => 'Река', 'dungeon' => 'Данж'], 'required' => true],
                ['name' => 'min_power', 'label' => 'Мин. сила', 'type' => 'number'],
                ['name' => 'world_tier', 'label' => 'Тир мира', 'type' => 'number'],
                ['name' => 'is_safe', 'label' => 'Безопасная зона', 'type' => 'boolean'],
                ['name' => 'parent_id', 'label' => 'Родительская локация', 'type' => 'location_select'],
                ['name' => 'description', 'label' => 'Описание', 'type' => 'textarea'],
            ],
        ];
    }

    private static function locationPois(): array
    {
        return [
            'label' => 'Точки интереса',
            'group' => 'Мир',
            'model' => LocationPoi::class,
            'editable' => true,
            'creatable' => true,
            'columns' => ['id', 'location_id', 'name', 'type'],
            'fields' => [
                ['name' => 'location_id', 'label' => 'Локация', 'type' => 'location_select', 'required' => true],
                ['name' => 'name', 'label' => 'Название', 'type' => 'text', 'required' => true],
                ['name' => 'type', 'label' => 'Тип', 'type' => 'select', 'options' => self::poiTypes(), 'required' => true],
                ['name' => 'description', 'label' => 'Описание', 'type' => 'textarea'],
            ],
        ];
    }

    private static function locationConnections(): array
    {
        return [
            'label' => 'Маршруты',
            'group' => 'Мир',
            'model' => LocationConnection::class,
            'editable' => true,
            'creatable' => true,
            'columns' => ['id', 'from_location_id', 'to_location_id', 'energy_cost'],
            'fields' => [
                ['name' => 'from_location_id', 'label' => 'Откуда', 'type' => 'location_select', 'required' => true],
                ['name' => 'to_location_id', 'label' => 'Куда', 'type' => 'location_select', 'required' => true],
                ['name' => 'energy_cost', 'label' => 'Стоимость энергии', 'type' => 'number', 'required' => true],
            ],
        ];
    }

    private static function merchantOffers(): array
    {
        return [
            'label' => 'Торговцы',
            'group' => 'Мир',
            'model' => MerchantOffer::class,
            'editable' => true,
            'creatable' => true,
            'columns' => ['id', 'location_id', 'poi_type', 'item_id', 'buy_price', 'stock'],
            'fields' => [
                ['name' => 'location_id', 'label' => 'Локация', 'type' => 'location_select', 'required' => true],
                ['name' => 'poi_type', 'label' => 'Тип торговца', 'type' => 'select', 'options' => self::poiTypes(), 'required' => true],
                ['name' => 'item_id', 'label' => 'Предмет', 'type' => 'item_select', 'required' => true],
                ['name' => 'buy_price', 'label' => 'Цена покупки', 'type' => 'number'],
                ['name' => 'stock', 'label' => 'Запас', 'type' => 'number'],
            ],
        ];
    }

    private static function dungeons(): array
    {
        return [
            'label' => 'Данжи',
            'group' => 'Данжи',
            'model' => Dungeon::class,
            'editable' => true,
            'creatable' => true,
            'default_sort' => 'name',
            'columns' => ['id', 'name', 'tier', 'set_key', 'item_level', 'min_power', 'pass_price', 'location_id'],
            'fields' => [
                ['name' => 'catalog_key', 'label' => 'Ключ', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Название', 'type' => 'text', 'required' => true],
                ['name' => 'tier', 'label' => 'Тир', 'type' => 'number', 'required' => true],
                ['name' => 'set_key', 'label' => 'Ключ сета', 'type' => 'text', 'required' => true],
                ['name' => 'item_level', 'label' => 'Уровень предметов', 'type' => 'number', 'required' => true],
                ['name' => 'min_power', 'label' => 'Мин. сила', 'type' => 'number'],
                ['name' => 'location_id', 'label' => 'Локация', 'type' => 'location_select'],
                ['name' => 'description', 'label' => 'Описание', 'type' => 'textarea'],
                ['name' => 'entry_energy', 'label' => 'Энергия: вход', 'type' => 'number'],
                ['name' => 'floor_energy', 'label' => 'Энергия: этаж', 'type' => 'number'],
                ['name' => 'combat_energy', 'label' => 'Энергия: бой', 'type' => 'number'],
                ['name' => 'resource_energy', 'label' => 'Энергия: ресурсы', 'type' => 'number'],
                ['name' => 'treasure_energy', 'label' => 'Энергия: сокровище', 'type' => 'number'],
                ['name' => 'floors_total', 'label' => 'Этажей всего', 'type' => 'number'],
                ['name' => 'rare_floor', 'label' => 'Этаж редкого моба', 'type' => 'number'],
                ['name' => 'boss_floor', 'label' => 'Этаж босса', 'type' => 'number'],
                ['name' => 'pass_price', 'label' => 'Цена пропуска', 'type' => 'number'],
                ['name' => 'resource_pile_chance', 'label' => 'Шанс кучи ресурсов %', 'type' => 'number'],
                ['name' => 'treasure_chance', 'label' => 'Шанс сокровища %', 'type' => 'number'],
                ['name' => 'resource_quantity_min', 'label' => 'Ресурсов в куче (мин)', 'type' => 'number'],
                ['name' => 'resource_quantity_max', 'label' => 'Ресурсов в куче (макс)', 'type' => 'number'],
                ['name' => 'rare_resource_from_pile_chance', 'label' => 'Редкий ресурс из кучи %', 'type' => 'number'],
                ['name' => 'rare_resource_from_mob_chance', 'label' => 'Редкий ресурс с моба %', 'type' => 'number'],
                ['name' => 'treasure_money_min', 'label' => 'Деньги в сокровище (мин)', 'type' => 'number'],
                ['name' => 'treasure_money_max', 'label' => 'Деньги в сокровище (макс)', 'type' => 'number'],
                ['name' => 'mob_money_multiplier', 'label' => 'Множитель денег с моба', 'type' => 'number'],
                ['name' => 'set_equipment_non_weapon_chance', 'label' => 'Шанс не-оружия в сете %', 'type' => 'number'],
                ['name' => 'xp_multiplier_normal', 'label' => 'XP множитель (обычный)', 'type' => 'number'],
                ['name' => 'xp_multiplier_rare', 'label' => 'XP множитель (редкий)', 'type' => 'number'],
                ['name' => 'xp_multiplier_boss', 'label' => 'XP множитель (босс)', 'type' => 'number'],
                ['name' => 'clear_bonus_xp_percent', 'label' => 'Бонус XP за прохождение %', 'type' => 'number'],
                ['name' => 'clear_bonus_money', 'label' => 'Бонус денег за прохождение', 'type' => 'number'],
                ['name' => 'explorer_seal_on_clear', 'label' => 'Печать исследователя', 'type' => 'number'],
                ['name' => 'exploration_entrance_energy', 'label' => 'Энергия входа при осмотре', 'type' => 'number'],
                ['name' => 'exploration_entrance_chance', 'label' => 'Шанс входа при осмотре %', 'type' => 'number'],
            ],
        ];
    }

    private static function dungeonMonsters(): array
    {
        return [
            'label' => 'Мобы данжей',
            'group' => 'Данжи',
            'model' => DungeonMonster::class,
            'editable' => true,
            'creatable' => true,
            'columns' => ['id', 'dungeon_id', 'role', 'monster_id'],
            'fields' => [
                ['name' => 'dungeon_id', 'label' => 'Данж', 'type' => 'dungeon_select', 'required' => true],
                ['name' => 'role', 'label' => 'Роль', 'type' => 'select', 'options' => ['normal' => 'Обычный', 'rare' => 'Редкий', 'boss' => 'Босс'], 'required' => true],
                ['name' => 'monster_id', 'label' => 'Моб', 'type' => 'monster_select', 'required' => true],
            ],
        ];
    }

    private static function users(): array
    {
        return [
            'label' => 'Пользователи',
            'group' => 'Игроки',
            'model' => User::class,
            'editable' => true,
            'creatable' => false,
            'columns' => ['id', 'nickname', 'email', 'is_admin', 'created_at'],
            'fields' => [
                ['name' => 'email', 'label' => 'Email', 'type' => 'text', 'required' => true],
                ['name' => 'nickname', 'label' => 'Никнейм', 'type' => 'text'],
                ['name' => 'status', 'label' => 'Статус', 'type' => 'text'],
                ['name' => 'is_admin', 'label' => 'Администратор', 'type' => 'boolean'],
            ],
        ];
    }

    private static function characters(): array
    {
        return [
            'label' => 'Персонажи',
            'group' => 'Игроки',
            'model' => Character::class,
            'editable' => true,
            'creatable' => false,
            'columns' => ['id', 'user_id', 'level', 'xp', 'power', 'hp', 'max_hp', 'energy', 'money'],
            'fields' => [
                ['name' => 'current_location_id', 'label' => 'Локация', 'type' => 'location_select'],
                ['name' => 'level', 'label' => 'Уровень', 'type' => 'number'],
                ['name' => 'xp', 'label' => 'Опыт', 'type' => 'number'],
                ['name' => 'hp', 'label' => 'HP', 'type' => 'number'],
                ['name' => 'max_hp', 'label' => 'Макс. HP', 'type' => 'number'],
                ['name' => 'strength', 'label' => 'Сила', 'type' => 'number'],
                ['name' => 'defense', 'label' => 'Защита', 'type' => 'number'],
                ['name' => 'power', 'label' => 'Power', 'type' => 'number'],
                ['name' => 'energy', 'label' => 'Энергия', 'type' => 'number'],
                ['name' => 'money', 'label' => 'Деньги', 'type' => 'number'],
                ['name' => 'profession', 'label' => 'Профессия', 'type' => 'select', 'options' => ['none' => 'Нет', 'blacksmith' => 'Кузнец']],
                ['name' => 'blacksmith_rank', 'label' => 'Ранг кузнеца', 'type' => 'select', 'options' => ['apprentice' => 'Подмастерье', 'journeyman' => 'Ремесленник', 'master' => 'Мастер', 'grandmaster' => 'Грандмастер']],
            ],
        ];
    }

    private static function inventoryItems(): array
    {
        return [
            'label' => 'Инвентарь',
            'group' => 'Игроки',
            'model' => InventoryItem::class,
            'editable' => true,
            'creatable' => true,
            'columns' => ['id', 'character_id', 'item_id', 'quantity', 'quality', 'equipment_level'],
            'fields' => [
                ['name' => 'character_id', 'label' => 'Персонаж', 'type' => 'character_select', 'required' => true],
                ['name' => 'item_id', 'label' => 'Предмет', 'type' => 'item_select', 'required' => true],
                ['name' => 'quantity', 'label' => 'Количество', 'type' => 'number'],
                ['name' => 'quality', 'label' => 'Качество', 'type' => 'select', 'options' => self::qualities()],
                ['name' => 'equipment_level', 'label' => 'Уровень экипировки', 'type' => 'number'],
                ['name' => 'equipment_source', 'label' => 'Источник', 'type' => 'select', 'options' => ['vendor' => 'Торговец', 'crafted' => 'Крафт', 'dungeon' => 'Данж']],
                ['name' => 'upgrade_count', 'label' => 'Улучшений', 'type' => 'number'],
            ],
        ];
    }

    private static function equippedItems(): array
    {
        return [
            'label' => 'Экипировка',
            'group' => 'Игроки',
            'model' => EquippedItem::class,
            'editable' => true,
            'creatable' => true,
            'columns' => ['id', 'character_id', 'slot', 'inventory_item_id'],
            'fields' => [
                ['name' => 'character_id', 'label' => 'Персонаж', 'type' => 'character_select', 'required' => true],
                ['name' => 'slot', 'label' => 'Слот', 'type' => 'select', 'options' => self::equipmentSlots(), 'required' => true],
                ['name' => 'inventory_item_id', 'label' => 'Предмет инвентаря', 'type' => 'number', 'required' => true],
            ],
        ];
    }

    private static function characterSkills(): array
    {
        return [
            'label' => 'Навыки персонажей',
            'group' => 'Игроки',
            'model' => CharacterSkill::class,
            'editable' => true,
            'creatable' => true,
            'columns' => ['id', 'character_id', 'skill_id', 'level'],
            'fields' => [
                ['name' => 'character_id', 'label' => 'Персонаж', 'type' => 'character_select', 'required' => true],
                ['name' => 'skill_id', 'label' => 'Навык', 'type' => 'skill_select', 'required' => true],
                ['name' => 'level', 'label' => 'Уровень', 'type' => 'number'],
            ],
        ];
    }

    private static function readOnly(string $label, string $group, string $model, array $columns): array
    {
        return [
            'label' => $label,
            'group' => $group,
            'model' => $model,
            'editable' => false,
            'creatable' => false,
            'columns' => $columns,
            'fields' => [],
        ];
    }

    private static function combats(): array
    {
        return self::readOnly('Бои', 'Логи', Combat::class, ['id', 'character_id', 'monster_id', 'status', 'created_at']);
    }

    private static function combatRounds(): array
    {
        return self::readOnly('Раунды боя', 'Логи', CombatRound::class, ['id', 'combat_id', 'round_number', 'actor', 'damage']);
    }

    private static function explorationSessions(): array
    {
        return self::readOnly('Исследования', 'Логи', ExplorationSession::class, ['id', 'character_id', 'location_id', 'is_active', 'created_at']);
    }

    private static function explorationActions(): array
    {
        return self::readOnly('Действия исследования', 'Логи', ExplorationAction::class, ['id', 'exploration_session_id', 'action_type', 'energy_cost', 'is_resolved']);
    }

    private static function achievementCompletions(): array
    {
        return self::readOnly('Выполненные достижения', 'Логи', AchievementCompletion::class, ['id', 'character_id', 'achievement_template_id', 'completed_at']);
    }

    private static function energyTransactions(): array
    {
        return self::readOnly('Транзакции энергии', 'Логи', EnergyTransaction::class, ['id', 'character_id', 'amount', 'source', 'created_at']);
    }

    private static function dungeonRuns(): array
    {
        return self::readOnly('Прохождения данжей', 'Логи', DungeonRun::class, ['id', 'character_id', 'dungeon_id', 'current_floor', 'is_active', 'completed']);
    }

    private static function dungeonFloorStates(): array
    {
        return self::readOnly('Этажи данжей', 'Логи', DungeonFloorState::class, ['id', 'dungeon_run_id', 'floor', 'monster_id', 'mob_defeated']);
    }

    private static function characterDungeonUnlocks(): array
    {
        return self::readOnly('Разблокировки данжей', 'Логи', CharacterDungeonUnlock::class, ['id', 'character_id', 'dungeon_id', 'created_at']);
    }

    /** @return array<string, string> */
    private static function itemTypes(): array
    {
        return [
            'equipment' => 'Экипировка',
            'consumable' => 'Расходник',
            'resource' => 'Ресурс',
            'teleport_stone' => 'Камень перемещения',
            'upgrade_material' => 'Материал улучшения',
        ];
    }

    /** @return array<string, string> */
    private static function tiers(): array
    {
        return [
            'white' => 'Белый',
            'green' => 'Зелёный',
            'blue' => 'Синий',
            'purple' => 'Фиолетовый',
            'red' => 'Красный',
            'boss' => 'Босс',
        ];
    }

    /** @return array<string, string> */
    private static function qualities(): array
    {
        return [
            'white' => 'Белое',
            'green' => 'Зелёное',
            'blue' => 'Синее',
            'purple' => 'Фиолетовое',
            'red' => 'Красное',
        ];
    }

    /** @return array<string, string> */
    private static function poiTypes(): array
    {
        return [
            'material_merchant' => 'Торговец сырьём',
            'armorer' => 'Оружейник',
            'inn' => 'Гостиница',
            'forge' => 'Кузница',
            'alchemist' => 'Алхимик',
            'guild_master' => 'Гильдмастер',
        ];
    }

    /** @return array<string, string> */
    private static function equipmentSlots(): array
    {
        return array_combine(config('game.equipment_slots'), config('game.equipment_slots'));
    }
}
