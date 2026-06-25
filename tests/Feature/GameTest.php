<?php

use App\Models\AchievementTemplate;
use App\Models\Character;
use App\Models\User;
use Database\Seeders\GameDataSeeder;

beforeEach(function () {
    $this->seed(GameDataSeeder::class);
});

function createUserWithCharacter(array $userAttrs = []): User
{
    $user = User::factory()->create($userAttrs);

    $user->character()->create([
        'current_location_id' => \App\Models\Location::where('type', 'city')->first()->id,
        'hp' => 30,
        'max_hp' => 30,
        'energy' => 10,
        'money' => 50,
        'xp' => 0,
        'level' => 1,
        'strength' => 1,
        'defense' => 1,
        'power' => 9,
        'profession' => 'none',
        'last_hp_reset_at' => now(),
    ]);

    return $user->fresh('character');
}

function giveDungeonPass(\App\Models\Character $character, int $quantity = 1): void
{
    $item = \App\Models\Item::where('catalog_key', 'dungeon_pass_t1')->first();
    app(\App\Services\InventoryService::class)->addItem($character, $item, $quantity);
}

function learnSkill(\App\Models\Character $character, string $catalogKey): \App\Models\CharacterSkill
{
    $skill = \App\Models\Skill::where('catalog_key', $catalogKey)->firstOrFail();
    $character->update(['money' => max($character->money, $skill->teach_price + 100)]);

    return app(\App\Services\SkillService::class)->learn($character->fresh(), $skill);
}

test('xp per level scales by 1.2 multiplier', function () {
    $user = createUserWithCharacter();
    $character = $user->character;
    $service = app(\App\Services\CharacterService::class);

    expect($service->xpRequiredForLevel(1))->toBe(100);
    expect($service->xpRequiredForLevel(2))->toBe(120);
    expect($service->xpRequiredForLevel(3))->toBe(144);

    $service->addXp($character, 100);
    $character->refresh();

    expect($character->level)->toBe(2);
    expect($character->xp)->toBe(0);
});

test('profile setup creates character', function () {
    $user = User::factory()->withoutProfile()->create();

    $this->actingAs($user)->post('/profile/setup', [
        'nickname' => 'hero123',
        'status' => 'Готов к приключениям',
    ]);

    $user->refresh();

    expect($user->character)->not->toBeNull();
    expect($user->character->current_location_id)->not->toBeNull();
});

test('game hub can be rendered', function () {
    $user = createUserWithCharacter();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
});

test('financial achievement awards energy', function () {
    $user = createUserWithCharacter();
    $character = $user->character;

    $response = $this->actingAs($user)->post('/achievements/financial', [
        'amount_rubles' => 1000,
        'is_primary_income' => true,
    ]);

    $response->assertRedirect();

    $character->refresh();
    expect($character->energy)->toBe(12);
});

test('default achievement can be completed', function () {
    $user = createUserWithCharacter();
    $template = AchievementTemplate::where('is_default', true)->first();

    $this->actingAs($user)->post(route('achievements.complete', $template));

    $user->character->refresh();
    expect($user->character->energy)->toBe(11);
});

test('default achievement can be completed multiple times per day', function () {
    $user = createUserWithCharacter();
    $template = AchievementTemplate::where('is_default', true)->first();

    $this->actingAs($user)->post(route('achievements.complete', $template));
    $response = $this->actingAs($user)->post(route('achievements.complete', $template));

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $user->character->refresh();
    expect($user->character->energy)->toBe(12);
});

test('user routine with daily frequency cannot be completed twice per day', function () {
    $user = createUserWithCharacter();

    $template = AchievementTemplate::create([
        'user_id' => $user->id,
        'type' => \App\Enums\AchievementType::Routine,
        'title' => 'Утренняя зарядка',
        'reward_points' => 2,
        'frequency' => \App\Enums\AchievementFrequency::Daily,
        'is_default' => false,
    ]);

    $this->actingAs($user)->post(route('achievements.complete', $template));
    $response = $this->actingAs($user)->post(route('achievements.complete', $template));

    $response->assertSessionHasErrors('achievement');

    $user->character->refresh();
    expect($user->character->energy)->toBe(12);
});

test('look around generates 2 to 4 random actions that can repeat', function () {
    $user = createUserWithCharacter();
    $forest = \App\Models\Location::where('type', 'forest')->first();

    $user->character->update([
        'current_location_id' => $forest->id,
        'energy' => 100,
        'power' => 100,
    ]);

    $explorationService = app(\App\Services\ExplorationService::class);
    $session = $explorationService->lookAround($user->character->fresh());

    $rolledActions = $session->actions
        ->reject(fn ($action) => ($action->action_type->value ?? $action->action_type) === \App\Enums\ExplorationActionType::DungeonEntrance->value)
        ->count();

    expect($rolledActions)->toBeGreaterThanOrEqual(config('game.exploration.actions_min'));
    expect($rolledActions)->toBeLessThanOrEqual(config('game.exploration.actions_max'));

    $sawDuplicates = false;

    for ($i = 0; $i < 30; $i++) {
        $user->character->update(['energy' => 100]);
        $explorationService->clearActiveSessions($user->character);
        $rolled = $explorationService->rollExplorationActions($forest, 4);
        $types = array_map(fn ($action) => $action['type']->value, $rolled);

        if (count($types) !== count(array_unique($types))) {
            $sawDuplicates = true;
            break;
        }
    }

    expect($sawDuplicates)->toBeTrue();
});

test('exploration session stays visible after all actions are resolved', function () {
    $user = createUserWithCharacter();
    $forest = \App\Models\Location::where('type', 'forest')->first();

    $user->character->update([
        'current_location_id' => $forest->id,
        'energy' => 100,
        'power' => 100,
    ]);

    $explorationService = app(\App\Services\ExplorationService::class);
    $session = $explorationService->lookAround($user->character->fresh());
    $session->actions->each(fn ($action) => $action->update(['is_resolved' => true]));

    $activeSession = $explorationService->getActiveSession($user->character->fresh());

    expect($activeSession)->not->toBeNull();
    expect($activeSession->actions->every(fn ($action) => $action->is_resolved))->toBeTrue();
});

