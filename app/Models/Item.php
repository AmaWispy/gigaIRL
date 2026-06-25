<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $fillable = [
        'name',
        'catalog_key',
        'type',
        'equipment_source',
        'set_key',
        'item_level',
        'tier',
        'tier_emoji',
        'slot',
        'stats',
        'buy_price',
        'sell_price',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'stats' => 'array',
        ];
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function merchantSalePrice(): int
    {
        $base = $this->buy_price > 0 ? $this->buy_price : $this->sell_price;

        if ($base <= 0) {
            return 0;
        }

        return (int) floor($base * config('game.merchant.sell_ratio'));
    }

    public function isSellable(): bool
    {
        return $this->merchantSalePrice() > 0;
    }
}
