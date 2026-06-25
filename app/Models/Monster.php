<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Monster extends Model
{
    protected $fillable = [
        'name',
        'flavor_text',
        'hp',
        'attack',
        'defense',
        'tier',
        'energy_cost',
        'xp_reward',
        'money_reward',
        'loot_table',
        'location_id',
    ];

    protected function casts(): array
    {
        return [
            'loot_table' => 'array',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function combats(): HasMany
    {
        return $this->hasMany(Combat::class);
    }
}