test('player can sell resources at shop for seventy percent of buy price', function () {
    $user = createUserWithCharacter();
    $item = \App\Models\Item::where('catalog_key', 'iron_ore')->first();
    $inventoryItem = app(\App\Services\InventoryService::class)->addItem($user->character, $item, 3);

    $expectedUnitPrice = (int) floor($item->buy_price * config('game.merchant.sell_ratio'));

    $this->actingAs($user)
        ->post(route('world.shop.sell', $inventoryItem), ['quantity' => 2])
        ->assertRedirect();

    $user->character->refresh();
    $inventoryItem->refresh();

    expect($expectedUnitPrice)->toBe(14);
    expect($user->character->money)->toBe(50 + 28);
    expect($inventoryItem->quantity)->toBe(1);
});

test('crafted green quality gives incremental bonus on standard slots', function () {
    $sword = \App\Models\Item::where('catalog_key', 'C-W01')->first();
    $service = app(\App\Services\EquipmentService::class);

    $white = $service->computeStats($sword, 'white', 7, 'crafted');
    $green = $service->computeStats($sword, 'green', 7, 'crafted');

    expect($green['strength'])->toBe($white['strength'] + max(1, (int) floor($white['strength'] * 0.2)));
    expect($green['strength'])->toBeLessThan($white['strength'] * 2);
});

test('cloak keeps multiplier quality bonus', function () {
    $cloak = \App\Models\Item::where('catalog_key', 'V-C01')->first();
    $service = app(\App\Services\EquipmentService::class);

    $white = $service->computeStats($cloak, 'white', 7, 'vendor');
    $green = $service->computeStats($cloak, 'green', 7, 'vendor');

    expect($green['max_hp'])->toBeGreaterThan($white['max_hp'] * 1.5);
});

test('blacksmith has crafting recipes for all equipment slots', function () {
    $this->seed(\Database\Seeders\GameDataSeeder::class);

    expect(\App\Models\CraftingRecipe::where('category', 'basic')->count())->toBe(7);
    expect(\App\Models\CraftingRecipe::where('category', 'rare')->count())->toBe(4);
    expect(\App\Models\Item::where('equipment_source', 'crafted')->count())->toBe(11);
});

test('blacksmith can craft helmet recipe', function () {
    $user = createUserWithCharacter();
    $user->character->update(['profession' => 'blacksmith', 'energy' => 20]);

    $leather = \App\Models\Item::where('catalog_key', 'leather')->first();
    $ironOre = \App\Models\Item::where('catalog_key', 'iron_ore')->first();
    $recipe = \App\Models\CraftingRecipe::where('name', 'Кожаный шлем (крафт)')->first();

    $inventory = app(\App\Services\InventoryService::class);
    $inventory->addItem($user->character, $leather, 10);
    $inventory->addItem($user->character, $ironOre, 10);

    $this->actingAs($user)
        ->post(route('crafting.craft', $recipe))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($user->character->fresh()->inventoryItems()
        ->whereHas('item', fn ($q) => $q->where('catalog_key', 'C-H01'))
        ->exists())->toBeTrue();
});

test('rare ring recipe requires scroll and crafts blue plus two strength', function () {
    $user = createUserWithCharacter();
    $user->character->update(['profession' => 'blacksmith', 'energy' => 20]);

    $scroll = \App\Models\Item::where('catalog_key', 'recipe_scroll_tempered_will_ring')->first();
    $obsidian = \App\Models\Item::where('catalog_key', 'obsidian_shard')->first();
    $runeDust = \App\Models\Item::where('catalog_key', 'rune_dust')->first();
    $ironOre = \App\Models\Item::where('catalog_key', 'iron_ore')->first();
    $recipe = \App\Models\CraftingRecipe::where('name', 'Кольцо закалённой воли')->first();

    $inventory = app(\App\Services\InventoryService::class);
    $crafting = app(\App\Services\CraftingService::class);

    $grouped = $crafting->getRecipesByCategory($user->character->fresh());
    expect($grouped['rare'])->toBeEmpty();

    $inventory->addItem($user->character, $scroll, 1);
    $inventory->addItem($user->character, $obsidian, 1);
    $inventory->addItem($user->character, $runeDust, 1);
    $inventory->addItem($user->character, $ironOre, 2);

    $grouped = $crafting->getRecipesByCategory($user->character->fresh());
    expect($grouped['rare'])->toHaveCount(1);

    $crafting->craft($user->character->fresh(), $recipe);

    $ring = $user->character->fresh()->inventoryItems()
        ->whereHas('item', fn ($q) => $q->where('catalog_key', 'C-R01'))
        ->first();

    expect($ring)->not->toBeNull();
    expect($ring->quality)->toBe('blue');
    expect(app(\App\Services\EquipmentService::class)->computeStatsForInventoryItem($ring)['strength'])->toBe(2);
    expect($user->character->fresh()->inventoryItems()->where('item_id', $scroll->id)->sum('quantity'))->toBe(0);

    $upgradeOptions = app(\App\Services\EquipmentUpgradeService::class)->getUpgradeOptions($user->character->fresh());
    expect(collect($upgradeOptions)->pluck('inventory_item_id'))->not->toContain($ring->id);
});

test('recipe scroll is sold at village alchemist', function () {
    $village = \App\Models\Location::where('name', 'Зелёная Деревня')->first();
    $scroll = \App\Models\Item::where('catalog_key', 'recipe_scroll_tempered_will_ring')->first();

    $offer = \App\Models\MerchantOffer::where('location_id', $village->id)
        ->where('poi_type', 'alchemist')
        ->where('item_id', $scroll->id)
        ->first();

    expect($offer)->not->toBeNull();
    expect($offer->buy_price)->toBe(300);
});

