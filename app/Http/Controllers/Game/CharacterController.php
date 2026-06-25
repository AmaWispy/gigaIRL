<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\CharacterSkill;
use App\Models\InventoryItem;
use App\Services\CharacterService;
use App\Services\InventoryService;
use App\Services\SkillService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CharacterController extends Controller
{
    public function show(Request $request, CharacterService $characterService, InventoryService $inventoryService, SkillService $skillService): Response
    {
        $character = $request->user()->character;

        return Inertia::render('Game/Character/Show', [
            'character' => $characterService->toArray($character),
            'inventory' => $inventoryService->getInventory($character),
            'equipped' => $inventoryService->getEquipped($character),
            'equipmentSlots' => config('game.equipment_slots'),
            'skills' => $skillService->formatSkillsForCharacter($character),
            'maxSkillSlots' => $skillService->maxEquipSlots($character->level),
        ]);
    }

    public function equip(Request $request, InventoryItem $inventoryItem, InventoryService $inventoryService): RedirectResponse
    {
        try {
            $inventoryService->equip($request->user()->character, $inventoryItem);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['equip' => $e->getMessage()]);
        }

        return back()->with('success', 'Предмет экипирован.');
    }

    public function unequip(Request $request, InventoryService $inventoryService): RedirectResponse
    {
        $validated = $request->validate(['slot' => 'required|string']);

        $inventoryService->unequip($request->user()->character, $validated['slot']);

        return back()->with('success', 'Предмет снят.');
    }

    public function useItem(Request $request, InventoryItem $inventoryItem, InventoryService $inventoryService): RedirectResponse
    {
        try {
            $inventoryService->useConsumable($request->user()->character, $inventoryItem);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['item' => $e->getMessage()]);
        }

        return back()->with('success', 'Предмет использован.');
    }

    public function equipSkill(Request $request, CharacterSkill $characterSkill, SkillService $skillService): RedirectResponse
    {
        try {
            $skillService->equip($request->user()->character, $characterSkill);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['skill' => $e->getMessage()]);
        }

        return back()->with('success', 'Навык экипирован.');
    }

    public function unequipSkill(Request $request, CharacterSkill $characterSkill, SkillService $skillService): RedirectResponse
    {
        try {
            $skillService->unequip($request->user()->character, $characterSkill);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['skill' => $e->getMessage()]);
        }

        return back()->with('success', 'Навык снят.');
    }
}
