<?php

use App\Http\Controllers\Game\AchievementController;
use App\Http\Controllers\Game\CharacterController;
use App\Http\Controllers\Game\CombatController;
use App\Http\Controllers\Game\CraftingController;
use App\Http\Controllers\Game\DungeonController;
use App\Http\Controllers\Game\ExplorationController;
use App\Http\Controllers\Game\HubController;
use App\Http\Controllers\Game\ShopController;
use App\Http\Controllers\Game\WorldController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth'])->group(function () {
    Route::get('profile/setup', [\App\Http\Controllers\Auth\ProfileSetupController::class, 'create'])
        ->name('profile.setup');

    Route::post('profile/setup', [\App\Http\Controllers\Auth\ProfileSetupController::class, 'store'])
        ->name('profile.setup.store');
});

Route::middleware(['auth', 'character'])->group(function () {
    Route::get('/dashboard', HubController::class)->name('dashboard');

    Route::prefix('achievements')->name('achievements.')->group(function () {
        Route::get('/', [AchievementController::class, 'index'])->name('index');
        Route::get('/create', [AchievementController::class, 'create'])->name('create');
        Route::post('/', [AchievementController::class, 'store'])->name('store');
        Route::post('/financial', [AchievementController::class, 'completeFinancial'])->name('financial');
        Route::post('/{template}/complete', [AchievementController::class, 'complete'])->name('complete');
    });

    Route::prefix('character')->name('character.')->group(function () {
        Route::get('/', [CharacterController::class, 'show'])->name('show');
        Route::post('/equip/{inventoryItem}', [CharacterController::class, 'equip'])->name('equip');
        Route::post('/unequip', [CharacterController::class, 'unequip'])->name('unequip');
        Route::post('/use/{inventoryItem}', [CharacterController::class, 'useItem'])->name('use');
        Route::post('/skills/{characterSkill}/equip', [CharacterController::class, 'equipSkill'])->name('skills.equip');
        Route::post('/skills/{characterSkill}/unequip', [CharacterController::class, 'unequipSkill'])->name('skills.unequip');
    });

    Route::prefix('world')->name('world.')->group(function () {
        Route::get('/map', [WorldController::class, 'map'])->name('map');
        Route::get('/city', [WorldController::class, 'city'])->name('city');
        Route::post('/travel/{location}', [WorldController::class, 'travel'])->name('travel');
        Route::post('/inn', [WorldController::class, 'restAtInn'])->name('inn');
        Route::get('/shop/{poiType}', [ShopController::class, 'show'])->name('shop');
        Route::post('/shop/buy/{offer}', [ShopController::class, 'buy'])->name('shop.buy');
        Route::post('/shop/sell/{inventoryItem}', [ShopController::class, 'sell'])->name('shop.sell');
        Route::get('/skills', [\App\Http\Controllers\Game\SkillTrainerController::class, 'show'])->name('skills');
        Route::post('/skills/learn/{skill}', [\App\Http\Controllers\Game\SkillTrainerController::class, 'learn'])->name('skills.learn');
    });

        Route::prefix('dungeon')->name('dungeon.')->group(function () {
        Route::post('/{dungeon}/start', [DungeonController::class, 'start'])->name('start');
        Route::get('/run/{run}', [DungeonController::class, 'run'])->name('run');
        Route::post('/run/{run}/fight', [DungeonController::class, 'fight'])->name('fight');
        Route::post('/run/{run}/resource', [DungeonController::class, 'claimResource'])->name('resource');
        Route::post('/run/{run}/treasure', [DungeonController::class, 'claimTreasure'])->name('treasure');
        Route::post('/run/{run}/advance', [DungeonController::class, 'advance'])->name('advance');
        Route::post('/run/{run}/potion/{inventoryItem}', [DungeonController::class, 'usePotion'])->name('potion');
    });

    Route::prefix('exploration')->name('exploration.')->group(function () {
        Route::get('/', [ExplorationController::class, 'show'])->name('show');
        Route::post('/look', [ExplorationController::class, 'lookAround'])->name('look');
        Route::post('/action/{action}', [ExplorationController::class, 'resolveAction'])->name('resolve');
    });

    Route::prefix('combat')->name('combat.')->group(function () {
        Route::get('/{combat}', [CombatController::class, 'show'])->name('show');
        Route::post('/{combat}/attack', [CombatController::class, 'attack'])->name('attack');
    });

    Route::prefix('crafting')->name('crafting.')->group(function () {
        Route::get('/', [CraftingController::class, 'index'])->name('index');
        Route::post('/{recipe}', [CraftingController::class, 'craft'])->name('craft');
        Route::post('/profession/learn', [CraftingController::class, 'learnProfession'])->name('profession');
        Route::post('/rank/upgrade', [CraftingController::class, 'upgradeRank'])->name('rank');
        Route::post('/upgrade/{inventoryItem}', [CraftingController::class, 'upgradeEquipment'])->name('upgrade');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
