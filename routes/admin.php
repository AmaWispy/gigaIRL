<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ResourceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/config', [DashboardController::class, 'config'])->name('config');

    Route::prefix('resources/{resource}')->name('resources.')->group(function () {
        Route::get('/', [ResourceController::class, 'index'])->name('index');
        Route::get('/create', [ResourceController::class, 'create'])->name('create');
        Route::post('/', [ResourceController::class, 'store'])->name('store');
        Route::get('/{record}', [ResourceController::class, 'show'])->name('show');
        Route::get('/{record}/edit', [ResourceController::class, 'edit'])->name('edit');
        Route::put('/{record}', [ResourceController::class, 'update'])->name('update');
        Route::delete('/{record}', [ResourceController::class, 'destroy'])->name('destroy');
    });
});
