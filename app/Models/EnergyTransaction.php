<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EnergyTransaction extends Model
{
    protected $fillable = [
        'character_id',
        'amount',
        'source',
        'reference_type',
        'reference_id',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
