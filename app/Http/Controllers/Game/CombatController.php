<?php

namespace App\Http\Controllers\Game;

use App\Enums\CombatStatus;
use App\Http\Controllers\Controller;
use App\Models\Combat;
use App\Services\CharacterService;
use App\Services\CombatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CombatController extends Controller
{
    public function show(Request $request, Combat $combat, CharacterService $characterService, CombatService $combatService): Response
    {
        if ($combat->character_id !== $request->user()->character->id) {
            abort(403);
        }

        if ($combat->status === CombatStatus::Active) {
            $combat = $combatService->runToCompletion($combat);
        }

        $combat->load(['monster', 'rounds']);

        $startingHp = $combatService->getStartingHp($combat);

        $returnRoute = route('exploration.show');
        $dungeonDefeat = false;

        if ($combat->dungeon_run_id) {
            $run = \App\Models\DungeonRun::find($combat->dungeon_run_id);

            if ($run && $run->is_active) {
                $returnRoute = route('dungeon.run', $run);
            } elseif ($run && $run->failed) {
                $returnRoute = route('world.city');
                $dungeonDefeat = true;
            } else {
                $returnRoute = route('world.map');
            }
        }

        return Inertia::render('Game/Combat/Show', [
            'character' => $characterService->toArray($request->user()->character),
            'returnRoute' => $returnRoute,
            'dungeonDefeat' => $dungeonDefeat,
            'combat' => [
                'id' => $combat->id,
                'status' => $combat->status->value,
                'character_hp' => $combat->character_hp,
                'monster_hp' => $combat->monster_hp,
                'character_hp_start' => $startingHp['character'],
                'monster_hp_start' => $startingHp['monster'],
                'rewards' => $combat->rewards,
                'monster' => [
                    'name' => $combat->monster->name,
                    'hp' => $combat->monster->hp,
                    'tier' => $combat->monster->tier,
                    'flavor_text' => $combat->monster->flavor_text,
                ],
                'rounds' => $combat->rounds->map(fn ($r) => [
                    'round_number' => $r->round_number,
                    'actor' => $r->actor,
                    'action' => $r->action,
                    'damage' => $r->damage,
                    'heal' => $r->heal,
                    'meta' => $r->meta,
                    'character_hp_after' => $r->character_hp_after,
                    'monster_hp_after' => $r->monster_hp_after,
                ]),
            ],
        ]);
    }

    public function attack(Request $request, Combat $combat, CombatService $combatService): RedirectResponse
    {
        if ($combat->character_id !== $request->user()->character->id) {
            abort(403);
        }

        if ($combat->status !== CombatStatus::Active) {
            return back()->withErrors(['combat' => 'Бой уже завершён.']);
        }

        $combatService->executeRound($combat);

        return redirect()->route('combat.show', $combat);
    }
}
