<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\MerchantOffer;
use App\Services\CharacterService;
use App\Services\InventoryService;
use App\Services\MerchantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function show(Request $request, string $poiType, MerchantService $merchantService, CharacterService $characterService, InventoryService $inventoryService): Response
    {
        $character = $request->user()->character;
        $offers = $merchantService->getOffersForPoi($poiType, $character->current_location_id, $character);

        if (empty($offers) && ! in_array($poiType, ['material_merchant', 'armorer', 'alchemist', 'guild_master', 'recipe_merchant', 'seal_trader'])) {
            abort(404);
        }

        return Inertia::render('Game/World/Shop', [
            'character' => $characterService->toArray($character),
            'poiType' => $poiType,
            'poiTitle' => $this->poiTitle($poiType),
            'greeting' => $merchantService->greeting($poiType),
            'offers' => $offers,
            'inventory' => $inventoryService->getInventory($character),
            'sellRatioPercent' => (int) (config('game.merchant.sell_ratio') * 100),
        ]);
    }

    public function buy(Request $request, MerchantOffer $offer, MerchantService $merchantService): RedirectResponse
    {
        try {
            $merchantService->buy($request->user()->character, $offer);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['shop' => $e->getMessage()]);
        }

        $offer->load('item');

        return back()->with('success', "Куплено: {$offer->item->name}");
    }

    public function sell(Request $request, InventoryItem $inventoryItem, MerchantService $merchantService): RedirectResponse
    {
        $character = $request->user()->character;

        if ($inventoryItem->character_id !== $character->id) {
            abort(403);
        }

        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
        ]);

        try {
            $total = $merchantService->sellInventoryItem(
                $character,
                $inventoryItem,
                $validated['quantity'] ?? 1
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['shop' => $e->getMessage()]);
        }

        $inventoryItem->load('item');

        return back()->with('success', "Продано: {$inventoryItem->item->name} (+{$total} 💰)");
    }

    private function poiTitle(string $poiType): string
    {
        return match ($poiType) {
            'material_merchant' => 'Торговец сырьём',
            'armorer' => 'Оружейник',
            'alchemist' => 'Алхимик',
            'guild_master' => 'Гильдмастер',
            'recipe_merchant' => 'Лавка рецептов',
            'seal_trader' => 'Хранитель печатей',
            default => 'Лавка',
        };
    }
}
