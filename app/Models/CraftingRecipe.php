<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CraftingRecipe extends Model
{
    protected $fillable = [
        'name',
        'result_item_id',
        'result_quantity',
        'energy_cost',
        'required_profession',
        'category',
        'recipe_scroll_item_id',
        'fixed_result_quality',
        'upgradable',
        'quality_upgradable',
        'ingredients',
    ];

    protected function casts(): array
    {
        return [
            'ingredients' => 'array',
            'upgradable' => 'boolean',
            'quality_upgradable' => 'boolean',
        ];
    }

    public function resultItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'result_item_id');
    }

    public function recipeScrollItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'recipe_scroll_item_id');
    }
}
