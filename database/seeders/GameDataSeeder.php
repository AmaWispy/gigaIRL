<?php

namespace Database\Seeders;

use App\Enums\DungeonMonsterRole;
use App\Enums\AchievementFrequency;
use App\Enums\AchievementType;
use App\Models\AchievementTemplate;
use App\Models\CraftingRecipe;
use App\Models\Dungeon;
use App\Models\DungeonMonster;
use App\Models\Item;
use App\Models\Location;
use App\Models\LocationConnection;
use App\Models\LocationPoi;
use App\Models\MerchantOffer;
use App\Models\Monster;
use App\Models\Skill;
use App\Services\EquipmentService;
use Illuminate\Database\Seeder;

class GameDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLocations();
        $this->seedItems();
        $this->seedMonsters();
        $this->seedDefaultAchievements();
        $this->seedSkills();
        $this->seedCraftingRecipes();
        $this->seedMerchants();
        $this->seedDungeons();
    }

    private function seedLocations(): void
    {
        $city = Location::firstOrCreate(
            ['name' => 'Староград'],
            ['type' => 'city', 'min_power' => 0, 'is_safe' => true, 'description' => 'Стартовый город. Здесь безопасно.']
        );

        $pois = [
            ['name' => 'Рынок сырья', 'type' => 'material_merchant', 'description' => 'Торговец сырьём: «Руда не горит, если не продавать!»'],
            ['name' => 'Оружейник', 'type' => 'armorer', 'description' => 'Готовая экипировка и редкие находки'],
            ['name' => 'Гостиница', 'type' => 'inn', 'description' => ''],
            ['name' => 'Кузница', 'type' => 'forge', 'description' => 'Крафт и ранги кузнеца'],
            ['name' => 'Гильдмастер', 'type' => 'guild_master', 'description' => 'Пропуска в данжи и зелья'],
            ['name' => 'Наставник приёмов', 'type' => 'skill_trainer', 'description' => 'Обучение боевым навыкам'],
        ];

        foreach ($pois as $poi) {
            LocationPoi::updateOrCreate(
                ['location_id' => $city->id, 'type' => $poi['type']],
                ['name' => $poi['name'], 'description' => $poi['description']]
            );
        }

        $village = Location::firstOrCreate(
            ['name' => 'Зелёная Деревня'],
            ['type' => 'village', 'min_power' => 0, 'is_safe' => true, 'description' => 'Тихая деревня. Здесь живёт единственный алхимик с подозрительно дорогими зельями.']
        );

        LocationPoi::firstOrCreate(
            ['location_id' => $village->id, 'type' => 'alchemist'],
            ['name' => 'Алхимик Борис', 'description' => 'Зелья по завышенной цене. Всего 2 штуки — не разгуляешься']
        );

        LocationPoi::firstOrCreate(
            ['location_id' => $village->id, 'type' => 'armorer'],
            ['name' => 'Оружейник Кузьма', 'description' => 'Белая экипировка ур. 7 для деревенских героев']
        );

        $forest = Location::firstOrCreate(
            ['name' => 'Тёмный Лес'],
            ['type' => 'forest', 'min_power' => 10, 'world_tier' => 1, 'is_safe' => false, 'description' => 'Тир 1. Здесь водятся кабаны, разбойники и сплетни о Лесной Ведьме.']
        );

        $field = Location::firstOrCreate(
            ['name' => 'Золотое Поле'],
            ['type' => 'field', 'min_power' => 5, 'world_tier' => 1, 'is_safe' => false, 'description' => 'Открытое поле. Дикие кабаны считают его своим парковочным местом.']
        );

        Location::firstOrCreate(
            ['name' => 'Заброшенная Шахта'],
            ['type' => 'dungeon', 'min_power' => 30, 'world_tier' => 1, 'is_safe' => false, 'description' => 'Тёмный данж с сильными врагами.']
        );

        $dungeon = Location::where('name', 'Заброшенная Шахта')->first();

        $connections = [
            [$city->id, $village->id, 1],
            [$city->id, $forest->id, 2],
            [$city->id, $field->id, 1],
            [$forest->id, $dungeon->id, 3],
            [$village->id, $field->id, 1],
        ];

        foreach ($connections as [$from, $to, $cost]) {
            LocationConnection::updateOrCreate(
                ['from_location_id' => $from, 'to_location_id' => $to],
                ['energy_cost' => $cost]
            );
            LocationConnection::updateOrCreate(
                ['from_location_id' => $to, 'to_location_id' => $from],
                ['energy_cost' => $cost]
            );
        }
    }

    private function seedItems(): void
    {
        $equipment = app(EquipmentService::class);

        $vendorGear = [
            ['catalog_key' => 'V-W01', 'name' => 'Потёртый кинжал', 'slot' => 'weapon', 'item_level' => 5],
            ['catalog_key' => 'V-A01', 'name' => 'Льняной нагрудник', 'slot' => 'armor', 'item_level' => 5],
            ['catalog_key' => 'V-P01', 'name' => 'Льняные штаны', 'slot' => 'pants', 'item_level' => 5],
            ['catalog_key' => 'V-G01', 'name' => 'Простые перчатки', 'slot' => 'gloves', 'item_level' => 5],
            ['catalog_key' => 'V-B01', 'name' => 'Соломенные сапоги', 'slot' => 'boots', 'item_level' => 5],
            ['catalog_key' => 'V-W02', 'name' => 'Железный меч', 'slot' => 'weapon', 'item_level' => 7],
            ['catalog_key' => 'V-A02', 'name' => 'Кожаный нагрудник', 'slot' => 'armor', 'item_level' => 7],
            ['catalog_key' => 'V-P02', 'name' => 'Кожаные штаны', 'slot' => 'pants', 'item_level' => 7],
            ['catalog_key' => 'V-H01', 'name' => 'Кожаный шлем', 'slot' => 'helmet', 'item_level' => 7],
            ['catalog_key' => 'V-C01', 'name' => 'Деревянный плащ', 'slot' => 'cloak', 'item_level' => 7],
        ];

        $craftedGear = [
            ['catalog_key' => 'C-W01', 'name' => 'Железный меч (крафт)', 'slot' => 'weapon', 'item_level' => 7],
            ['catalog_key' => 'C-A01', 'name' => 'Кожаный доспех (крафт)', 'slot' => 'armor', 'item_level' => 7],
            ['catalog_key' => 'C-P01', 'name' => 'Усиленные поножи', 'slot' => 'pants', 'item_level' => 7],
            ['catalog_key' => 'C-G01', 'name' => 'Кожаные перчатки (крафт)', 'slot' => 'gloves', 'item_level' => 7],
            ['catalog_key' => 'C-H01', 'name' => 'Кожаный шлем (крафт)', 'slot' => 'helmet', 'item_level' => 7],
            ['catalog_key' => 'C-BO01', 'name' => 'Укреплённые сапоги', 'slot' => 'boots', 'item_level' => 7],
            ['catalog_key' => 'C-BE01', 'name' => 'Кожаный пояс (крафт)', 'slot' => 'belt', 'item_level' => 7],
            ['catalog_key' => 'C-R01', 'name' => 'Кольцо закалённой воли', 'slot' => 'ring1', 'item_level' => 7],
        ];

        $dungeonGear = [
            ['catalog_key' => 'D-W01', 'name' => 'Кирка-клинок', 'slot' => 'weapon', 'item_level' => 7, 'set_key' => 'mine_fury'],
            ['catalog_key' => 'D-H01', 'name' => 'Шлем забойщика', 'slot' => 'helmet', 'item_level' => 7, 'set_key' => 'mine_fury'],
            ['catalog_key' => 'D-A01', 'name' => 'Нагрудник с рунами', 'slot' => 'armor', 'item_level' => 7, 'set_key' => 'mine_fury'],
            ['catalog_key' => 'D-G01', 'name' => 'Перчатки шахтёра', 'slot' => 'gloves', 'item_level' => 7, 'set_key' => 'mine_fury'],
            ['catalog_key' => 'D-P01', 'name' => 'Штаны забойщика', 'slot' => 'pants', 'item_level' => 7, 'set_key' => 'mine_fury'],
        ];

        $dungeonExtras = [
            [
                'catalog_key' => 'D-R01',
                'name' => 'Кольцо глубины',
                'slot' => 'ring1',
                'item_level' => 7,
                'stats' => [
                    'fixed' => true,
                    'fixed_per_quality' => true,
                    'strength' => 1,
                    'defense' => 0,
                    'max_hp' => 3,
                ],
                'description' => 'Кольцо из глубин шахты: +1 урона и +3 HP за тир качества. Не входит в сет и не падает в данже.',
            ],
        ];

        $items = [
            ['catalog_key' => 'teleport_stone', 'name' => 'Камень перемещения', 'type' => 'teleport_stone', 'buy_price' => 200, 'sell_price' => 50, 'description' => 'Телепорт без траты энергии'],
            ['catalog_key' => 'healing_potion', 'name' => 'Зелье лечения', 'type' => 'consumable', 'tier' => 'green', 'tier_emoji' => '🟢', 'stats' => ['hp_restore' => 15], 'buy_price' => 250, 'sell_price' => 10, 'description' => 'Восстанавливает 15 HP'],
            ['catalog_key' => 'iron_ore', 'name' => '🟢 Железная руда', 'type' => 'resource', 'tier' => 'green', 'tier_emoji' => '🟢', 'buy_price' => 20, 'sell_price' => 5, 'description' => 'Тир 1. Основа кузнечного дела'],
            ['catalog_key' => 'wood', 'name' => '🟢 Крепкая древесина', 'type' => 'resource', 'tier' => 'green', 'tier_emoji' => '🟢', 'buy_price' => 15, 'sell_price' => 4, 'description' => 'Тир 1'],
            ['catalog_key' => 'leather', 'name' => '🟢 Грубая кожа', 'type' => 'resource', 'tier' => 'green', 'tier_emoji' => '🟢', 'buy_price' => 12, 'sell_price' => 3, 'description' => 'Тир 1'],
            ['catalog_key' => 'monster_hide', 'name' => '🔵 Шкура монстра', 'type' => 'resource', 'tier' => 'blue', 'tier_emoji' => '🔵', 'buy_price' => 80, 'sell_price' => 25, 'description' => 'Тир 2'],
            ['catalog_key' => 'mana_crystal', 'name' => '🔵 Кристалл маны', 'type' => 'resource', 'tier' => 'blue', 'tier_emoji' => '🔵', 'buy_price' => 120, 'sell_price' => 40, 'description' => 'Тир 2'],
            ['catalog_key' => 'witch_core', 'name' => '⭐ Ядро Ведьмы', 'type' => 'resource', 'tier' => 'boss', 'tier_emoji' => '⭐', 'buy_price' => 0, 'sell_price' => 500, 'description' => 'Эксклюзив босса'],
            ['catalog_key' => 'craftsman_seal', 'name' => 'Печать ремесленника', 'type' => 'upgrade_material', 'buy_price' => 0, 'sell_price' => 10, 'description' => 'Улучшение торговой и крафтовой экипировки'],
            ['catalog_key' => 'transformation_sphere', 'name' => 'Сфера становления', 'type' => 'upgrade_material', 'buy_price' => 0, 'sell_price' => 25, 'description' => 'Повышение уровня данжевого сета'],
            ['catalog_key' => 'explorer_seal', 'name' => 'Печать исследователя', 'type' => 'dungeon_token', 'buy_price' => 0, 'sell_price' => 50, 'description' => 'Награда за полное прохождение данжа'],
            ['catalog_key' => 'dungeon_pass_t1', 'name' => 'Пропуск в Шахту', 'type' => 'dungeon_pass', 'buy_price' => 500, 'sell_price' => 0, 'description' => 'Расходуется при входе в «Заброшенная Шахта»', 'stats' => ['dungeon_key' => 'mine_t1']],
            ['catalog_key' => 'obsidian_shard', 'name' => '🟣 Обсидиановый осколок', 'type' => 'resource', 'tier' => 'purple', 'tier_emoji' => '🟣', 'buy_price' => 0, 'sell_price' => 40, 'description' => 'Редкий данжевый ресурс тира 1'],
            ['catalog_key' => 'rune_dust', 'name' => '🔵 Пыль рун', 'type' => 'resource', 'tier' => 'blue', 'tier_emoji' => '🔵', 'buy_price' => 0, 'sell_price' => 35, 'description' => 'Редкий данжевый ресурс тира 1'],
            ['catalog_key' => 'recipe_scroll_tempered_will_ring', 'name' => 'Свиток: Кольцо закалённой воли', 'type' => 'recipe_scroll', 'tier' => 'blue', 'tier_emoji' => '🔵', 'buy_price' => 300, 'sell_price' => 30, 'description' => 'Расходуется при создании кольца.'],
        ];

        foreach ($items as $item) {
            Item::updateOrCreate(['catalog_key' => $item['catalog_key']], $item);
        }

        foreach ($vendorGear as $gear) {
            $template = array_merge($gear, [
                'type' => 'equipment',
                'equipment_source' => 'vendor',
                'tier' => 'white',
                'tier_emoji' => '⚪',
                'description' => 'Торговая экипировка.',
            ]);
            $template['buy_price'] = $equipment->vendorBuyPrice(new Item($template));
            Item::updateOrCreate(['catalog_key' => $gear['catalog_key']], $template);
        }

        foreach ($craftedGear as $gear) {
            $extra = [];

            if ($gear['catalog_key'] === 'C-R01') {
                $extra = [
                    'tier' => 'blue',
                    'tier_emoji' => '🔵',
                    'stats' => [
                        'fixed' => true,
                        'strength' => 2,
                        'defense' => 0,
                        'max_hp' => 0,
                    ],
                    'description' => 'Редкое кольцо с фиксированным бонусом +2 к урону. Не улучшается.',
                ];
            }

            Item::updateOrCreate(['catalog_key' => $gear['catalog_key']], array_merge($gear, [
                'type' => 'equipment',
                'equipment_source' => 'crafted',
                'buy_price' => 0,
                'sell_price' => 0,
                'description' => 'Крафтовая экипировка (+15% к торговой).',
            ], $extra));
        }

        Item::whereIn('catalog_key', ['C-C01', 'C-R02', 'C-N01', 'D-N01'])->each(function (Item $item) {
            $item->inventoryItems()->each(function ($inv) {
                $inv->equippedSlot()?->delete();
                $inv->delete();
            });
            CraftingRecipe::where('result_item_id', $item->id)->delete();
            $item->delete();
        });

        foreach ($dungeonGear as $gear) {
            Item::updateOrCreate(['catalog_key' => $gear['catalog_key']], array_merge($gear, [
                'type' => 'equipment',
                'equipment_source' => 'dungeon',
                'buy_price' => 0,
                'sell_price' => 0,
                'set_key' => $gear['set_key'] ?? null,
                'description' => 'Предмет сета «Шахтёрский гнев».',
            ]));
        }

        foreach ($dungeonExtras as $gear) {
            Item::updateOrCreate(['catalog_key' => $gear['catalog_key']], array_merge($gear, [
                'type' => 'equipment',
                'equipment_source' => 'dungeon',
                'buy_price' => 0,
                'sell_price' => 0,
                'set_key' => null,
            ]));
        }
    }

    private function seedMonsters(): void
    {
        $forest = Location::where('type', 'forest')->first();
        $field = Location::where('type', 'field')->first();

        $ore = Item::where('catalog_key', 'iron_ore')->first();
        $wood = Item::where('catalog_key', 'wood')->first();
        $leather = Item::where('catalog_key', 'leather')->first();
        $hide = Item::where('catalog_key', 'monster_hide')->first();
        $crystal = Item::where('catalog_key', 'mana_crystal')->first();
        $witchCore = Item::where('catalog_key', 'witch_core')->first();
        $seal = Item::where('catalog_key', 'craftsman_seal')->first();
        $sphere = Item::where('catalog_key', 'transformation_sphere')->first();
        $dungeonWeapon = Item::where('catalog_key', 'D-W01')->first();

        Monster::updateOrCreate(
            ['name' => 'Дикий Кабан'],
            [
                'flavor_text' => 'Взревел так громко, что соседний разбойник попросил убавить громкость.',
                'hp' => 20, 'attack' => 2, 'defense' => 1, 'tier' => 'normal', 'energy_cost' => 1,
                'xp_reward' => 12, 'money_reward' => 8, 'location_id' => $field->id,
                'loot_table' => [
                    ['item_id' => $ore->id, 'chance' => 80, 'quantity' => 1],
                    ['item_id' => $leather->id, 'chance' => 50, 'quantity' => 1],
                    ['item_id' => $seal->id, 'chance' => 3, 'quantity' => 1],
                ],
            ]
        );

        Monster::updateOrCreate(
            ['name' => 'Разбойник'],
            [
                'flavor_text' => '«Стой! Отдай кошелёк!» — «Стой! Отдай HP!»',
                'hp' => 45, 'attack' => 4, 'defense' => 2, 'tier' => 'normal', 'energy_cost' => 1,
                'xp_reward' => 20, 'money_reward' => 15, 'location_id' => $forest->id,
                'loot_table' => [
                    ['item_id' => $wood->id, 'chance' => 70, 'quantity' => 1],
                    ['item_id' => $leather->id, 'chance' => 30, 'quantity' => 1],
                    ['item_id' => $seal->id, 'chance' => 3, 'quantity' => 1],
                ],
            ]
        );

        Monster::updateOrCreate(
            ['name' => 'Вожак Стаи'],
            [
                'flavor_text' => 'Стая кабанов смотрит с трибун. Это их менеджер.',
                'hp' => 80, 'attack' => 6, 'defense' => 2, 'tier' => 'rare', 'energy_cost' => 2,
                'xp_reward' => 45, 'money_reward' => 35, 'location_id' => $forest->id,
                'loot_table' => [
                    ['item_id' => $leather->id, 'chance' => 100, 'quantity' => 2],
                    ['item_id' => $hide->id, 'chance' => 20, 'quantity' => 1],
                    ['item_id' => $seal->id, 'chance' => 12, 'quantity' => 1],
                    ['item_id' => $sphere->id, 'chance' => 4, 'quantity' => 1],
                    ['item_id' => $dungeonWeapon->id, 'chance' => 8, 'quantity' => 1, 'equipment_quality' => 'random'],
                ],
            ]
        );

        Monster::updateOrCreate(
            ['name' => 'Лесная Ведьма'],
            [
                'flavor_text' => '«Ты пришёл за лутом или за советом?» — и атаковала.',
                'hp' => 150, 'attack' => 8, 'defense' => 3, 'tier' => 'boss', 'energy_cost' => 5,
                'xp_reward' => 120, 'money_reward' => 80, 'location_id' => $forest->id,
                'loot_table' => [
                    ['item_id' => $crystal->id, 'chance' => 100, 'quantity' => 1],
                    ['item_id' => $witchCore->id, 'chance' => 100, 'quantity' => 1],
                    ['item_id' => $seal->id, 'chance' => 25, 'quantity' => 2],
                    ['item_id' => $sphere->id, 'chance' => 15, 'quantity' => 1],
                ],
            ]
        );
    }

    private function seedDungeons(): void
    {
        $dungeonLoc = Location::where('name', 'Заброшенная Шахта')->first();

        $dungeon = Dungeon::updateOrCreate(
            ['catalog_key' => 'mine_t1'],
            [
                'name' => 'Заброшенная Шахта',
                'tier' => 1,
                'set_key' => 'mine_fury',
                'item_level' => 7,
                'min_power' => 30,
                'location_id' => $dungeonLoc->id,
                'description' => '10 этажей, сет «Шахтёрский гнев», босс на последнем этаже.',
            ]
        );

        $digger = Monster::updateOrCreate(
            ['name' => 'Заброшенный копатель'],
            [
                'flavor_text' => 'Из тьмы поднимается копатель — последний страж этой галереи.',
                'hp' => 55, 'attack' => 5, 'defense' => 2, 'tier' => 'normal', 'energy_cost' => 1,
                'xp_reward' => 28, 'money_reward' => 18, 'location_id' => $dungeonLoc->id,
                'loot_table' => [],
            ]
        );

        $guard = Monster::updateOrCreate(
            ['name' => 'Ржавый страж'],
            [
                'flavor_text' => 'Ржавый страж преграждает путь. Шестерни скрипят в полумраке.',
                'hp' => 70, 'attack' => 6, 'defense' => 3, 'tier' => 'normal', 'energy_cost' => 1,
                'xp_reward' => 32, 'money_reward' => 22, 'location_id' => $dungeonLoc->id,
                'loot_table' => [],
            ]
        );

        $rare = Monster::updateOrCreate(
            ['name' => 'Вожак забойщиков'],
            [
                'flavor_text' => 'Вожак забойщиков выходит из бокового штрека. Прохода нет без боя.',
                'hp' => 120, 'attack' => 9, 'defense' => 4, 'tier' => 'rare', 'energy_cost' => 1,
                'xp_reward' => 90, 'money_reward' => 45, 'location_id' => $dungeonLoc->id,
                'loot_table' => [],
            ]
        );

        $boss = Monster::updateOrCreate(
            ['name' => 'Король глубин'],
            [
                'flavor_text' => 'В глубине грохочет тишина. Король глубин поднимается с трона из руды.',
                'hp' => 220, 'attack' => 12, 'defense' => 5, 'tier' => 'boss', 'energy_cost' => 1,
                'xp_reward' => 200, 'money_reward' => 100, 'location_id' => $dungeonLoc->id,
                'loot_table' => [],
            ]
        );

        foreach ([
            [$digger, DungeonMonsterRole::Normal],
            [$guard, DungeonMonsterRole::Normal],
            [$rare, DungeonMonsterRole::Rare],
            [$boss, DungeonMonsterRole::Boss],
        ] as [$monster, $role]) {
            DungeonMonster::updateOrCreate(
                ['dungeon_id' => $dungeon->id, 'monster_id' => $monster->id],
                ['role' => $role]
            );
        }

        $defaults = app(\App\Services\DungeonConfigService::class)->tierOneDefaults();
        app(\App\Services\DungeonConfigService::class)->syncDungeon(
            $dungeon,
            $defaults['settings'],
            $defaults['loot_rules'],
            $defaults['resource_pools'],
        );
    }

    private function seedMerchants(): void
    {
        $city = Location::where('name', 'Староград')->first();
        $village = Location::where('name', 'Зелёная Деревня')->first();

        $offers = [
            ['location_id' => $city->id, 'poi_type' => 'material_merchant', 'catalog_key' => 'iron_ore', 'buy_price' => 25],
            ['location_id' => $city->id, 'poi_type' => 'material_merchant', 'catalog_key' => 'wood', 'buy_price' => 18],
            ['location_id' => $city->id, 'poi_type' => 'material_merchant', 'catalog_key' => 'leather', 'buy_price' => 15],
            ['location_id' => $city->id, 'poi_type' => 'armorer', 'catalog_key' => 'V-W01'],
            ['location_id' => $city->id, 'poi_type' => 'armorer', 'catalog_key' => 'V-A01'],
            ['location_id' => $city->id, 'poi_type' => 'armorer', 'catalog_key' => 'V-P01'],
            ['location_id' => $city->id, 'poi_type' => 'armorer', 'catalog_key' => 'V-G01'],
            ['location_id' => $city->id, 'poi_type' => 'armorer', 'catalog_key' => 'V-B01'],
            ['location_id' => $city->id, 'poi_type' => 'guild_master', 'catalog_key' => 'teleport_stone', 'buy_price' => 250],
            ['location_id' => $city->id, 'poi_type' => 'guild_master', 'catalog_key' => 'healing_potion', 'buy_price' => 250],
            ['location_id' => $city->id, 'poi_type' => 'guild_master', 'catalog_key' => 'dungeon_pass_t1', 'buy_price' => 500],
            ['location_id' => $village->id, 'poi_type' => 'armorer', 'catalog_key' => 'V-W02'],
            ['location_id' => $village->id, 'poi_type' => 'armorer', 'catalog_key' => 'V-A02'],
            ['location_id' => $village->id, 'poi_type' => 'armorer', 'catalog_key' => 'V-P02'],
            ['location_id' => $village->id, 'poi_type' => 'armorer', 'catalog_key' => 'V-H01'],
            ['location_id' => $village->id, 'poi_type' => 'armorer', 'catalog_key' => 'V-C01'],
            ['location_id' => $village->id, 'poi_type' => 'alchemist', 'catalog_key' => 'healing_potion', 'stock' => 2],
            ['location_id' => $village->id, 'poi_type' => 'alchemist', 'catalog_key' => 'recipe_scroll_tempered_will_ring', 'buy_price' => 300],
        ];

        foreach ($offers as $offer) {
            $item = Item::where('catalog_key', $offer['catalog_key'])->first();
            MerchantOffer::updateOrCreate(
                [
                    'location_id' => $offer['location_id'],
                    'poi_type' => $offer['poi_type'],
                    'item_id' => $item->id,
                ],
                [
                    'buy_price' => $offer['buy_price'] ?? $item->buy_price ?: 0,
                    'stock' => $offer['stock'] ?? null,
                ]
            );
        }

        MerchantOffer::where('poi_type', 'inn')
            ->whereHas('item', fn ($q) => $q->where('catalog_key', 'teleport_stone'))
            ->delete();
    }

    private function seedDefaultAchievements(): void
    {
        $defaults = [
            ['title' => 'Лёгкое дело', 'difficulty' => 1],
            ['title' => 'Непростое дело', 'difficulty' => 2],
            ['title' => 'Среднее дело', 'difficulty' => 3],
            ['title' => 'Сложное дело', 'difficulty' => 4],
        ];

        foreach ($defaults as $d) {
            AchievementTemplate::updateOrCreate(
                [
                    'is_default' => true,
                    'title' => $d['title'],
                ],
                [
                    'type' => AchievementType::Routine,
                    'reward_points' => $d['difficulty'],
                    'frequency' => AchievementFrequency::None,
                    'difficulty' => $d['difficulty'],
                ]
            );
        }
    }

    private function seedSkills(): void
    {
        $skills = [
            [
                'catalog_key' => 'stealth_strike',
                'name' => 'Атака исподтишка',
                'description' => 'Один раз за бой: первая атака наносит ×2 урона.',
                'min_learn_level' => 1,
                'teach_price' => 300,
            ],
            [
                'catalog_key' => 'power_strike',
                'name' => 'Мощный удар',
                'description' => 'Каждая третья атака наносит ×2 урона.',
                'min_learn_level' => 1,
                'teach_price' => 300,
            ],
            [
                'catalog_key' => 'retribution',
                'name' => 'Возмездие',
                'description' => 'Каждая пятая атака противника возвращает ему ×2 урона за ход.',
                'min_learn_level' => 1,
                'teach_price' => 350,
            ],
            [
                'catalog_key' => 'cleaving_strike',
                'name' => 'Разрубающий удар',
                'description' => 'Каждая четвёртая атака наносит ×2 урона и игнорирует броню.',
                'min_learn_level' => 5,
                'teach_price' => 600,
            ],
            [
                'catalog_key' => 'stunning_strike',
                'name' => 'Оглушающий удар',
                'description' => 'Каждая третья атака оглушает противника — он пропускает ход.',
                'min_learn_level' => 5,
                'teach_price' => 650,
            ],
            [
                'catalog_key' => 'vampire_strike',
                'name' => 'Удар вампира',
                'description' => 'Каждая третья атака восстанавливает 50% нанесённого урона.',
                'min_learn_level' => 10,
                'teach_price' => 1200,
            ],
        ];

        foreach ($skills as $skill) {
            Skill::updateOrCreate(
                ['catalog_key' => $skill['catalog_key']],
                [
                    'name' => $skill['name'],
                    'type' => 'combat',
                    'power' => 0,
                    'priority' => 0,
                    'description' => $skill['description'],
                    'min_learn_level' => $skill['min_learn_level'],
                    'teach_price' => $skill['teach_price'],
                ]
            );
        }

        Skill::whereNull('catalog_key')->each(function (Skill $skill) {
            $skill->characterSkills()->delete();
            $skill->delete();
        });
    }

    private function seedCraftingRecipes(): void
    {
        $ironOre = Item::where('catalog_key', 'iron_ore')->first();
        $wood = Item::where('catalog_key', 'wood')->first();
        $leather = Item::where('catalog_key', 'leather')->first();

        $recipes = [
            ['name' => 'Железный меч (крафт)', 'catalog_key' => 'C-W01', 'energy_cost' => 2, 'ingredients' => [
                ['item' => $ironOre, 'quantity' => 3],
                ['item' => $wood, 'quantity' => 1],
            ]],
            ['name' => 'Кожаный доспех (крафт)', 'catalog_key' => 'C-A01', 'energy_cost' => 3, 'ingredients' => [
                ['item' => $leather, 'quantity' => 5],
                ['item' => $ironOre, 'quantity' => 2],
            ]],
            ['name' => 'Усиленные поножи', 'catalog_key' => 'C-P01', 'energy_cost' => 3, 'ingredients' => [
                ['item' => $leather, 'quantity' => 4],
                ['item' => $ironOre, 'quantity' => 1],
            ]],
            ['name' => 'Кожаные перчатки (крафт)', 'catalog_key' => 'C-G01', 'energy_cost' => 2, 'ingredients' => [
                ['item' => $leather, 'quantity' => 3],
                ['item' => $ironOre, 'quantity' => 1],
            ]],
            ['name' => 'Кожаный шлем (крафт)', 'catalog_key' => 'C-H01', 'energy_cost' => 2, 'ingredients' => [
                ['item' => $leather, 'quantity' => 3],
                ['item' => $ironOre, 'quantity' => 2],
            ]],
            ['name' => 'Укреплённые сапоги', 'catalog_key' => 'C-BO01', 'energy_cost' => 2, 'ingredients' => [
                ['item' => $leather, 'quantity' => 4],
                ['item' => $wood, 'quantity' => 1],
            ]],
            ['name' => 'Кожаный пояс (крафт)', 'catalog_key' => 'C-BE01', 'energy_cost' => 2, 'ingredients' => [
                ['item' => $leather, 'quantity' => 3],
                ['item' => $ironOre, 'quantity' => 1],
            ]],
        ];

        foreach ($recipes as $recipe) {
            $resultItem = Item::where('catalog_key', $recipe['catalog_key'])->first();

            CraftingRecipe::updateOrCreate(
                ['name' => $recipe['name']],
                [
                    'result_item_id' => $resultItem->id,
                    'result_quantity' => 1,
                    'energy_cost' => $recipe['energy_cost'],
                    'required_profession' => 'blacksmith',
                    'category' => 'basic',
                    'recipe_scroll_item_id' => null,
                    'fixed_result_quality' => null,
                    'upgradable' => true,
                    'ingredients' => collect($recipe['ingredients'])->map(fn ($ing) => [
                        'item_id' => $ing['item']->id,
                        'quantity' => $ing['quantity'],
                    ])->all(),
                ]
            );
        }

        $obsidian = Item::where('catalog_key', 'obsidian_shard')->first();
        $runeDust = Item::where('catalog_key', 'rune_dust')->first();
        $scroll = Item::where('catalog_key', 'recipe_scroll_tempered_will_ring')->first();
        $ring = Item::where('catalog_key', 'C-R01')->first();

        CraftingRecipe::updateOrCreate(
            ['name' => 'Кольцо закалённой воли'],
            [
                'result_item_id' => $ring->id,
                'result_quantity' => 1,
                'energy_cost' => 3,
                'required_profession' => 'blacksmith',
                'category' => 'rare',
                'recipe_scroll_item_id' => $scroll->id,
                'fixed_result_quality' => 'blue',
                'upgradable' => false,
                'ingredients' => [
                    ['item_id' => $obsidian->id, 'quantity' => 1],
                    ['item_id' => $runeDust->id, 'quantity' => 1],
                    ['item_id' => $ironOre->id, 'quantity' => 2],
                ],
            ]
        );
    }
}
