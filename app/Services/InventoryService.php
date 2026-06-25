<?php

namespace App\Services;

use App\Models\Character;
use App\Models\EquippedItem;
use App\Models\InventoryItem;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function __construct(
        private CharacterService $characterService,
        private EquipmentService $equipmentService,
    ) {}

    public function addItem(Character $character, Item $item, int $quantity = 1): InventoryItem
    {
        if ($item->type === 'equipment') {
            return $this->addEquipment($character, $item);
        }

        $inventoryItem = InventoryItem::firstOrNew([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'quality' => null,
        ]);

        $inventoryItem->quantity = ($inventoryItem->quantity ?? 0) + $quantity;
        $inventoryItem->save();

        return $inventoryItem->load('item');
    }

    /**
     * @param  array{quality?: string, level?: int, source?: string}  $instance
     */
    public function addEquipment(Character $character, Item $item, array $instance = []): InventoryItem
    {
        if ($item->type !== 'equipment') {
            throw new InvalidArgumentException('Шаблон не является экипировкой.');
        }

        return InventoryItem::create([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'quality' => $instance['quality'] ?? 'white',
            'equipment_level' => $instance['level'] ?? $item->item_level ?? 1,
            'equipment_source' => $instance['source'] ?? $item->equipment_source ?? 'vendor',
            'upgrade_count' => 0,
        ])->load('item');
    }

    public function removeItem(Character $character, Item $item, int $quantity = 1): void
    {
        $inventoryItem = InventoryItem::where('character_id', $character->id)
            ->where('item_id', $item->id)
            ->whereNull('quality')
            ->first();

        if (! $inventoryItem || $inventoryItem->quantity < $quantity) {
            throw new InvalidArgumentException('Недостаточно предметов.');
        }

        if ($inventoryItem->quantity === $quantity) {
            $inventoryItem->delete();
        } else {
            $inventoryItem->decrement('quantity', $quantity);
        }
    }

    public function removeInventoryItem(InventoryItem $inventoryItem): void
    {
        EquippedItem::where('inventory_item_id', $inventoryItem->id)->delete();
        $inventoryItem->delete();
    }

    public function equip(Character $character, InventoryItem $inventoryItem): void
    {
        if ($inventoryItem->character_id !== $character->id) {
            throw new InvalidArgumentException('Это не ваш предмет.');
        }

        $item = $inventoryItem->item;

        if ($item->type !== 'equipment' || ! $item->slot) {
            throw new InvalidArgumentException('Этот предмет нельзя экипировать.');
        }

        $equipSlot = $this->resolveEquipSlot($character, $item, $inventoryItem);

        DB::transaction(function () use ($character, $inventoryItem, $equipSlot) {
            $previousEffectiveMaxHp = $this->characterService->getEffectiveMaxHp($character);

            EquippedItem::where('character_id', $character->id)
                ->where('slot', $equipSlot)
                ->delete();

            EquippedItem::updateOrCreate(
                ['character_id' => $character->id, 'slot' => $equipSlot],
                ['inventory_item_id' => $inventoryItem->id]
            );

            $character->refresh();
            $this->characterService->recalculatePower($character);
            $this->characterService->syncHpWithEquipment($character, $previousEffectiveMaxHp);
        });
    }

    private function resolveEquipSlot(Character $character, Item $item, InventoryItem $inventoryItem): string
    {
        if (! in_array($item->slot, ['ring1', 'ring2'], true)) {
            return $item->slot;
        }

        $ringSlots = ['ring1', 'ring2'];
        $occupied = EquippedItem::where('character_id', $character->id)
            ->whereIn('slot', $ringSlots)
            ->get();

        foreach ($occupied as $equipped) {
            if ($equipped->inventory_item_id === $inventoryItem->id) {
                return $equipped->slot;
            }
        }

        $occupiedSlots = $occupied->pluck('slot')->all();

        if ($item->slot === 'ring2' && ! in_array('ring2', $occupiedSlots, true)) {
            return 'ring2';
        }

        if (! in_array('ring1', $occupiedSlots, true)) {
            return 'ring1';
        }

        if (! in_array('ring2', $occupiedSlots, true)) {
            return 'ring2';
        }

        return $item->slot === 'ring2' ? 'ring2' : 'ring1';
    }

    public function unequip(Character $character, string $slot): void
    {
        $previousEffectiveMaxHp = $this->characterService->getEffectiveMaxHp($character);

        EquippedItem::where('character_id', $character->id)
            ->where('slot', $slot)
            ->delete();

        $character->refresh();
        $this->characterService->recalculatePower($character);
        $this->characterService->syncHpWithEquipment($character, $previousEffectiveMaxHp);
    }

    public function useConsumable(Character $character, InventoryItem $inventoryItem): Character
    {
        $item = $inventoryItem->item;

        if ($item->type !== 'consumable') {
            throw new InvalidArgumentException('Этот предмет нельзя использовать.');
        }

        DB::transaction(function () use ($character, $inventoryItem, $item) {
            $restore = $item->stats['hp_restore'] ?? config('game.potion.hp_restore');
            $effectiveMaxHp = $this->characterService->getEffectiveMaxHp($character);
            $character->hp = min($character->hp + $restore, $effectiveMaxHp);
            $character->save();

            $this->removeItem($character, $item, 1);
        });

        return $character->fresh();
    }

    public function getInventoryItemSellPrice(InventoryItem $inv): int
    {
        if ($inv->item->type === 'equipment') {
            return $this->equipmentService->instanceSalePrice($inv);
        }

        return $inv->item->merchantSalePrice();
    }

    public function canSellInventoryItem(InventoryItem $inv): bool
    {
        if ($inv->equippedSlot) {
            return false;
        }

        if ($inv->item->type === 'equipment') {
            $source = $inv->equipment_source ?? $inv->item->equipment_source ?? 'vendor';

            return in_array($source, ['vendor', 'crafted'], true)
                && $this->getInventoryItemSellPrice($inv) > 0;
        }

        return $inv->item->isSellable();
    }

    public function getInventory(Character $character): array
    {
        return $character->inventoryItems()
            ->with(['item', 'equippedSlot'])
            ->get()
            ->map(function (InventoryItem $inv) {
                $computed = $inv->item->type === 'equipment'
                    ? $this->equipmentService->computeStatsForInventoryItem($inv)
                    : null;

                return [
                    'id' => $inv->id,
                    'quantity' => $inv->quantity,
                    'quality' => $inv->quality,
                    'quality_label' => $inv->quality
                        ? $this->equipmentService->qualityLabel($inv->quality)
                        : null,
                    'equipment_level' => $inv->equipment_level,
                    'equipment_source' => $inv->equipment_source,
                    'computed_stats' => $computed,
                    'item' => [
                        'id' => $inv->item->id,
                        'name' => $inv->item->name,
                        'type' => $inv->item->type,
                        'slot' => $inv->item->slot,
                        'stats' => $inv->item->stats,
                        'description' => $inv->item->description,
                        'buy_price' => $inv->item->buy_price,
                    ],
                    'sell_price' => $this->getInventoryItemSellPrice($inv),
                    'can_sell' => $this->canSellInventoryItem($inv),
                    'is_equipped' => $inv->equippedSlot !== null,
                ];
            })
            ->all();
    }

    public function getEquipped(Character $character): array
    {
        $slots = config('game.equipment_slots');
        $equipped = $character->equippedItems()
            ->with('inventoryItem.item')
            ->get()
            ->keyBy('slot');

        return collect($slots)->mapWithKeys(function ($slot) use ($equipped) {
            $eq = $equipped->get($slot);

            if (! $eq) {
                return [$slot => null];
            }

            $stats = $this->equipmentService->computeStatsForInventoryItem($eq->inventoryItem);

            return [$slot => [
                'inventory_item_id' => $eq->inventory_item_id,
                'name' => $eq->inventoryItem->item->name,
                'quality' => $eq->inventoryItem->quality,
                'quality_label' => $eq->inventoryItem->quality
                    ? $this->equipmentService->qualityLabel($eq->inventoryItem->quality)
                    : null,
                'equipment_level' => $eq->inventoryItem->equipment_level,
                'equipment_source' => $eq->inventoryItem->equipment_source,
                'stats' => $stats,
            ]];
        })->all();
    }
}
