<?php

namespace App\Models;

use App\Enums\AchievementFrequency;
use App\Enums\AchievementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AchievementTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'reward_points',
        'frequency',
        'is_default',
        'difficulty',
        'financial_rate',
        'is_primary_income',
    ];

    protected function casts(): array
    {
        return [
            'type' => AchievementType::class,
            'frequency' => AchievementFrequency::class,
            'is_default' => 'boolean',
            'is_primary_income' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(AchievementCompletion::class);
    }
}
