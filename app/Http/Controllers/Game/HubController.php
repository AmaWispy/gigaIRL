<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Services\CharacterService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HubController extends Controller
{
    public function __invoke(Request $request, CharacterService $characterService): Response
    {
        $character = $request->user()->character;

        return Inertia::render('Game/Hub', [
            'character' => $characterService->toArray($character),
        ]);
    }
}
