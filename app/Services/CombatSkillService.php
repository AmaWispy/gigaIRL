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
     *     defense_reduction: int,
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
        $bonusDamage = 0;
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

            if (($effect['bonus_damage'] ?? 0) > 0) {
                $bonusDamage += (int) $effect['bonus_damage'];

                if (! in_array($skillName, $labels, true)) {
                    $labels[] = $skillName;
                }
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

        // Палач: пока цель ниже порога HP — увеличиваем урон.
        if (in_array('executioner', $skillKeys, true)) {
            $effect = config('skills.effects.executioner');
            $maxMonsterHp = max(1, (int) $monster->hp);
            $threshold = (float) ($effect['hp_threshold_percent'] ?? 30) / 100;

            if ($combat->monster_hp > 0 && $combat->monster_hp / $maxMonsterHp < $threshold) {
                $damageMultiplier *= 1 + ((float) ($effect['damage_bonus_percent'] ?? 50) / 100);
                $labels[] = $this->skillLabel('executioner');
            }
        }

        // Поиск бреши: накопительный срез брони + бонус при пробитой броне.
        $defenseReduction = (int) ($combat->combat_state['defense_reduction'] ?? 0);
        $hasFindGap = in_array('find_the_gap', $skillKeys, true);

        if ($hasFindGap) {
            $effect = config('skills.effects.find_the_gap');
            $n = (int) ($effect['n'] ?? 2);

            if ($n > 0 && $attackNumber % $n === 0) {
                $defenseReduction += (int) ($effect['armor_shred'] ?? 2);
                $labels[] = $this->skillLabel('find_the_gap');
            }
        }

        $effectiveDefense = $ignoreArmor ? 0 : ($monster->defense - $defenseReduction);

        if ($hasFindGap && ! $ignoreArmor && $effectiveDefense <= 0) {
            $effect = config('skills.effects.find_the_gap');
            $damageMultiplier *= 1 + ((float) ($effect['broken_armor_bonus_percent'] ?? 25) / 100);

            if (! in_array($this->skillLabel('find_the_gap'), $labels, true)) {
                $labels[] = $this->skillLabel('find_the_gap');
            }
        }

        $defenseForResolve = $ignoreArmor ? 0 : max(0, $effectiveDefense);
        $armorResult = $this->combatDamageService->resolve($attackPower + $bonusDamage, $defenseForResolve, $ignoreArmor);
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
            'defense_reduction' => $defenseReduction,
            'labels' => $labels,
        ];
    }

    /**
     * Каменная кожа: на каждой N-й атаке противника прибавляет броню для смягчения урона.
     *
     * @return array{bonus_defense: int, labels: list<string>}
     */
    public function resolveIncomingMitigation(Combat $combat, Character $character): array
    {
        $skillKeys = $this->skillService->getEquippedSkillKeys($character);

        if (! in_array('stone_skin', $skillKeys, true)) {
            return ['bonus_defense' => 0, 'labels' => []];
        }

        $effect = config('skills.effects.stone_skin');
        $attackNumber = $combat->rounds()
            ->where('actor', 'monster')
            ->where('action', 'attack')
            ->count() + 1;
        $n = (int) ($effect['n'] ?? 3);

        if ($n > 0 && $attackNumber % $n === 0) {
            return [
                'bonus_defense' => (int) ($effect['bonus_defense'] ?? 20),
                'labels' => [$this->skillLabel('stone_skin')],
            ];
        }

        return ['bonus_defense' => 0, 'labels' => []];
    }

    /**
     * Второе дыхание: разовое лечение при низком HP.
     *
     * @return array{heal: int, used: bool, labels: list<string>}
     */
    public function resolveSecondWind(Combat $combat, Character $character, int $currentHp, int $maxHp): array
    {
        $skillKeys = $this->skillService->getEquippedSkillKeys($character);

        if (! in_array('second_wind', $skillKeys, true)) {
            return ['heal' => 0, 'used' => false, 'labels' => []];
        }

        if ($combat->combat_state['second_wind_used'] ?? false) {
            return ['heal' => 0, 'used' => false, 'labels' => []];
        }

        if ($currentHp <= 0 || $maxHp <= 0) {
            return ['heal' => 0, 'used' => false, 'labels' => []];
        }

        $effect = config('skills.effects.second_wind');
        $threshold = (float) ($effect['hp_threshold_percent'] ?? 10) / 100;

        if ($currentHp / $maxHp > $threshold) {
            return ['heal' => 0, 'used' => false, 'labels' => []];
        }

        $heal = (int) floor($maxHp * ((float) ($effect['heal_percent'] ?? 20) / 100));
        $heal = min($heal, $maxHp - $currentHp);

        if ($heal <= 0) {
            return ['heal' => 0, 'used' => false, 'labels' => []];
        }

        return ['heal' => $heal, 'used' => true, 'labels' => [$this->skillLabel('second_wind')]];
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
            'stone_skin' => 'Каменная кожа',
            'find_the_gap' => 'Поиск бреши',
            'second_wind' => 'Второе дыхание',
            'executioner' => 'Палач',
            default => $catalogKey,
        };
    }
}
