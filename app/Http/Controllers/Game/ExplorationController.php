<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\ExplorationAction;
use App\Services\CharacterService;
use App\Services\ExplorationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExplorationController extends Controller
{
    public function show(Request $request, ExplorationService $explorationService, CharacterService $characterService): Response|RedirectResponse
    {
        $character = $request->user()->character;
        $location = $character->currentLocation;

        if (! $location || $location->is_safe) {
            return redirect()->route('world.map')
                ->withErrors(['exploration' => 'Исследование доступно только в опасных зонах.']);
        }

        $session = $explorationService->getActiveSession($character);

        return Inertia::render('Game/Exploration/Show', [
            'character' => $characterService->toArray($character),
            'location' => $location,
            'session' => $session ? [
                'id' => $session->id,
                'actions' => $session->actions->map(fn ($a) => [
                    'id' => $a->id,
                    'action_type' => $a->action_type->value,
                    'energy_cost' => $a->energy_cost,
                    'is_resolved' => $a->is_resolved,
                    'payload' => $a->payload,
                    'result_message' => ($a->payload['result']['message'] ?? null)
                        ?: ($a->is_resolved && isset($a->payload['result'])
                            ? $explorationService->formatExplorationResult($a->payload['result'])
                            : null),
                ]),
            ] : null,
            'lookAroundEnergy' => config('game.exploration.look_around_energy'),
            'flavor' => session('flavor'),
        ]);
    }

    public function lookAround(Request $request, ExplorationService $explorationService, \App\Services\FlavorService $flavorService): RedirectResponse
    {
        try {
            $character = $request->user()->character;
            $session = $explorationService->lookAround($character);

            $messages = [$flavorService->randomLookQuip()];

            if ($event = $flavorService->rollExplorationEvent($character->fresh())) {
                $messages[] = $event['message'];
            }

            $ambush = $session->actions->first(fn ($a) => ($a->payload['ambush'] ?? false) === true);
            if ($ambush) {
                return redirect()->route('exploration.show')
                    ->with('warning', 'На вас напали!')
                    ->with('flavor', implode(' ', $messages));
            }

            return redirect()->route('exploration.show')
                ->with('success', 'Вы осмотрелись.')
                ->with('flavor', implode(' ', $messages));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['exploration' => $e->getMessage()]);
        }
    }

    public function resolveAction(Request $request, ExplorationAction $action, ExplorationService $explorationService): RedirectResponse
    {
        try {
            $result = $explorationService->resolveAction($request->user()->character, $action);

            if (($result['type'] ?? '') === 'combat') {
                return redirect()->route('combat.show', $result['combat_id']);
            }

            if (($result['type'] ?? '') === 'dungeon_entrance') {
                return redirect()->route('world.map')
                    ->with('success', $explorationService->formatExplorationResult($result));
            }

            return back()->with('success', $explorationService->formatExplorationResult($result));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }
    }
}
