<?php

use App\Models\User;

test('profile setup screen can be rendered', function () {
    $user = User::factory()->withoutProfile()->create();

    $response = $this->actingAs($user)->get('/profile/setup');

    $response->assertOk();
});

test('unverified users can access profile setup', function () {
    $user = User::factory()->unverified()->withoutProfile()->create();

    $response = $this->actingAs($user)->get('/profile/setup');

    $response->assertOk();
});

test('users can complete profile setup', function () {
    $this->seed(\Database\Seeders\GameDataSeeder::class);

    $user = User::factory()->withoutProfile()->create();

    $response = $this->actingAs($user)->post('/profile/setup', [
        'nickname' => 'hero123',
        'status' => 'Готов к приключениям',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));

    $user->refresh();

    expect($user->nickname)->toBe('hero123');
    expect($user->status)->toBe('Готов к приключениям');
    expect($user->character)->not->toBeNull();
});

test('nickname must be unique', function () {
    User::factory()->create(['nickname' => 'taken_nick']);

    $user = User::factory()->withoutProfile()->create([
        'email' => 'another@example.com',
    ]);

    $response = $this->actingAs($user)->post('/profile/setup', [
        'nickname' => 'taken_nick',
        'status' => 'Статус',
    ]);

    $response->assertSessionHasErrors('nickname');
});

test('users without completed profile are redirected to setup', function () {
    $user = User::factory()->withoutProfile()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('profile.setup', absolute: false));
});
