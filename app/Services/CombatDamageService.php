<?php

namespace App\Services;

class CombatDamageService
{
    /**
     * Diminishing armor: reduction = scale × defense / (defense + pivot).
     * Examples (scale=0.6, pivot=10): 10 def → 30%, 20 def → 40%, 30 def → 45%.
     *
     * @return array{damage: int, blocked: int, reduction: float}
     */
    public function resolve(int $attack, int $defense, bool $ignoreArmor = false): array
    {
        if ($attack <= 0) {
            return ['damage' => 0, 'blocked' => 0, 'reduction' => 0.0];
        }

        if ($ignoreArmor || $defense <= 0) {
            $minDamage = config('game.combat.defense.min_damage', 1);

            return [
                'damage' => max($minDamage, $attack),
                'blocked' => 0,
                'reduction' => 0.0,
            ];
        }

        $scale = (float) config('game.combat.defense.scale', 0.6);
        $pivot = (float) config('game.combat.defense.pivot', 10);
        $minDamage = (int) config('game.combat.defense.min_damage', 1);

        $reduction = ($scale * $defense) / ($defense + $pivot);
        $reduction = min($reduction, $scale);

        $damage = max($minDamage, (int) floor($attack * (1 - $reduction)));
        $blocked = max(0, $attack - $damage);

        return [
            'damage' => $damage,
            'blocked' => $blocked,
            'reduction' => $reduction,
        ];
    }
}
