<?php

namespace App\Models;

use App\Enums\DungeonMonsterRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DungeonFloorState extends Model
{
    protected $fillable = [
        'dungeon_run_id',
        'floor',
        'monster_id',
        'mob_role',
        'mob_defeated',
        'has_resource_pile',
        'resource_claimed',
        'has_treasure',
        'treasure_claimed',
    ];

    protected function casts(): array
    {
        return [
            'mob_role' => DungeonMonsterRole::class,
            'mob_defeated' => 'boolean',
            'has_resource_pile' => 'boolean',
            'resource_claimed' => 'boolean',
            'has_treasure' => 'boolean',
            'treasure_claimed' => 'boolean',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(DungeonRun::class, 'dungeon_run_id');
    }

    public function monster(): BelongsTo
    {
        return $this->belongsTo(Monster::class);
    }
}
