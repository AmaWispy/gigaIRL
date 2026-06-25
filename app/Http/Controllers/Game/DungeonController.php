<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Dungeon;
use App\Models\DungeonRun;
use App\Models\InventoryItem;
use App\Services\CharacterService;
use App\Services\DungeonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DungeonController extends Controller
{
    public function start(Request $request, Dungeon $dungeon, DungeonService $dungeonService): RedirectResponse
    {
        $character = $request->user()->character;
        $viaEntrance = session('dungeon_entrance_id') === $dungeon->id;

        try {
            if (! $dungeonService->canEnter($character, $dungeon, session('dungeon_entrance_id'))) {
                throw new \InvalidArgumentException('Нет доступа к этому данжу.');
            }

            $run = $dungeonService->startRun($character, $dungeon, viaEntrance: $viaEntrance);

            if ($viaEntrance) {
                session()->forget('dungeon_entrance_id');
            }
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['dungeon' => $e->getMessage()]);
        }

        return redirect()->route('dungeon.run', $run)
            ->with('success', 'Вы вошли в данж.');
    }

    public function run(Request $request, DungeonRun $run, DungeonService $dungeonService, CharacterService $characterService): Response|RedirectResponse
    {
        $character = $request->user()->character;

        if ($run->character_id !== $character->id) {
            abort(403);
        }

        if ($character->current_location_id !== $run->dungeon->location_id) {
            return redirect()->route('world.map')
                ->withErrors(['dungeon' => 'Забег доступен только пока вы у входа в данж на карте.']);
        }

        if (! $run->is_active) {
            return redirect()->route('world.map')
                ->with('warning', $run->failed ? 'Забег в данже прерван.' : 'Забег завершён.');
        }

        $maxHp = $characterService->getEffectiveMaxHp($character);

        $dungeon = $run->dungeon;

        return Inertia::render('Game/Dungeon/Run', [
            'character' => $characterService->toArray($character),
            'run' => $dungeonService->runToArray($run),
            'effectiveMaxHp' => $maxHp,
            'potions' => $dungeonService->getHealingPotions($character),
            'floorEnergy' => $dungeon->floor_energy,
            'combatEnergy' => $dungeon->combat_energy,
            'resourceEnergy' => $dungeon->resource_energy,
            'treasureEnergy' => $dungeon->treasure_energy,
        ]);
    }

    public function fight(Request $request, DungeonRun $run, DungeonService $dungeonService): RedirectResponse
    {
        if ($run->character_id !== $request->user()->character->id) {
            abort(403);
        }

        try {
            $combat = $dungeonService->fightMob($request->user()->character, $run);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['dungeon' => $e->getMessage()]);
        }

        return redirect()->route('combat.show', $combat);
    }

    public function claimResource(Request $request, DungeonRun $run, DungeonService $dungeonService): RedirectResponse
    {
        if ($run->character_id !== $request->user()->character->id) {
            abort(403);
        }

        try {
            $result = $dungeonService->claimResource($request->user()->character, $run);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['dungeon' => $e->getMessage()]);
        }

        return back()->with('success', $result['message']);
    }

    public function claimTreasure(Request $request, DungeonRun $run, DungeonService $dungeonService): RedirectResponse
    {
        if ($run->character_id !== $request->user()->character->id) {
            abort(403);
        }

        try {
            $result = $dungeonService->claimTreasure($request->user()->character, $run);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['dungeon' => $e->getMessage()]);
        }

        return back()->with('success', $result['message']);
    }

    public function advance(Request $request, DungeonRun $run, DungeonService $dungeonService): RedirectResponse
    {
        if ($run->character_id !== $request->user()->character->id) {
            abort(403);
        }

        try {
            $dungeonService->advanceFloor($run);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['dungeon' => $e->getMessage()]);
        }

        return back()->with('success', 'Вы спустились на следующий этаж.');
    }

    public function usePotion(
        Request $request,
        DungeonRun $run,
        InventoryItem $inventoryItem,
        DungeonService $dungeonService,
    ): RedirectResponse {
        if ($run->character_id !== $request->user()->character->id) {
            abort(403);
        }

        try {
            $dungeonService->useHealingPotion($request->user()->character, $run, $inventoryItem);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['dungeon' => $e->getMessage()]);
        }

        return back()->with('success', 'Зелье использовано.');
    }
}
