<?php

namespace App\Http\Controllers\Admin;

use App\Admin\AdminResourceRegistry;
use App\Admin\DungeonConfigPresenter;
use App\Http\Controllers\Controller;
use App\Models\Dungeon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private DungeonConfigPresenter $dungeonConfig) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'navigation' => AdminResourceRegistry::navigation(),
        ]);
    }

    public function config(): Response
    {
        $dungeons = Dungeon::query()
            ->orderBy('tier')
            ->orderBy('name')
            ->get()
            ->map(fn (Dungeon $dungeon) => $this->dungeonConfig->present($dungeon))
            ->values();

        return Inertia::render('Admin/Config', [
            'game' => config('game'),
            'equipment' => config('equipment'),
            'dungeons' => $dungeons,
        ]);
    }
}
