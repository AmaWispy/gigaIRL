<?php

namespace App\Models;

use App\Enums\CombatStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Combat extends Model
{
    protected $fillable = [
        'character_id',
        'monster_id',
        'dungeon_run_id',
        'dungeon_floor_state_id',
        'status',
        'character_hp',
        'monster_hp',
        'combat_state',
        'rewards',
    ];

    protected function casts(): array
    {
        return [
            'status' => CombatStatus::class,
            'rewards' => 'array',
            'combat_state' => 'array',
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function monster(): BelongsTo
    {
        return $this->belongsTo(Monster::class);
    }

    public function dungeonRun(): BelongsTo
    {
        return $this->belongsTo(DungeonRun::class);
    }

    public function dungeonFloorState(): BelongsTo
    {
        return $this->belongsTo(DungeonFloorState::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(CombatRound::class);
    }
}
