<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Services\CharacterService;
use App\Services\FlavorService;
use App\Services\SkillService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SkillTrainerController extends Controller
{
    public function show(Request $request, CharacterService $characterService, SkillService $skillService, FlavorService $flavorService): Response
    {
        $character = $request->user()->character;

        if ($character->currentLocation?->type !== 'city') {
            abort(403, 'Наставник доступен только в городе.');
        }

        return Inertia::render('Game/Skills/Trainer', [
            'character' => $characterService->toArray($character),
            'skills' => $skillService->getTrainerCatalog($character),
            'maxEquipSlots' => $skillService->maxEquipSlots($character->level),
            'greeting' => $flavorService->merchantGreeting('skill_trainer'),
        ]);
    }

    public function learn(Request $request, Skill $skill, SkillService $skillService): RedirectResponse
    {
        try {
            $skillService->learn($request->user()->character, $skill);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['skill' => $e->getMessage()]);
        }

        return back()->with('success', "Вы выучили приём «{$skill->name}».");
    }
}
