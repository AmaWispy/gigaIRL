<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DungeonRun extends Model
{
    protected $fillable = [
        'character_id',
        'dungeon_id',
        'current_floor',
        'character_hp',
        'run_xp_earned',
        'run_money_earned',
        'is_active',
        'completed',
        'failed',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'completed' => 'boolean',
            'failed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    public function floorStates(): HasMany
    {
        return $this->hasMany(DungeonFloorState::class);
    }

    public function currentFloorState(): ?DungeonFloorState
    {
        return $this->floorStates()->where('floor', $this->current_floor)->first();
    }
}
