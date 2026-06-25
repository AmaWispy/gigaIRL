<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DungeonLootRule extends Model
{
    protected $fillable = [
        'dungeon_id',
        'source',
        'reward_type',
        'chance_percent',
    ];

    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }
}
