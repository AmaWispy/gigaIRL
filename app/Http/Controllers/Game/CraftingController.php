<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\CraftingRecipe;
use App\Models\InventoryItem;
use App\Services\CharacterService;
use App\Services\CraftingService;
use App\Services\EquipmentUpgradeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CraftingController extends Controller
{
    public function index(
        Request $request,
        CraftingService $craftingService,
        CharacterService $characterService,
        EquipmentUpgradeService $equipmentUpgradeService,
    ): Response {
        $character = $request->user()->character;
        $groupedRecipes = $craftingService->getRecipesByCategory($character);

        return Inertia::render('Game/Crafting/Index', [
            'character' => $characterService->toArray($character),
            'basicRecipes' => $groupedRecipes['basic'],
            'rareRecipes' => $groupedRecipes['rare'],
            'professions' => config('game.professions'),
            'blacksmithRanks' => $craftingService->getBlacksmithRanks($character),
            'upgradeOptions' => $equipmentUpgradeService->getUpgradeOptions($character),
        ]);
    }

    public function craft(Request $request, CraftingRecipe $recipe, CraftingService $craftingService): RedirectResponse
    {
        try {
            $craftingService->craft($request->user()->character, $recipe);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['craft' => $e->getMessage()]);
        }

        return back()->with('success', 'Предмет создан!');
    }

    public function learnProfession(Request $request, CraftingService $craftingService): RedirectResponse
    {
        $validated = $request->validate([
            'profession' => 'required|in:blacksmith',
        ]);

        $craftingService->learnProfession($request->user()->character, $validated['profession']);

        return back()->with('success', 'Профессия изучена! Вы — подмастерье.');
    }

    public function upgradeRank(Request $request, CraftingService $craftingService): RedirectResponse
    {
        $validated = $request->validate([
            'rank' => 'required|in:journeyman,master,grandmaster',
        ]);

        try {
            $craftingService->upgradeBlacksmithRank($request->user()->character, $validated['rank']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['craft' => $e->getMessage()]);
        }

        return back()->with('success', 'Ранг кузнеца повышен!');
    }

    public function upgradeEquipment(
        Request $request,
        InventoryItem $inventoryItem,
        EquipmentUpgradeService $equipmentUpgradeService,
    ): RedirectResponse {
        $character = $request->user()->character;

        if ($inventoryItem->character_id !== $character->id) {
            abort(403);
        }

        $validated = $request->validate([
            'action' => 'required|in:crafted_level,crafted_quality,dungeon_level',
        ]);

        try {
            match ($validated['action']) {
                'crafted_level' => $equipmentUpgradeService->upgradeCraftedLevel($character, $inventoryItem),
                'crafted_quality' => $equipmentUpgradeService->upgradeCraftedQuality($character, $inventoryItem),
                'dungeon_level' => $equipmentUpgradeService->upgradeDungeonLevel($character, $inventoryItem),
            };
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['craft' => $e->getMessage()]);
        }

        return back()->with('success', 'Экипировка улучшена!');
    }
}
