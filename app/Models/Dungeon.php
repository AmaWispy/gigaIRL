<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Dungeon extends Model
{
    protected $fillable = [
        'catalog_key',
        'name',
        'tier',
        'set_key',
        'item_level',
        'min_power',
        'location_id',
        'description',
        'entry_energy',
        'floor_energy',
        'combat_energy',
        'resource_energy',
        'treasure_energy',
        'floors_total',
        'rare_floor',
        'boss_floor',
        'pass_price',
        'resource_pile_chance',
        'treasure_chance',
        'resource_quantity_min',
        'resource_quantity_max',
        'rare_resource_from_pile_chance',
        'rare_resource_from_mob_chance',
        'treasure_money_min',
        'treasure_money_max',
        'mob_money_multiplier',
        'set_equipment_non_weapon_chance',
        'xp_multiplier_normal',
        'xp_multiplier_rare',
        'xp_multiplier_boss',
        'clear_bonus_xp_percent',
        'clear_bonus_money',
        'explorer_seal_on_clear',
        'exploration_entrance_energy',
        'exploration_entrance_chance',
    ];

    protected function casts(): array
    {
        return [
            'mob_money_multiplier' => 'float',
            'xp_multiplier_normal' => 'float',
            'xp_multiplier_rare' => 'float',
            'xp_multiplier_boss' => 'float',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function dungeonMonsters(): HasMany
    {
        return $this->hasMany(DungeonMonster::class);
    }

    public function lootRules(): HasMany
    {
        return $this->hasMany(DungeonLootRule::class);
    }

    public function resourcePools(): HasMany
    {
        return $this->hasMany(DungeonResourcePool::class);
    }

    public function unlocks(): HasMany
    {
        return $this->hasMany(CharacterDungeonUnlock::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(DungeonRun::class);
    }

    public function lootChance(string $source, string $rewardType): int
    {
        $this->loadMissing('lootRules');

        return (int) ($this->lootRules
            ->first(fn (DungeonLootRule $rule) => $rule->source === $source && $rule->reward_type === $rewardType)
            ?->chance_percent ?? 0);
    }

    public function xpMultiplierForRole(string $role): float
    {
        return match ($role) {
            'rare' => (float) $this->xp_multiplier_rare,
            'boss' => (float) $this->xp_multiplier_boss,
            default => (float) $this->xp_multiplier_normal,
        };
    }

    /**
     * @return Collection<int, Item>
     */
    public function resourceItemsForPool(string $pool): Collection
    {
        $this->loadMissing(['resourcePools.item']);

        return $this->resourcePools
            ->where('pool', $pool)
            ->sortBy(fn (DungeonResourcePool $entry) => $entry->item?->catalog_key ?? '')
            ->map(fn (DungeonResourcePool $entry) => $entry->item)
            ->filter()
            ->values();
    }
}
