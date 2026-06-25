<?php

namespace App\Services;

use App\Models\Character;
use App\Models\EquippedItem;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CharacterService
{
    public function __construct(
        private EquipmentService $equipmentService,
    ) {}

    public function createForUser(User $user): Character
    {
        $startingLocation = Location::where('type', 'city')->first();

        $starting = config('game.starting');

        $character = Character::create([
            'user_id' => $user->id,
            'current_location_id' => $startingLocation?->id,
            'hp' => $starting['hp'],
            'max_hp' => $starting['hp'],
            'energy' => $starting['energy'],
            'money' => $starting['money'],
            'xp' => $starting['xp'],
            'level' => $starting['level'],
            'strength' => $starting['strength'],
            'defense' => $starting['defense'],
            'power' => 0,
            'profession' => 'none',
        'blacksmith_rank' => 'apprentice',
            'last_hp_reset_at' => now(),
        ]);

        $this->recalculatePower($character);

        return $character->fresh(['currentLocation']);
    }

    public function recalculatePower(Character $character): Character
    {
        $equipmentBonus = $this->getEquipmentBonus($character);

        $power = (int) (
            $character->level
            + $character->defense * config('game.power.armor_multiplier')
            + $character->strength * config('game.power.damage_multiplier')
            + (int) floor($this->getEffectiveMaxHp($character) / config('game.power.hp_divisor'))
            + $equipmentBonus
        );

        $character->update(['power' => $power]);

        return $character->fresh();
    }

    public function getEquipmentStatBonus(Character $character, string $stat): int
    {
        return $character->equippedItems()
            ->with('inventoryItem.item')
            ->get()
            ->sum(function (EquippedItem $equipped) use ($stat) {
                $stats = $this->equipmentService->computeStatsForInventoryItem($equipped->inventoryItem);

                return $stats[$stat] ?? 0;
            });
    }

    public function getEquipmentBonus(Character $character): int
    {
        return $this->getEquipmentStatBonus($character, 'strength')
            + $this->getEquipmentStatBonus($character, 'defense');
    }

    public function getEffectiveStrength(Character $character): int
    {
        return $character->strength + $this->getEquipmentStatBonus($character, 'strength');
    }

    public function getEffectiveDefense(Character $character): int
    {
        return $character->defense + $this->getEquipmentStatBonus($character, 'defense');
    }

    public function getEffectiveMaxHp(Character $character): int
    {
        return $character->max_hp + $this->getEquipmentStatBonus($character, 'max_hp');
    }

    public function syncHpWithEquipment(Character $character, int $previousEffectiveMaxHp): Character
    {
        $effectiveMaxHp = $this->getEffectiveMaxHp($character);
        $bonusDelta = $effectiveMaxHp - $previousEffectiveMaxHp;

        if ($bonusDelta > 0 && $character->hp >= $previousEffectiveMaxHp) {
            $character->hp = min($character->hp + $bonusDelta, $effectiveMaxHp);
        } else {
            $character->hp = min($character->hp, $effectiveMaxHp);
        }

        $character->save();

        return $character->fresh();
    }

    public function addXp(Character $character, int $xp): Character
    {
        return DB::transaction(function () use ($character, $xp) {
            $character->refresh();
            $character->xp += $xp;

            $maxLevel = config('game.level.max_level');

            while (
                $character->level < $maxLevel
                && $character->xp >= $this->xpRequiredForLevel($character->level)
            ) {
                $character->xp -= $this->xpRequiredForLevel($character->level);
                $character->level++;

                $character->max_hp += config('game.level.hp_bonus');
                $character->hp = min($character->hp + config('game.level.hp_bonus'), $character->max_hp);

                if ($character->level % config('game.level.stat_bonus_every_n_levels') === 0) {
                    $character->strength += config('game.level.stat_bonus');
                    $character->defense += config('game.level.stat_bonus');
                }
            }

            $character->save();
            $this->recalculatePower($character);

            return $character->fresh();
        });
    }

    public function xpRequiredForLevel(int $level): int
    {
        $base = config('game.level.xp_per_level');
        $multiplier = config('game.level.xp_level_multiplier', 1.2);

        return (int) floor($base * ($multiplier ** max(0, $level - 1)));
    }

    public function restoreDailyHp(Character $character): void
    {
        $character->update([
            'hp' => $this->getEffectiveMaxHp($character),
            'last_hp_reset_at' => now(),
        ]);
    }

    public function toArray(Character $character): array
    {
        $character->loadMissing(['currentLocation', 'equippedItems.inventoryItem.item']);

        return [
            'id' => $character->id,
            'hp' => $character->hp,
            'max_hp' => $character->max_hp,
            'effective_max_hp' => $this->getEffectiveMaxHp($character),
            'energy' => $character->energy,
            'money' => $character->money,
            'xp' => $character->xp,
            'level' => $character->level,
            'strength' => $character->strength,
            'defense' => $character->defense,
            'effective_strength' => $this->getEffectiveStrength($character),
            'effective_defense' => $this->getEffectiveDefense($character),
            'power' => $character->power,
            'profession' => $character->profession,
            'blacksmith_rank' => $character->blacksmith_rank ?? 'apprentice',
            'xp_to_next_level' => $this->xpRequiredForLevel($character->level),
            'current_location' => $character->currentLocation ? [
                'id' => $character->currentLocation->id,
                'name' => $character->currentLocation->name,
                'type' => $character->currentLocation->type,
                'is_safe' => $character->currentLocation->is_safe,
            ] : null,
        ];
    }
}
