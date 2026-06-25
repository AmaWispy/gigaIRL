<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InventoryItem extends Model
{
    protected $fillable = [
        'character_id',
        'item_id',
        'quantity',
        'quality',
        'equipment_level',
        'equipment_source',
        'upgrade_count',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function equippedSlot(): HasOne
    {
        return $this->hasOne(EquippedItem::class);
    }
}
