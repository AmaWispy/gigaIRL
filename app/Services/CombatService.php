<?php

namespace App\Services;

use App\Enums\CombatStatus;
use App\Models\Character;
use App\Models\Combat;
use App\Models\CombatRound;
use App\Models\Item;
use App\Models\Monster;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CombatService
{
    public function __construct(
        private EnergyService $energyService,
        private CharacterService $characterService,
        private InventoryService $inventoryService,
        private EquipmentService $equipmentService,
        private CombatSkillService $combatSkillService,
        private CombatDamageService $combatDamageService,
    ) {}

    public function startCombat(Character $character, Monster $monster, bool $spendEnergy = true): Combat
    {
        $energyCost = $monster->energy_cost ?? config('game.combat.energy_cost');

        $combat = DB::transaction(function () use ($character, $monster, $energyCost, $spendEnergy) {
            if ($spendEnergy) {
                $this->energyService->spend($character, $energyCost, 'combat_start');
            }

            return Combat::create([
                'character_id' => $character->id,
                'monster_id' => $monster->id,
                'status' => CombatStatus::Active,
                'character_hp' => $character->hp,
                'monster_hp' => $monster->hp,
            ]);
        });

        return $this->runToCompletion($combat);
    }

    public function startDungeonCombat(
        Character $character,
        Monster $monster,
        \App\Models\DungeonRun $run,
        \App\Models\DungeonFloorState $floorState,
    ): Combat {
        $combat = DB::transaction(function () use ($character, $monster, $run, $floorState) {
            return Combat::create([
                'character_id' => $character->id,
                'monster_id' => $monster->id,
                'dungeon_run_id' => $run->id,
                'dungeon_floor_state_id' => $floorState->id,
                'status' => CombatStatus::Active,
                'character_hp' => $run->character_hp,
                'monster_hp' => $monster->hp,
            ]);
        });

        return $this->runToCompletion($combat);
    }

    public function runToCompletion(Combat $combat): Combat
    {
        $maxRounds = 100;

        while ($combat->status === CombatStatus::Active && $maxRounds-- > 0) {
            $combat = $this->executeRound($combat);
        }

        return $combat->fresh(['rounds', 'monster']);
    }

    /**
     * @return array{character: int, monster: int}
     */
    public function getStartingHp(Combat $combat): array
    {
        $combat->loadMissing(['rounds', 'monster']);

        if ($combat->rounds->isEmpty()) {
            return [
                'character' => $combat->character_hp,
                'monster' => $combat->monster_hp,
            ];
        }

        $characterHp = $combat->character_hp;
        $monsterHp = $combat->monster_hp;

        foreach ($combat->rounds->sortByDesc('round_number') as $round) {
            if ($round->actor === 'character') {
                $monsterHp += $round->damage;
            } elseif ($round->actor === 'monster') {
                $characterHp += $round->damage;
            }
        }

        return [
            'character' => $characterHp,
            'monster' => $combat->monster->hp,
        ];
    }

    public function executeRound(Combat $combat): Combat
    {
        if ($combat->status !== CombatStatus::Active) {
            throw new InvalidArgumentException('Бой уже завершён.');
        }

        return DB::transaction(function () use ($combat) {
            $combat->load(['character.equippedItems.inventoryItem.item', 'character.characterSkills.skill', 'monster', 'rounds']);
            $character = $combat->character;
            $monster = $combat->monster;
            $roundNumber = $combat->rounds()->count() + 1;

            $monsterHp = $combat->monster_hp;
            $charHp = $combat->character_hp;
            $attack = $this->characterService->getEffectiveStrength($character);
            $defense = $this->characterService->getEffectiveDefense($character);
            $skipMonsterTurn = false;
            $state = $combat->combat_state ?? [];

            if ($monsterHp > 0) {
                $attackResult = $this->combatSkillService->resolveCharacterAttack(
                    $combat,
                    $character,
                    $monster,
                    $attack,
                );

                $state['defense_reduction'] = $attackResult['defense_reduction'];
                $monsterHp = max(0, $monsterHp - $attackResult['damage']);

                if ($attackResult['heal'] > 0) {
                    $effectiveMaxHp = $this->characterService->getEffectiveMaxHp($character);
                    $charHp = min($effectiveMaxHp, $charHp + $attackResult['heal']);
                }

                $skipMonsterTurn = $attackResult['skip_monster_turn'];

                CombatRound::create([
                    'combat_id' => $combat->id,
                    'round_number' => $roundNumber,
                    'actor' => 'character',
                    'action' => 'attack',
                    'damage' => $attackResult['damage'],
                    'heal' => $attackResult['heal'],
                    'meta' => [
                        'skills' => $attackResult['labels'],
                        'blocked' => $attackResult['blocked'],
                    ],
                    'character_hp_after' => $charHp,
                    'monster_hp_after' => $monsterHp,
                ]);
                $roundNumber++;
            }

            if ($monsterHp > 0 && ! $skipMonsterTurn) {
                $mitigation = $this->combatSkillService->resolveIncomingMitigation($combat, $character);
                $armorResult = $this->combatDamageService->resolve($monster->attack, $defense + $mitigation['bonus_defense']);
                $monsterDamage = $armorResult['damage'];
                $blockedDamage = $armorResult['blocked'];
                $retribution = $this->combatSkillService->resolveMonsterAttackRetribution(
                    $combat,
                    $character,
                    $monsterDamage,
                );

                if ($retribution['reflect_damage'] > 0) {
                    $monsterHp = max(0, $monsterHp - $retribution['reflect_damage']);

                    CombatRound::create([
                        'combat_id' => $combat->id,
                        'round_number' => $roundNumber,
                        'actor' => 'character',
                        'action' => 'retribution',
                        'damage' => $retribution['reflect_damage'],
                        'meta' => ['skills' => $retribution['labels']],
                        'character_hp_after' => $charHp,
                        'monster_hp_after' => $monsterHp,
                    ]);
                    $roundNumber++;
                }

                if ($monsterHp > 0) {
                    $charHp = max(0, $charHp - $monsterDamage);

                    CombatRound::create([
                        'combat_id' => $combat->id,
                        'round_number' => $roundNumber,
                        'actor' => 'monster',
                        'action' => 'attack',
                        'damage' => $monsterDamage,
                        'meta' => ['blocked' => $blockedDamage, 'skills' => $mitigation['labels']],
                        'character_hp_after' => $charHp,
                        'monster_hp_after' => $monsterHp,
                    ]);
                    $roundNumber++;

                    if ($charHp > 0) {
                        $effectiveMaxHp = $this->characterService->getEffectiveMaxHp($character);
                        $secondWind = $this->combatSkillService->resolveSecondWind($combat, $character, $charHp, $effectiveMaxHp);

                        if ($secondWind['heal'] > 0) {
                            $charHp = min($effectiveMaxHp, $charHp + $secondWind['heal']);
                            $state['second_wind_used'] = true;

                            CombatRound::create([
                                'combat_id' => $combat->id,
                                'round_number' => $roundNumber,
                                'actor' => 'character',
                                'action' => 'second_wind',
                                'damage' => 0,
                                'heal' => $secondWind['heal'],
                                'meta' => ['skills' => $secondWind['labels']],
                                'character_hp_after' => $charHp,
                                'monster_hp_after' => $monsterHp,
                            ]);
                            $roundNumber++;
                        }
                    }
                }
            } elseif ($monsterHp > 0 && $skipMonsterTurn) {
                CombatRound::create([
                    'combat_id' => $combat->id,
                    'round_number' => $roundNumber,
                    'actor' => 'monster',
                    'action' => 'stunned',
                    'damage' => 0,
                    'meta' => ['message' => 'Противник оглушён и пропускает ход'],
                    'character_hp_after' => $charHp,
                    'monster_hp_after' => $monsterHp,
                ]);
            }

            $combat->update([
                'character_hp' => $charHp,
                'monster_hp' => $monsterHp,
                'combat_state' => $state,
            ]);

            if ($monsterHp <= 0) {
                return $this->endCombatVictory($combat);
            }

            if ($charHp <= 0) {
                return $this->endCombatDefeat($combat);
            }

            return $combat->fresh(['rounds', 'monster']);
        });
    }

    private function endCombatVictory(Combat $combat): Combat
    {
        if ($combat->dungeon_run_id) {
            return app(DungeonService::class)->handleCombatVictory($combat);
        }

        $monster = $combat->monster;
        $character = $combat->character;

        $rewards = [
            'xp' => $monster->xp_reward,
            'money' => $monster->money_reward,
            'items' => [],
        ];

        $loot = $monster->loot_table ?? [];
        foreach ($loot as $entry) {
            if (random_int(1, 100) <= ($entry['chance'] ?? 0)) {
                $item = Item::find($entry['item_id']);
                if ($item) {
                    $qty = $entry['quantity'] ?? 1;

                    if ($item->type === 'equipment') {
                        $quality = ($entry['equipment_quality'] ?? null) === 'random'
                            ? $this->equipmentService->rollDungeonLootQuality()
                            : ($entry['equipment_quality'] ?? 'white');

                        for ($i = 0; $i < $qty; $i++) {
                            $this->inventoryService->addEquipment($character, $item, [
                                'quality' => $quality,
                                'source' => $item->equipment_source ?? 'dungeon',
                                'level' => $item->item_level ?? 1,
                            ]);
                        }
                    } else {
                        $this->inventoryService->addItem($character, $item, $qty);
                    }

                    $rewards['items'][] = ['name' => $item->name, 'quantity' => $qty];
                }
            }
        }

        $character->update(['hp' => $combat->character_hp]);
        $character->increment('money', $monster->money_reward);
        $this->characterService->addXp($character, $monster->xp_reward);

        $combat->update([
            'status' => CombatStatus::Won,
            'rewards' => $rewards,
        ]);

        return $combat->fresh(['rounds', 'monster']);
    }

    private function endCombatDefeat(Combat $combat): Combat
    {
        if ($combat->dungeon_run_id) {
            return app(DungeonService::class)->handleCombatDefeat($combat);
        }

        $combat->character->update(['hp' => 1]);
        $combat->update(['status' => CombatStatus::Lost, 'character_hp' => 0]);

        return $combat->fresh(['rounds', 'monster']);
    }
}
