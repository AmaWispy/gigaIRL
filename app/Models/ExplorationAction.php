<?php

namespace App\Models;

use App\Enums\ExplorationActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExplorationAction extends Model
{
    protected $fillable = [
        'exploration_session_id',
        'action_type',
        'energy_cost',
        'payload',
        'is_resolved',
    ];

    protected function casts(): array
    {
        return [
            'action_type' => ExplorationActionType::class,
            'payload' => 'array',
            'is_resolved' => 'boolean',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExplorationSession::class, 'exploration_session_id');
    }
}
