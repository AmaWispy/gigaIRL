<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Combat;
use App\Models\Monster;

class CombatSkillService
{
    public function __construct(
        private SkillService $skillService,
        private CombatDamageService $combatDamageService,
    ) {}

    /**
     * @return array{
     *     damage: int,
     *     blocked: int,
     *     heal: int,
     *     skip_monster_turn: bool,
     *     labels: list<string>,
     * }
     */
    public function resolveCharacterAttack(
        Combat $combat,
        Character $character,
        Monster $monster,
        int $attackPower,
    ): array {
        $skillKeys = $this->skillService->getEquippedSkillKeys($character);
        $attackNumber = $combat->rounds()
            ->where('actor', 'character')
            ->where('action', 'attack')
            ->count() + 1;

        $damageMultiplier = 1.0;
        $ignoreArmor = false;
        $heal = 0;
        $skipMonsterTurn = false;
        $labels = [];

        foreach ($skillKeys as $key) {
            $effect = config("skills.effects.{$key}");

            if (! $effect) {
                continue;
            }

            if (! $this->characterAttackTriggers($effect, $attackNumber)) {
                continue;
            }

            $skillName = $this->skillLabel($key);

            if (($effect['damage_multiplier'] ?? 1) > 1) {
                $damageMultiplier *= (float) $effect['damage_multiplier'];
                $labels[] = $skillName;
            }

            if ($effect['ignore_armor'] ?? false) {
                $ignoreArmor = true;

                if (! in_array($skillName, $labels, true)) {
                    $labels[] = $skillName;
                }
            }

            if ($effect['stun'] ?? false) {
                $skipMonsterTurn = true;

                if (! in_array($skillName, $labels, true)) {
                    $labels[] = $skillName;
                }
            }
        }

        $defense = $ignoreArmor ? 0 : $monster->defense;
        $armorResult = $this->combatDamageService->resolve($attackPower, $defense, $ignoreArmor);
        $baseDamage = $armorResult['damage'];
        $blocked = $armorResult['blocked'];
        $damage = max(1, (int) floor($baseDamage * $damageMultiplier));

        foreach ($skillKeys as $key) {
            $effect = config("skills.effects.{$key}");

            if (! $effect || ! $this->characterAttackTriggers($effect, $attackNumber)) {
                continue;
            }

            if (isset($effect['lifesteal_ratio'])) {
                $heal += (int) floor($damage * (float) $effect['lifesteal_ratio']);
                $skillName = $this->skillLabel($key);

                if (! in_array($skillName, $labels, true)) {
                    $labels[] = $skillName;
                }
            }
        }

        return [
            'damage' => $damage,
            'blocked' => $blocked,
            'heal' => $heal,
            'skip_monster_turn' => $skipMonsterTurn,
            'labels' => $labels,
        ];
    }

    /**
     * @return array{
     *     reflect_damage: int,
     *     labels: list<string>,
     * }
     */
    public function resolveMonsterAttackRetribution(
        Combat $combat,
        Character $character,
        int $monsterDamage,
    ): array {
        $skillKeys = $this->skillService->getEquippedSkillKeys($character);
        $attackNumber = $combat->rounds()
            ->where('actor', 'monster')
            ->where('action', 'attack')
            ->count() + 1;

        foreach ($skillKeys as $key) {
            if ($key !== 'retribution') {
                continue;
            }

            $effect = config('skills.effects.retribution');

            if ($this->monsterAttackTriggers($effect, $attackNumber)) {
                return [
                    'reflect_damage' => max(1, (int) floor($monsterDamage * (float) $effect['reflect_multiplier'])),
                    'labels' => [$this->skillLabel($key)],
                ];
            }
        }

        return [
            'reflect_damage' => 0,
            'labels' => [],
        ];
    }

    private function characterAttackTriggers(array $effect, int $attackNumber): bool
    {
        return match ($effect['trigger'] ?? null) {
            'first_attack' => $attackNumber === 1,
            'every_nth_attack' => $attackNumber % (int) ($effect['n'] ?? 1) === 0,
            default => false,
        };
    }

    private function monsterAttackTriggers(array $effect, int $attackNumber): bool
    {
        return match ($effect['trigger'] ?? null) {
            'every_nth_monster_attack' => $attackNumber % (int) ($effect['n'] ?? 1) === 0,
            default => false,
        };
    }

    private function skillLabel(string $catalogKey): string
    {
        return match ($catalogKey) {
            'stealth_strike' => 'Атака исподтишка',
            'power_strike' => 'Мощный удар',
            'retribution' => 'Возмездие',
            'cleaving_strike' => 'Разрубающий удар',
            'stunning_strike' => 'Оглушающий удар',
            'vampire_strike' => 'Удар вампира',
            default => $catalogKey,
        };
    }
}
