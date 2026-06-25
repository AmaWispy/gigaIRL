<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    protected $fillable = [
        'user_id',
        'current_location_id',
        'hp',
        'max_hp',
        'energy',
        'money',
        'xp',
        'level',
        'strength',
        'defense',
        'power',
        'profession',
        'blacksmith_rank',
        'last_hp_reset_at',
    ];

    protected function casts(): array
    {
        return [
            'last_hp_reset_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function equippedItems(): HasMany
    {
        return $this->hasMany(EquippedItem::class);
    }

    public function achievementCompletions(): HasMany
    {
        return $this->hasMany(AchievementCompletion::class);
    }

    public function energyTransactions(): HasMany
    {
        return $this->hasMany(EnergyTransaction::class);
    }

    public function characterSkills(): HasMany
    {
        return $this->hasMany(CharacterSkill::class);
    }

    public function combats(): HasMany
    {
        return $this->hasMany(Combat::class);
    }

    public function explorationSessions(): HasMany
    {
        return $this->hasMany(ExplorationSession::class);
    }
}
