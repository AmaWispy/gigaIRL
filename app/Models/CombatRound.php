<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CombatRound extends Model
{
    protected $fillable = [
        'combat_id',
        'round_number',
        'actor',
        'action',
        'damage',
        'heal',
        'meta',
        'character_hp_after',
        'monster_hp_after',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function combat(): BelongsTo
    {
        return $this->belongsTo(Combat::class);
    }
}
