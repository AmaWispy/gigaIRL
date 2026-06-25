<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name',
        'type',
        'min_power',
        'world_tier',
        'is_safe',
        'parent_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_safe' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    public function connectionsFrom(): HasMany
    {
        return $this->hasMany(LocationConnection::class, 'from_location_id');
    }

    public function connectionsTo(): HasMany
    {
        return $this->hasMany(LocationConnection::class, 'to_location_id');
    }

    public function pois(): HasMany
    {
        return $this->hasMany(LocationPoi::class);
    }

    public function monsters(): HasMany
    {
        return $this->hasMany(Monster::class);
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class, 'current_location_id');
    }
}
