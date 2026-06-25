<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AchievementCompletion extends Model
{
    protected $fillable = [
        'character_id',
        'achievement_template_id',
        'completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AchievementTemplate::class, 'achievement_template_id');
    }
}
