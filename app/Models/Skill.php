<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    protected $fillable = [
        'name',
        'catalog_key',
        'type',
        'power',
        'priority',
        'description',
        'min_learn_level',
        'teach_price',
    ];

    public function characterSkills(): HasMany
    {
        return $this->hasMany(CharacterSkill::class);
    }
}
