<?php

namespace App\Models;

use App\Enums\DungeonMonsterRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DungeonMonster extends Model
{
    protected $fillable = [
        'dungeon_id',
        'role',
        'monster_id',
    ];

    protected function casts(): array
    {
        return [
            'role' => DungeonMonsterRole::class,
        ];
    }

    public function dungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class);
    }

    public function monster(): BelongsTo
    {
        return $this->belongsTo(Monster::class);
    }
}
