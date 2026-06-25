<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantOffer extends Model
{
    protected $fillable = [
        'location_id',
        'poi_type',
        'item_id',
        'buy_price',
        'cost_item_id',
        'cost_quantity',
        'stock',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function costItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'cost_item_id');
    }
}
