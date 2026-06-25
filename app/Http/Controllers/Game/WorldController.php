<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Services\CharacterService;
use App\Services\DungeonService;
use App\Services\TravelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorldController extends Controller
{
    public function map(Request $request, TravelService $travelService, CharacterService $characterService, DungeonService $dungeonService, \App\Services\EquipmentService $equipmentService): Response
    {
        $character = $request->user()->character;
        $location = $character->currentLocation;
        $dungeonAtLocation = $dungeonService->getDungeonAtCharacterLocation($character);
        $activeRun = $dungeonService->getActiveRun($character);

        $dungeonPanel = null;

        if ($dungeonAtLocation) {
            $dungeonPanel = [
                'id' => $dungeonAtLocation->id,
                'name' => $dungeonAtLocation->name,
                'description' => $dungeonAtLocation->description,
                'min_power' => $dungeonAtLocation->min_power,
                'pass_count' => $dungeonService->countPassItems($character, $dungeonAtLocation),
                'entrance_available' => session('dungeon_entrance_id') === $dungeonAtLocation->id,
                'can_enter' => $dungeonService->canEnter($character, $dungeonAtLocation, session('dungeon_entrance_id')),
                'active_run_id' => ($activeRun && $activeRun->dungeon_id === $dungeonAtLocation->id) ? $activeRun->id : null,
                'entry_energy' => $dungeonAtLocation->entry_energy,
                'set' => $equipmentService->getSetInfo($dungeonAtLocation->set_key, $dungeonAtLocation->item_level),
            ];
        }

        return Inertia::render('Game/World/Map', [
            'character' => $characterService->toArray($character),
            'destinations' => $travelService->getAvailableDestinations($character),
            'dungeonPanel' => $dungeonPanel,
        ]);
    }

    public function city(Request $request, CharacterService $characterService): Response
    {
        $character = $request->user()->character;
        $location = $character->currentLocation;

        if (! $location || ! $location->is_safe) {
            return redirect()->route('world.map');
        }

        $location->load('pois');

        return Inertia::render('Game/World/City', [
            'character' => $characterService->toArray($character),
            'location' => $location,
            'innCost' => config('game.inn.cost'),
            'innRestorePercent' => config('game.inn.hp_restore_percent'),
        ]);
    }

    public function travel(Request $request, Location $location, TravelService $travelService): RedirectResponse
    {
        $validated = $request->validate([
            'use_teleport_stone' => 'boolean',
        ]);

        try {
            $result = $travelService->travel(
                $request->user()->character,
                $location,
                $validated['use_teleport_stone'] ?? false
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['travel' => $e->getMessage()]);
        }

        $message = 'Вы переместились в '.$location->name;

        if ($result['dungeon_run_abandoned'] ?? false) {
            $message .= ' Прогресс в данже потерян.';
        }

        return back()->with('success', $message);
    }

    public function restAtInn(Request $request, TravelService $travelService): RedirectResponse
    {
        try {
            $travelService->restAtInn($request->user()->character);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['inn' => $e->getMessage()]);
        }

        return back()->with('success', 'Вы отдохнули в гостинице.');
    }
}
