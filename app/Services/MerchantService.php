<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CraftingRecipe;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\MerchantOffer;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MerchantService
{
    public function __construct(
        private InventoryService $inventoryService,
        private FlavorService $flavorService,
        private EquipmentService $equipmentService,
    ) {}

    public function getOffersForPoi(string $poiType, ?int $locationId = null, ?Character $character = null): array
    {
        return MerchantOffer::with(['item', 'costItem'])
            ->where('poi_type', $poiType)
            ->when($locationId !== null, fn ($q) => $q->where('location_id', $locationId))
            ->get()
            ->map(function (MerchantOffer $offer) use ($character) {
                $item = $offer->item;
                $owned = 0;

                if ($character && $item->type !== 'equipment') {
                    $owned = (int) InventoryItem::where('character_id', $character->id)
                        ->where('item_id', $item->id)
                        ->sum('quantity');
                }

                $costItem = null;

                if ($offer->cost_item_id && $offer->costItem) {
                    $costOwned = $character
                        ? (int) InventoryItem::where('character_id', $character->id)
                            ->where('item_id', $offer->cost_item_id)
                            ->sum('quantity')
                        : 0;

                    $costItem = [
                        'name' => $offer->costItem->name,
                        'tier_emoji' => $offer->costItem->tier_emoji,
                        'quantity' => $offer->cost_quantity,
                        'owned' => $costOwned,
                    ];
                }

                $grantQuality = $this->grantQualityForItem($item);
                $equipmentPreview = $this->equipmentService->previewItem($item, 'vendor', $grantQuality);

                if ($equipmentPreview === null && $item->type === 'recipe_scroll') {
                    $equipmentPreview = $this->recipeResultPreview($item);
                }

                return [
                    'id' => $offer->id,
                    'item_id' => $offer->item_id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'tier_emoji' => $item->tier_emoji,
                    'buy_price' => $offer->buy_price,
                    'cost_item' => $costItem,
                    'stock' => $offer->stock,
                    'type' => $item->type,
                    'owned' => $owned,
                    'equipment_preview' => $equipmentPreview,
                ];
            })
            ->all();
    }

    public function buy(Character $character, MerchantOffer $offer): void
    {
        if ($offer->stock !== null && $offer->stock <= 0) {
            throw new InvalidArgumentException('Товар закончился. Приходите завтра — алхимик тоже спит.');
        }

        if ($character->money < $offer->buy_price) {
            throw new InvalidArgumentException('Недостаточно золота. Торговец сочувствует, но не кредитует.');
        }

        $costItem = null;

        if ($offer->cost_item_id) {
            $offer->loadMissing('costItem');
            $costItem = $offer->costItem;
            $owned = (int) InventoryItem::where('character_id', $character->id)
                ->where('item_id', $offer->cost_item_id)
                ->sum('quantity');

            if (! $costItem || $owned < $offer->cost_quantity) {
                throw new InvalidArgumentException("Недостаточно: нужно {$offer->cost_quantity} × {$costItem?->name}.");
            }
        }

        DB::transaction(function () use ($character, $offer, $costItem) {
            if ($offer->buy_price > 0) {
                $character->decrement('money', $offer->buy_price);
            }

            if ($costItem) {
                $this->inventoryService->removeItem($character, $costItem, $offer->cost_quantity);
            }

            if ($offer->item->type === 'equipment') {
                app(InventoryService::class)->addEquipment($character, $offer->item, [
                    'quality' => $this->grantQualityForItem($offer->item),
                    'source' => $offer->item->equipment_source ?? 'vendor',
                    'level' => $offer->item->item_level ?? 1,
                ]);
            } else {
                $this->inventoryService->addItem($character, $offer->item, 1);
            }

            if ($offer->stock !== null) {
                $offer->decrement('stock');
            }
        });
    }

    private function recipeResultPreview(Item $scroll): ?array
    {
        $recipe = CraftingRecipe::with('resultItem')
            ->where('recipe_scroll_item_id', $scroll->id)
            ->first();

        if (! $recipe || ! $recipe->resultItem) {
            return null;
        }

        $quality = $recipe->fixed_result_quality ?? 'white';

        return $this->equipmentService->previewItem($recipe->resultItem, 'crafted', $quality);
    }

    private function grantQualityForItem(Item $item): string
    {
        $tier = $item->tier;

        return $tier && array_key_exists($tier, config('equipment.qualities', []))
            ? $tier
            : 'white';
    }

    public function getSellPrice(Item $item): int
    {
        return $item->merchantSalePrice();
    }

    public function canSell(Item $item): bool
    {
        return $item->isSellable();
    }

    public function sellInventoryItem(Character $character, InventoryItem $inventoryItem, int $quantity = 1): int
    {
        if ($inventoryItem->character_id !== $character->id) {
            throw new InvalidArgumentException('Это не ваш предмет.');
        }

        if ($inventoryItem->equippedSlot()->exists()) {
            throw new InvalidArgumentException('Сначала снимите предмет с экипировки.');
        }

        if (! $this->inventoryService->canSellInventoryItem($inventoryItem)) {
            throw new InvalidArgumentException('Этот предмет никто не покупает. Даже бродячий кот отвернулся.');
        }

        $item = $inventoryItem->item;
        $unitPrice = $this->inventoryService->getInventoryItemSellPrice($inventoryItem);

        if ($unitPrice <= 0) {
            throw new InvalidArgumentException('Этот предмет никто не покупает. Даже бродячий кот отвернулся.');
        }

        if ($inventoryItem->quantity < $quantity) {
            throw new InvalidArgumentException('Недостаточно предметов для продажи.');
        }

        $total = $unitPrice * $quantity;

        DB::transaction(function () use ($character, $inventoryItem, $item, $quantity, $total) {
            if ($item->type === 'equipment') {
                $this->inventoryService->removeInventoryItem($inventoryItem);
            } else {
                $this->inventoryService->removeItem($character, $item, $quantity);
            }
            $character->increment('money', $total);
        });

        return $total;
    }

    public function greeting(string $poiType): ?string
    {
        return $this->flavorService->merchantGreeting($poiType);
    }
}