test('dungeon map panel includes equipment set tooltip data', function () {
    $user = createUserWithCharacter();
    $dungeonLoc = \App\Models\Location::where('type', 'dungeon')->first();

    $user->character->update([
        'current_location_id' => $dungeonLoc->id,
        'power' => 50,
    ]);

    giveDungeonPass($user->character);

    $this->actingAs($user)
        ->get(route('world.map'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('dungeonPanel.set.items', 5)
            ->where('dungeonPanel.set.name', 'Шахтёрский гнев')
            ->where('dungeonPanel.set.items', fn ($items) => collect($items)->pluck('slot')->contains('pants')
                && ! collect($items)->pluck('slot')->contains('necklace'))
        );
});

test('player can equip two rings simultaneously', function () {
    $user = createUserWithCharacter();
    $inventoryService = app(\App\Services\InventoryService::class);

    $craftedRing = \App\Models\Item::where('catalog_key', 'C-R01')->first();
    $depthRing = \App\Models\Item::where('catalog_key', 'D-R01')->first();

    $first = $inventoryService->addEquipment($user->character, $craftedRing, [
        'quality' => 'blue',
        'source' => 'crafted',
        'level' => 7,
    ]);
    $second = $inventoryService->addEquipment($user->character, $depthRing, [
        'quality' => 'green',
        'source' => 'dungeon',
        'level' => 7,
    ]);

    $inventoryService->equip($user->character->fresh(), $first);
    $inventoryService->equip($user->character->fresh(), $second);

    $equipped = $inventoryService->getEquipped($user->character->fresh());

    expect($equipped['ring1'])->not->toBeNull();
    expect($equipped['ring2'])->not->toBeNull();
});

test('depth ring scales strength and hp per quality tier', function () {
    $ring = \App\Models\Item::where('catalog_key', 'D-R01')->first();
    $equipmentService = app(\App\Services\EquipmentService::class);

    $green = \App\Models\InventoryItem::make([
        'quality' => 'green',
        'equipment_level' => 7,
        'equipment_source' => 'dungeon',
    ]);
    $green->setRelation('item', $ring);

    expect($equipmentService->computeStatsForInventoryItem($green))->toMatchArray([
        'strength' => 2,
        'max_hp' => 6,
        'defense' => 0,
    ]);
});

test('depth ring is not part of mine fury dungeon drop pool', function () {
    $setKeys = \App\Models\Item::where('set_key', 'mine_fury')->pluck('catalog_key');

    expect($setKeys)->toContain('D-P01');
    expect($setKeys)->not->toContain('D-R01');
});

test('dungeon gameplay settings are stored in database', function () {
    $dungeon = \App\Models\Dungeon::where('catalog_key', 'mine_t1')->firstOrFail();

    expect($dungeon->entry_energy)->toBe(10);
    expect($dungeon->floors_total)->toBe(10);
    expect($dungeon->lootChance('boss', 'set_equipment'))->toBe(60);
    expect($dungeon->lootChance('boss', 'set_equipment_extra'))->toBe(40);
    expect($dungeon->resourceItemsForPool('rare')->pluck('catalog_key')->all())
        ->toEqual(['obsidian_shard', 'rune_dust']);
    expect($dungeon->resourceItemsForPool('common')->pluck('catalog_key')->all())
        ->toEqual(['iron_ore', 'leather', 'wood']);
});

test('player can sell unequipped vendor equipment at shop', function () {
    $user = createUserWithCharacter();
    $city = \App\Models\Location::where('type', 'city')->first();
    $sword = \App\Models\Item::where('catalog_key', 'V-W01')->first();

    $user->character->update(['current_location_id' => $city->id]);

    $inventoryItem = app(\App\Services\InventoryService::class)->addEquipment($user->character, $sword, [
        'quality' => 'white',
        'source' => 'vendor',
        'level' => 5,
    ]);

    $sellPrice = app(\App\Services\InventoryService::class)->getInventoryItemSellPrice($inventoryItem);

    expect(app(\App\Services\InventoryService::class)->canSellInventoryItem($inventoryItem))->toBeTrue();

    $this->actingAs($user)
        ->post(route('world.shop.sell', $inventoryItem))
        ->assertRedirect();

    expect($user->character->fresh()->money)->toBe(50 + $sellPrice);
    expect(\App\Models\InventoryItem::find($inventoryItem->id))->toBeNull();
});

test('dungeon equipment cannot be sold', function () {
    $user = createUserWithCharacter();
    $item = \App\Models\Item::where('catalog_key', 'D-W01')->first();

    $inventoryItem = app(\App\Services\InventoryService::class)->addEquipment($user->character, $item, [
        'quality' => 'white',
        'source' => 'dungeon',
        'level' => 7,
    ]);

    expect(app(\App\Services\InventoryService::class)->canSellInventoryItem($inventoryItem))->toBeFalse();
});

test('equipped weapon increases combat damage', function () {
    $user = createUserWithCharacter();
    $sword = \App\Models\Item::where('catalog_key', 'V-W01')->first();
    $boar = \App\Models\Monster::where('name', 'Дикий Кабан')->first();

    $inventoryItem = app(\App\Services\InventoryService::class)->addEquipment($user->character, $sword, [
        'quality' => 'green',
        'source' => 'vendor',
        'level' => 5,
    ]);
    app(\App\Services\InventoryService::class)->equip($user->character->fresh(), $inventoryItem);

    $combat = app(\App\Services\CombatService::class)->startCombat($user->character->fresh(), $boar);
    $firstAttack = $combat->rounds->firstWhere('actor', 'character');

    expect($firstAttack->damage)->toBe(4);
});

test('equipped armor increases effective max hp', function () {
    $user = createUserWithCharacter();
    $armor = \App\Models\Item::where('catalog_key', 'V-A01')->first();

    $characterService = app(\App\Services\CharacterService::class);

    expect($characterService->getEffectiveMaxHp($user->character))->toBe(30);

    $inventoryItem = app(\App\Services\InventoryService::class)->addEquipment($user->character, $armor, [
        'quality' => 'white',
        'source' => 'vendor',
        'level' => 5,
    ]);
    app(\App\Services\InventoryService::class)->equip($user->character->fresh(), $inventoryItem);

    $user->character->refresh();

    expect($characterService->getEffectiveMaxHp($user->character))->toBe(33);
    expect($user->character->hp)->toBe(33);
});

test('equipping armor does not heal damaged character', function () {
    $user = createUserWithCharacter();
    $user->character->update(['hp' => 20]);
    $armor = \App\Models\Item::where('catalog_key', 'V-A01')->first();

    $inventoryService = app(\App\Services\InventoryService::class);
    $characterService = app(\App\Services\CharacterService::class);

    $inventoryItem = $inventoryService->addEquipment($user->character, $armor, [
        'quality' => 'white',
        'source' => 'vendor',
        'level' => 5,
    ]);

    $inventoryService->equip($user->character->fresh(), $inventoryItem);
    $user->character->refresh();

    expect($characterService->getEffectiveMaxHp($user->character))->toBe(33);
    expect($user->character->hp)->toBe(20);

    $inventoryService->unequip($user->character->fresh(), 'armor');
    $inventoryService->equip($user->character->fresh(), $inventoryItem->fresh());
    $user->character->refresh();

    expect($characterService->getEffectiveMaxHp($user->character))->toBe(33);
    expect($user->character->hp)->toBe(20);
});

test('travel spends energy', function () {
    $user = createUserWithCharacter();
    $forest = \App\Models\Location::where('type', 'forest')->first();

    $this->actingAs($user)->post(route('world.travel', $forest));

    $user->character->refresh();
    expect($user->character->current_location_id)->toBe($forest->id);
    expect($user->character->energy)->toBe(8);
});

test('travel clears exploration session from previous location', function () {
    $user = createUserWithCharacter();
    $field = \App\Models\Location::where('type', 'field')->first();
    $city = \App\Models\Location::where('type', 'city')->first();

    $user->character->update(['current_location_id' => $field->id, 'energy' => 20]);

    $explorationService = app(\App\Services\ExplorationService::class);
    $explorationService->lookAround($user->character->fresh());

    expect($explorationService->getActiveSession($user->character->fresh()))->not->toBeNull();

    $this->actingAs($user)->post(route('world.travel', $city))->assertRedirect();

    $user->character->refresh();
    expect($user->character->current_location_id)->toBe($city->id);
    expect($explorationService->getActiveSession($user->character))->toBeNull();
    expect(\App\Models\ExplorationSession::where('is_active', true)->count())->toBe(0);
});

test('exploration session is not shown in a different location', function () {
    $user = createUserWithCharacter();
    $field = \App\Models\Location::where('type', 'field')->first();
    $forest = \App\Models\Location::where('type', 'forest')->first();

    $user->character->update(['current_location_id' => $field->id, 'energy' => 20]);

    app(\App\Services\ExplorationService::class)->lookAround($user->character->fresh());

    \App\Models\ExplorationSession::where('character_id', $user->character->id)
        ->update(['is_active' => true]);

    $user->character->update(['current_location_id' => $forest->id]);

    expect(app(\App\Services\ExplorationService::class)->getActiveSession($user->character->fresh()))->toBeNull();
});

test('daily hp reset command works', function () {
    $user = createUserWithCharacter();
    $user->character->update(['hp' => 10]);

    $this->artisan('game:reset-daily-hp')->assertSuccessful();

    expect($user->character->fresh()->hp)->toBe(30);
});

test('guild master sells dungeon pass teleport stone and potion', function () {
    $user = createUserWithCharacter();
    $city = \App\Models\Location::where('type', 'city')->first();

    $user->character->update([
        'current_location_id' => $city->id,
        'money' => 2000,
    ]);

    $response = $this->actingAs($user)->get(route('world.shop', 'guild_master'));

    $response->assertOk();

    $passOffer = \App\Models\MerchantOffer::where('poi_type', 'guild_master')
        ->whereHas('item', fn ($q) => $q->where('catalog_key', 'dungeon_pass_t1'))
        ->first();

    expect($passOffer)->not->toBeNull();

    $this->actingAs($user)->post(route('world.shop.buy', $passOffer))->assertRedirect();

    $passItem = \App\Models\Item::where('catalog_key', 'dungeon_pass_t1')->first();
    expect($user->character->fresh()->inventoryItems()->where('item_id', $passItem->id)->value('quantity'))->toBe(1);
    expect($user->character->fresh()->money)->toBe(1500);

    $this->actingAs($user)->post(route('world.shop.buy', $passOffer))->assertRedirect();
    expect($user->character->fresh()->inventoryItems()->where('item_id', $passItem->id)->value('quantity'))->toBe(2);
});

test('dungeon pass is consumed when entering dungeon', function () {
    $user = createUserWithCharacter();
    $dungeon = \App\Models\Dungeon::first();
    $dungeonLoc = \App\Models\Location::where('type', 'dungeon')->first();

    $user->character->update([
        'current_location_id' => $dungeonLoc->id,
        'energy' => 50,
        'power' => 50,
    ]);

    giveDungeonPass($user->character, 2);

    $this->actingAs($user)->post(route('dungeon.start', $dungeon))->assertRedirect();

    expect(app(\App\Services\DungeonService::class)->countPassItems($user->character->fresh(), $dungeon))->toBe(1);
});

test('dungeon pass can be purchased and starts a run', function () {
    $user = createUserWithCharacter();
    $dungeon = \App\Models\Dungeon::first();
    $dungeonLoc = \App\Models\Location::where('type', 'dungeon')->first();

    $user->character->update([
        'current_location_id' => $dungeonLoc->id,
        'energy' => 50,
        'power' => 50,
        'strength' => 25,
        'hp' => 100,
        'max_hp' => 100,
    ]);

    giveDungeonPass($user->character);

    $this->actingAs($user)->post(route('dungeon.start', $dungeon))->assertRedirect();

    $run = \App\Models\DungeonRun::where('character_id', $user->character->id)->first();
    expect($run)->not->toBeNull();
    expect($run->current_floor)->toBe(1);
    expect($run->floorStates)->toHaveCount(10);
    expect($user->character->fresh()->energy)->toBe(40);
    expect(app(\App\Services\DungeonService::class)->countPassItems($user->character->fresh(), $dungeon))->toBe(0);
});

test('dungeon cannot start away from entrance on map', function () {
    $user = createUserWithCharacter();
    $dungeon = \App\Models\Dungeon::first();

    $user->character->update(['energy' => 50, 'power' => 50]);

    giveDungeonPass($user->character);

    $response = $this->actingAs($user)->post(route('dungeon.start', $dungeon));

    $response->assertRedirect();
    $response->assertSessionHasErrors('dungeon');
});

test('traveling away from dungeon abandons active run', function () {
    $user = createUserWithCharacter();
    $dungeon = \App\Models\Dungeon::first();
    $dungeonLoc = \App\Models\Location::where('type', 'dungeon')->first();
    $forest = \App\Models\Location::where('type', 'forest')->first();

    $user->character->update([
        'current_location_id' => $dungeonLoc->id,
        'money' => 600,
        'energy' => 50,
        'power' => 50,
    ]);

    giveDungeonPass($user->character);
    $this->actingAs($user)->post(route('dungeon.start', $dungeon));

    $run = \App\Models\DungeonRun::where('character_id', $user->character->id)->first();
    expect($run->is_active)->toBeTrue();

    $response = $this->actingAs($user)->post(route('world.travel', $forest));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $run->refresh();
    expect($run->is_active)->toBeFalse();
    expect($run->failed)->toBeTrue();
    expect($user->character->fresh()->current_location_id)->toBe($forest->id);
});

test('dungeon floor advance requires mob defeat', function () {
    $user = createUserWithCharacter();
    $dungeon = \App\Models\Dungeon::first();
    $dungeonLoc = \App\Models\Location::where('type', 'dungeon')->first();

    $user->character->update([
        'current_location_id' => $dungeonLoc->id,
        'money' => 600,
        'energy' => 50,
        'power' => 50,
    ]);

    giveDungeonPass($user->character);
    $this->actingAs($user)->post(route('dungeon.start', $dungeon));

    $run = \App\Models\DungeonRun::where('character_id', $user->character->id)->first();

    $response = $this->actingAs($user)->post(route('dungeon.advance', $run));

    $response->assertRedirect();
    $response->assertSessionHasErrors('dungeon');
});

test('travel to dungeon requires pass in inventory', function () {
    $user = createUserWithCharacter();
    $forest = \App\Models\Location::where('type', 'forest')->first();
    $dungeonLoc = \App\Models\Location::where('type', 'dungeon')->first();

    $user->character->update([
        'current_location_id' => $forest->id,
        'energy' => 20,
    ]);

    $response = $this->actingAs($user)->post(route('world.travel', $dungeonLoc));

    $response->assertRedirect();
    $response->assertSessionHasErrors('travel');
    expect($user->character->fresh()->current_location_id)->toBe($forest->id);

    giveDungeonPass($user->character->fresh());

    $this->actingAs($user)->post(route('world.travel', $dungeonLoc))->assertRedirect();
    expect($user->character->fresh()->current_location_id)->toBe($dungeonLoc->id);
});

test('tier 1 dungeon normal mobs give reduced xp', function () {
    $dungeon = \App\Models\Dungeon::where('catalog_key', 'mine_t1')->first();
    $service = app(\App\Services\DungeonService::class);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('getXpMultiplier');
    $method->setAccessible(true);

    expect($method->invoke($service, $dungeon, 'normal'))->toBe(1.5);
    expect($method->invoke($service, $dungeon, 'rare'))->toBe(3.5);
    expect($method->invoke($service, $dungeon, 'boss'))->toBe(6.0);
});

test('dungeon mob fight resolves combat', function () {
    $user = createUserWithCharacter();
    $dungeon = \App\Models\Dungeon::first();
    $dungeonLoc = \App\Models\Location::where('type', 'dungeon')->first();

    $user->character->update([
        'current_location_id' => $dungeonLoc->id,
        'money' => 600,
        'energy' => 50,
        'power' => 50,
        'strength' => 30,
        'hp' => 150,
        'max_hp' => 150,
    ]);

    giveDungeonPass($user->character);
    $this->actingAs($user)->post(route('dungeon.start', $dungeon));

    $run = \App\Models\DungeonRun::where('character_id', $user->character->id)->first();

    $response = $this->actingAs($user)->post(route('dungeon.fight', $run));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/combat/');
});

test('skill trainer teaches combat skills in city', function () {
    $user = createUserWithCharacter();
    $city = \App\Models\Location::where('type', 'city')->first();

    $user->character->update([
        'current_location_id' => $city->id,
        'money' => 1000,
    ]);

    $skill = \App\Models\Skill::where('catalog_key', 'stealth_strike')->first();

    $this->actingAs($user)->post(route('world.skills.learn', $skill))->assertRedirect();

    expect(app(\App\Services\SkillService::class)->hasLearned($user->character->fresh(), $skill))->toBeTrue();
    expect(\App\Models\Skill::whereNotNull('catalog_key')->count())->toBe(10);
});

test('character can equip one skill before level 10', function () {
    $user = createUserWithCharacter();
    $skillService = app(\App\Services\SkillService::class);

    $first = learnSkill($user->character, 'stealth_strike');
    $second = learnSkill($user->character->fresh(), 'power_strike');

    $skillService->equip($user->character->fresh(), $first);

    expect(fn () => $skillService->equip($user->character->fresh(), $second))
        ->toThrow(\InvalidArgumentException::class);

    expect($user->character->fresh()->characterSkills()->where('is_equipped', true)->count())->toBe(1);
});

test('stealth strike doubles first attack damage', function () {
    $user = createUserWithCharacter();
    $boar = \App\Models\Monster::where('name', 'Дикий Кабан')->first();

    $characterSkill = learnSkill($user->character, 'stealth_strike');
    app(\App\Services\SkillService::class)->equip($user->character->fresh(), $characterSkill);

    $combat = app(\App\Services\CombatService::class)->startCombat($user->character->fresh(), $boar);
    $firstAttack = $combat->rounds->firstWhere('actor', 'character');

    expect($firstAttack->damage)->toBe(2);
});

test('power strike doubles every third attack', function () {
    $user = createUserWithCharacter();
    $boar = \App\Models\Monster::where('name', 'Дикий Кабан')->first();
    $boar->update(['hp' => 500]);

    $characterSkill = learnSkill($user->character, 'power_strike');
    app(\App\Services\SkillService::class)->equip($user->character->fresh(), $characterSkill);

    $combat = app(\App\Services\CombatService::class)->startCombat($user->character->fresh(), $boar);
    $characterAttacks = $combat->rounds->where('actor', 'character')->where('action', 'attack')->values();

    expect($characterAttacks[0]->damage)->toBe(1);
    expect($characterAttacks[2]->damage)->toBe(2);
});

test('retribution reflects damage on fifth monster attack', function () {
    $user = createUserWithCharacter();
    $boar = \App\Models\Monster::where('name', 'Дикий Кабан')->first();
    $boar->update(['hp' => 500, 'attack' => 4]);

    $characterSkill = learnSkill($user->character, 'retribution');
    app(\App\Services\SkillService::class)->equip($user->character->fresh(), $characterSkill);

    $combat = app(\App\Services\CombatService::class)->startCombat($user->character->fresh(), $boar);
    $retribution = $combat->rounds->firstWhere('action', 'retribution');

    expect($retribution)->not->toBeNull();
    expect($retribution->damage)->toBe(6);
});

test('vampire strike adds flat damage and heals forty percent every third attack', function () {
    $user = createUserWithCharacter();
    $boar = \App\Models\Monster::where('name', 'Дикий Кабан')->first();
    $boar->update(['hp' => 500, 'defense' => 0]);

    $user->character->update(['hp' => 200, 'max_hp' => 200, 'level' => 10]);

    $characterSkill = learnSkill($user->character, 'vampire_strike');
    app(\App\Services\SkillService::class)->equip($user->character->fresh(), $characterSkill);

    $combat = app(\App\Services\CombatService::class)->startCombat($user->character->fresh(), $boar);
    $characterAttacks = $combat->rounds->where('actor', 'character')->where('action', 'attack')->values();

    $base = $characterAttacks[0]->damage;
    $thirdDamage = $characterAttacks[2]->damage;

    expect($thirdDamage)->toBe($base + 3);
    expect($characterAttacks[2]->heal)->toBe((int) floor($thirdDamage * 0.4));
    expect($characterAttacks[0]->heal)->toBe(0);
});

test('executioner increases damage below thirty percent enemy hp', function () {
    $user = createUserWithCharacter();
    $boar = \App\Models\Monster::where('name', 'Дикий Кабан')->first();
    $boar->update(['hp' => 100, 'attack' => 0, 'defense' => 0]);

    $user->character->update(['hp' => 200, 'max_hp' => 200, 'level' => 15, 'strength' => 10]);

    $characterSkill = learnSkill($user->character, 'executioner');
    app(\App\Services\SkillService::class)->equip($user->character->fresh(), $characterSkill);

    $combat = app(\App\Services\CombatService::class)->startCombat($user->character->fresh(), $boar);
    $attacks = $combat->rounds->where('actor', 'character')->where('action', 'attack')->values();

    expect($attacks->pluck('damage')->min())->toBe(10);
    expect($attacks->pluck('damage')->max())->toBe(15);
});

test('find the gap shreds armor and breaks it for bonus damage', function () {
    $user = createUserWithCharacter();
    $boar = \App\Models\Monster::where('name', 'Дикий Кабан')->first();
    $boar->update(['hp' => 5000, 'attack' => 0, 'defense' => 4]);

    $user->character->update(['hp' => 500, 'max_hp' => 500, 'level' => 10, 'strength' => 100]);

    $characterSkill = learnSkill($user->character, 'find_the_gap');
    app(\App\Services\SkillService::class)->equip($user->character->fresh(), $characterSkill);

    $combat = app(\App\Services\CombatService::class)->startCombat($user->character->fresh(), $boar);
    $attacks = $combat->rounds->where('actor', 'character')->where('action', 'attack')->values();

    expect($attacks[0]->damage)->toBe(82);
    expect($attacks[1]->damage)->toBe(90);
    expect($attacks[3]->damage)->toBe(125);
});

test('stone skin reduces every third incoming hit', function () {
    $user = createUserWithCharacter();
    $boar = \App\Models\Monster::where('name', 'Дикий Кабан')->first();
    $boar->update(['hp' => 5000, 'attack' => 50, 'defense' => 0]);

    $user->character->update(['hp' => 5000, 'max_hp' => 5000, 'level' => 10, 'strength' => 1, 'defense' => 1]);

    $characterSkill = learnSkill($user->character, 'stone_skin');
    app(\App\Services\SkillService::class)->equip($user->character->fresh(), $characterSkill);

    $combat = app(\App\Services\CombatService::class)->startCombat($user->character->fresh(), $boar);
    $monsterAttacks = $combat->rounds->where('actor', 'monster')->where('action', 'attack')->values();

    expect($monsterAttacks[2]->damage)->toBeLessThan($monsterAttacks[0]->damage);
    expect($monsterAttacks[2]->damage)->toBe(29);
    expect($monsterAttacks[0]->damage)->toBe(47);
});

test('second wind heals once when hp drops low', function () {
    $user = createUserWithCharacter();
    $boar = \App\Models\Monster::where('name', 'Дикий Кабан')->first();
    $boar->update(['hp' => 50, 'attack' => 95, 'defense' => 0]);

    $user->character->update(['hp' => 100, 'max_hp' => 100, 'level' => 15, 'strength' => 1, 'defense' => 0]);

    $characterSkill = learnSkill($user->character, 'second_wind');
    app(\App\Services\SkillService::class)->equip($user->character->fresh(), $characterSkill);

    $combat = app(\App\Services\CombatService::class)->startCombat($user->character->fresh(), $boar);
    $secondWind = $combat->rounds->where('action', 'second_wind')->values();

    expect($secondWind)->toHaveCount(1);
    expect($secondWind[0]->heal)->toBe(20);
});

test('dungeon death returns to city and closes run', function () {
    $user = createUserWithCharacter();
    $dungeon = \App\Models\Dungeon::first();
    $dungeonLoc = \App\Models\Location::where('type', 'dungeon')->first();
    $city = \App\Models\Location::where('type', 'city')->first();

    $user->character->update([
        'current_location_id' => $dungeonLoc->id,
        'money' => 600,
        'energy' => 50,
        'power' => 50,
        'hp' => 1,
        'max_hp' => 30,
        'strength' => 1,
        'defense' => 0,
    ]);

    giveDungeonPass($user->character);
    $this->actingAs($user)->post(route('dungeon.start', $dungeon));

    $run = \App\Models\DungeonRun::where('character_id', $user->character->id)->first();

    $response = $this->actingAs($user)->post(route('dungeon.fight', $run));
    $combatId = (int) str_replace('/combat/', '', parse_url($response->headers->get('Location'), PHP_URL_PATH));

    $this->actingAs($user)->get(route('combat.show', $combatId));

    $run->refresh();
    $user->character->refresh();

    expect($run->is_active)->toBeFalse();
    expect($run->failed)->toBeTrue();
    expect($user->character->current_location_id)->toBe($city->id);
    expect($user->character->hp)->toBe(1);
});

test('combat log records armor blocked damage', function () {
    $user = createUserWithCharacter();
    $sword = \App\Models\Item::where('catalog_key', 'V-W01')->first();
    $boar = \App\Models\Monster::where('name', 'Дикий Кабан')->first();

    $inventoryItem = app(\App\Services\InventoryService::class)->addEquipment($user->character, $sword, [
        'quality' => 'green',
        'source' => 'vendor',
        'level' => 5,
    ]);
    app(\App\Services\InventoryService::class)->equip($user->character->fresh(), $inventoryItem);

    $combat = app(\App\Services\CombatService::class)->startCombat($user->character->fresh(), $boar);
    $characterAttack = $combat->rounds->firstWhere('actor', 'character');
    $monsterAttack = $combat->rounds->firstWhere('actor', 'monster');

    expect($characterAttack->meta['blocked'])->toBeGreaterThan(0);
    expect($monsterAttack->meta['blocked'])->toBeGreaterThan(0);
});

test('defense reduces damage with diminishing returns', function () {
    $service = app(\App\Services\CombatDamageService::class);

    $at10 = $service->resolve(10, 10);
    $at20 = $service->resolve(10, 20);

    expect($at10['damage'])->toBe(7);
    expect($at10['blocked'])->toBe(3);
    expect($at20['damage'])->toBe(6);
    expect($at20['blocked'])->toBe(4);
});

test('heavy armor still takes meaningful damage from dungeon mobs', function () {
    $service = app(\App\Services\CombatDamageService::class);

    $bossHit = $service->resolve(12, 25);
    $normalHit = $service->resolve(6, 12);

    expect($bossHit['damage'])->toBeGreaterThan(1);
    expect($normalHit['damage'])->toBeGreaterThan(1);
    expect($bossHit['damage'])->toBeLessThan(12);
});

test('healing potion restores hp during dungeon run', function () {
    $user = createUserWithCharacter();
    $dungeon = \App\Models\Dungeon::first();
    $dungeonLoc = \App\Models\Location::where('type', 'dungeon')->first();
    $potion = \App\Models\Item::where('catalog_key', 'healing_potion')->first();

    $user->character->update([
        'current_location_id' => $dungeonLoc->id,
        'money' => 600,
        'energy' => 50,
        'power' => 50,
        'hp' => 10,
    ]);

    giveDungeonPass($user->character);
    app(\App\Services\InventoryService::class)->addItem($user->character, $potion, 2);

    $this->actingAs($user)->post(route('dungeon.start', $dungeon));

    $run = \App\Models\DungeonRun::where('character_id', $user->character->id)->first();
    expect($run->character_hp)->toBe(10);

    $inventoryItem = $user->character->inventoryItems()->where('item_id', $potion->id)->first();

    $this->actingAs($user)->post(route('dungeon.potion', [$run->id, $inventoryItem->id]));

    $run->refresh();
    expect($run->character_hp)->toBe(25);
    expect($user->character->fresh()->inventoryItems()->where('item_id', $potion->id)->sum('quantity'))->toBe(1);
});

test('seekers camp is reachable from ancient ruins and sells the tier two pass and recipes', function () {
    $camp = \App\Models\Location::where('name', 'Лагерь Искателей')->first();
    $ruins = \App\Models\Location::where('name', 'Древние Руины')->first();

    expect($camp)->not->toBeNull();
    expect($camp->is_safe)->toBeTrue();

    $link = \App\Models\LocationConnection::where('from_location_id', $ruins->id)
        ->where('to_location_id', $camp->id)
        ->first();
    expect($link)->not->toBeNull();

    $passOffer = \App\Models\MerchantOffer::where('location_id', $camp->id)
        ->where('poi_type', 'recipe_merchant')
        ->whereHas('item', fn ($q) => $q->where('catalog_key', 'dungeon_pass_t2'))
        ->first();
    expect($passOffer)->not->toBeNull();
    expect($passOffer->buy_price)->toBe(800);

    $witchRecipe = \App\Models\MerchantOffer::where('location_id', $camp->id)
        ->where('poi_type', 'recipe_merchant')
        ->whereHas('item', fn ($q) => $q->where('catalog_key', 'recipe_scroll_witch_blade'))
        ->first();
    expect($witchRecipe)->not->toBeNull();
});

test('seal trader exchanges explorer seals for blue cloak and amulet', function () {
    $user = createUserWithCharacter();
    $camp = \App\Models\Location::where('name', 'Лагерь Искателей')->first();
    $user->character->update(['current_location_id' => $camp->id]);

    $seal = \App\Models\Item::where('catalog_key', 'explorer_seal')->first();
    app(\App\Services\InventoryService::class)->addItem($user->character, $seal, 3);

    $cloakOffer = \App\Models\MerchantOffer::where('location_id', $camp->id)
        ->where('poi_type', 'seal_trader')
        ->whereHas('item', fn ($q) => $q->where('catalog_key', 'seal_cloak'))
        ->first();
    $amuletOffer = \App\Models\MerchantOffer::where('location_id', $camp->id)
        ->where('poi_type', 'seal_trader')
        ->whereHas('item', fn ($q) => $q->where('catalog_key', 'seal_amulet'))
        ->first();

    $this->actingAs($user)->post(route('world.shop.buy', $cloakOffer))->assertRedirect();
    $this->actingAs($user)->post(route('world.shop.buy', $amuletOffer))->assertRedirect();

    expect($user->character->fresh()->inventoryItems()->where('item_id', $seal->id)->sum('quantity'))->toBe(0);

    $cloak = \App\Models\Item::where('catalog_key', 'seal_cloak')->first();
    $cloakInv = $user->character->fresh()->inventoryItems()->where('item_id', $cloak->id)->first();
    expect($cloakInv)->not->toBeNull();
    expect($cloakInv->quality)->toBe('blue');
    expect(app(\App\Services\EquipmentService::class)->computeStatsForInventoryItem($cloakInv)['max_hp'])->toBe(15);

    $amulet = \App\Models\Item::where('catalog_key', 'seal_amulet')->first();
    $amuletInv = $user->character->fresh()->inventoryItems()->where('item_id', $amulet->id)->first();
    expect($amuletInv)->not->toBeNull();
    $amuletStats = app(\App\Services\EquipmentService::class)->computeStatsForInventoryItem($amuletInv);
    expect($amuletStats['strength'])->toBe(3);
    expect($amuletStats['max_hp'])->toBe(3);
});

test('seal trader purchase is blocked without enough seals', function () {
    $user = createUserWithCharacter();
    $camp = \App\Models\Location::where('name', 'Лагерь Искателей')->first();
    $user->character->update(['current_location_id' => $camp->id]);

    $amuletOffer = \App\Models\MerchantOffer::where('location_id', $camp->id)
        ->where('poi_type', 'seal_trader')
        ->whereHas('item', fn ($q) => $q->where('catalog_key', 'seal_amulet'))
        ->first();

    $this->actingAs($user)->post(route('world.shop.buy', $amuletOffer))
        ->assertSessionHasErrors('shop');

    $amulet = \App\Models\Item::where('catalog_key', 'seal_amulet')->first();
    expect($user->character->fresh()->inventoryItems()->where('item_id', $amulet->id)->count())->toBe(0);
});

test('craftsman seal upgrades crafted gear straight to level 14 with tier two resources', function () {
    $user = createUserWithCharacter();
    $city = \App\Models\Location::where('type', 'city')->first();
    $user->character->update(['current_location_id' => $city->id, 'energy' => 20]);

    $sword = \App\Models\Item::where('catalog_key', 'C-W01')->first();
    $inventory = app(\App\Services\InventoryService::class);
    $inv = $inventory->addEquipment($user->character, $sword, [
        'quality' => 'green',
        'source' => 'crafted',
        'level' => 7,
    ]);

    $seal = \App\Models\Item::where('catalog_key', 'craftsman_seal')->first();
    $hide = \App\Models\Item::where('catalog_key', 'monster_hide')->first();
    $essence = \App\Models\Item::where('catalog_key', 'twilight_essence')->first();
    $inventory->addItem($user->character, $seal, 1);
    $inventory->addItem($user->character, $hide, 3);
    $inventory->addItem($user->character, $essence, 2);

    $this->actingAs($user)
        ->post(route('crafting.upgrade', $inv->id), ['action' => 'crafted_level'])
        ->assertRedirect();

    expect($inv->fresh()->equipment_level)->toBe(14);
    expect($user->character->fresh()->inventoryItems()->where('item_id', $seal->id)->sum('quantity'))->toBe(0);
    expect($user->character->fresh()->inventoryItems()->where('item_id', $hide->id)->sum('quantity'))->toBe(0);
    expect($user->character->fresh()->inventoryItems()->where('item_id', $essence->id)->sum('quantity'))->toBe(0);
});

test('recipe crafted gear upgrades quality with witch cores', function () {
    $user = createUserWithCharacter();
    $city = \App\Models\Location::where('type', 'city')->first();
    $user->character->update(['current_location_id' => $city->id]);

    $armor = \App\Models\Item::where('catalog_key', 'C-A02')->first();
    $inventory = app(\App\Services\InventoryService::class);
    $inv = $inventory->addEquipment($user->character, $armor, [
        'quality' => 'white',
        'source' => 'crafted',
        'level' => 14,
    ]);

    $core = \App\Models\Item::where('catalog_key', 'witch_core')->first();
    $cost = config('equipment.recipe_quality_upgrade.cost_per_tier.green');
    $inventory->addItem($user->character, $core, $cost);

    $this->actingAs($user)
        ->post(route('crafting.upgrade', $inv->id), ['action' => 'crafted_quality'])
        ->assertRedirect();

    expect($inv->fresh()->quality)->toBe('green');
    expect($inv->fresh()->equipment_level)->toBe(14);
    expect($user->character->fresh()->inventoryItems()->where('item_id', $core->id)->sum('quantity'))->toBe(0);
});

test('recipe crafted gear always crafts white', function () {
    $recipe = \App\Models\CraftingRecipe::where('name', 'Панцирь охотника')->first();

    expect($recipe->fixed_result_quality)->toBe('white');
    expect($recipe->quality_upgradable)->toBeTrue();
});

test('dungeon gear can be upgraded with sphere while still equipped', function () {
    $user = createUserWithCharacter();
    $city = \App\Models\Location::where('type', 'city')->first();
    $user->character->update(['current_location_id' => $city->id]);

    $armor = \App\Models\Item::where('catalog_key', 'D-A01')->first();
    $inventory = app(\App\Services\InventoryService::class);
    $inv = $inventory->addEquipment($user->character, $armor, [
        'quality' => 'blue',
        'source' => 'dungeon',
        'level' => 7,
    ]);
    $inventory->equip($user->character->fresh(), $inv);

    $sphere = \App\Models\Item::where('catalog_key', 'transformation_sphere')->first();
    $sphereCost = config('equipment.dungeon_t2_upgrade.transformation_sphere');
    $inventory->addItem($user->character, $sphere, $sphereCost);

    expect($inv->fresh()->equippedSlot()->exists())->toBeTrue();

    $this->actingAs($user)
        ->post(route('crafting.upgrade', $inv->id), ['action' => 'dungeon_level'])
        ->assertRedirect();

    expect($inv->fresh()->equipment_level)->toBe(14);
    expect($inv->fresh()->equippedSlot()->exists())->toBeTrue();
    expect($user->character->fresh()->inventoryItems()->where('item_id', $sphere->id)->sum('quantity'))->toBe(0);
});

test('leaving the second dungeon delivers you to the seekers camp', function () {
    $user = createUserWithCharacter();
    $dungeon = \App\Models\Dungeon::where('catalog_key', 'spider_t2')->firstOrFail();
    $cave = \App\Models\Location::where('name', 'Пещера Арахнидов')->firstOrFail();
    $camp = \App\Models\Location::where('name', 'Лагерь Искателей')->firstOrFail();

    $user->character->update([
        'current_location_id' => $cave->id,
        'energy' => 80,
        'power' => 200,
        'strength' => 60,
        'hp' => 300,
        'max_hp' => 300,
    ]);

    $pass = \App\Models\Item::where('catalog_key', 'dungeon_pass_t2')->firstOrFail();
    app(\App\Services\InventoryService::class)->addItem($user->character, $pass, 1);

    $service = app(\App\Services\DungeonService::class);
    $run = $service->startRun($user->character->fresh(), $dungeon);

    $run->currentFloorState()->update(['mob_defeated' => true]);

    $service->leaveDungeon($user->character->fresh(), $run->fresh());

    expect($user->character->fresh()->current_location_id)->toBe($camp->id);
});

test('tier two dungeon drops craftsman seals from mobs', function () {
    $dungeon = \App\Models\Dungeon::where('catalog_key', 'spider_t2')->first();

    expect($dungeon)->not->toBeNull();
    expect($dungeon->lootChance('boss', 'craftsman_seal'))->toBeGreaterThan(0);
    expect($dungeon->lootChance('normal', 'craftsman_seal'))->toBeGreaterThan(0);
});
