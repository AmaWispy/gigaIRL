<?php

use App\Models\User;
use Database\Seeders\GameDataSeeder;

beforeEach(function () {
    $this->seed(GameDataSeeder::class);
});

test('non-admin cannot access admin panel', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admin can access dashboard and resource list', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Dashboard'));

    $this->actingAs($user)
        ->get(route('admin.resources.index', 'items'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Resources/Index')
            ->has('records.data'));
});

test('admin can view monster loot table on show page', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $monster = \App\Models\Monster::query()->first();

    $this->actingAs($user)
        ->get(route('admin.resources.show', ['monsters', $monster->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Resources/Show')
            ->has('record.loot_table')
            ->where('record.id', $monster->id));
});

test('admin can edit monster loot table', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $monster = \App\Models\Monster::query()->first();
    $itemId = \App\Models\Item::query()->first()->id;

    $this->actingAs($user)
        ->put(route('admin.resources.update', ['monsters', $monster->id]), [
            'name' => $monster->name,
            'location_id' => $monster->location_id,
            'tier' => $monster->tier,
            'flavor_text' => $monster->flavor_text,
            'hp' => $monster->hp,
            'attack' => $monster->attack,
            'defense' => $monster->defense,
            'energy_cost' => $monster->energy_cost,
            'xp_reward' => $monster->xp_reward,
            'money_reward' => $monster->money_reward,
            'loot_table' => [
                ['item_id' => $itemId, 'chance' => 50, 'quantity' => 1],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($monster->fresh()->loot_table)->toHaveCount(1);
});

test('admin config page shows dungeon config from database', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get(route('admin.config'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Config')
            ->has('dungeons', 1)
            ->where('dungeons.0.name', 'Заброшенная Шахта')
            ->has('dungeons.0.loot_rules')
            ->has('dungeons.0.resource_pools.common'));
});

test('admin can view dungeon config on show page', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $dungeon = \App\Models\Dungeon::query()->first();

    $this->actingAs($user)
        ->get(route('admin.resources.show', ['dungeons', $dungeon->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Resources/Show')
            ->has('dungeonConfig.loot_rules')
            ->where('dungeonConfig.id', $dungeon->id));
});
