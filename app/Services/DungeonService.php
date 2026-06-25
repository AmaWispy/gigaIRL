<?php

namespace App\Services;

use App\Enums\CombatStatus;
use App\Enums\DungeonMonsterRole;
use App\Models\Character;
use App\Models\Combat;
use App\Models\Dungeon;
use App\Models\DungeonFloorState;
use App\Models\DungeonMonster;
use App\Models\DungeonRun;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\Location;
use App\Models\Monster;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DungeonService
{
    public function __construct(
        private EnergyService $energyService,
        private CharacterService $characterService,
        private InventoryService $inventoryService,
        private EquipmentService $equipmentService,
        private CombatService $combatService,
    ) {}

    public function getPassItem(Dungeon $dungeon): ?Item
    {
        return Item::where('type', 'dungeon_pass')
            ->where('stats->dungeon_key', $dungeon->catalog_key)
            ->first();
    }

    public function countPassItems(Character $character, Dungeon $dungeon): int
    {
        $item = $this->getPassItem($dungeon);

        if (! $item) {
            return 0;
        }

        return (int) $character->inventoryItems()
            ->where('item_id', $item->id)
            ->sum('quantity');
    }

    public function hasPassItem(Character $character, Dungeon $dungeon): bool
    {
        return $this->countPassItems($character, $dungeon) > 0;
    }

    public function consumePassItem(Character $character, Dungeon $dungeon): void
    {
        $item = $this->getPassItem($dungeon);

        if (! $item) {
            throw new InvalidArgumentException('Пропуск для этого данжа не настроен.');
        }

        $this->inventoryService->removeItem($character, $item, 1);
    }

    public function getActiveRun(Character $character): ?DungeonRun
    {
        return DungeonRun::with(['dungeon', 'floorStates.monster'])
            ->where('character_id', $character->id)
            ->where('is_active', true)
            ->first();
    }

    public function getDungeonForLocation(Location $location): ?Dungeon
    {
        if ($location->type !== 'dungeon') {
            return null;
        }

        return Dungeon::where('location_id', $location->id)->first();
    }

    public function getDungeonAtCharacterLocation(Character $character): ?Dungeon
    {
        $location = $character->currentLocation;

        if (! $location) {
            return null;
        }

        return $this->getDungeonForLocation($location);
    }

    public function assertAtDungeonEntrance(Character $character, Dungeon $dungeon): void
    {
        if ($character->current_location_id !== $dungeon->location_id) {
            throw new InvalidArgumentException('Войти в данж можно только на карте, у входа в локацию.');
        }
    }

    public function abandonRunOnTravelAway(Character $character, DungeonRun $run): void
    {
        if (! $run->is_active) {
            return;
        }

        DB::transaction(function () use ($character, $run) {
            $this->syncCharacterHp($character, $run->character_hp);
            $run->update([
                'is_active' => false,
                'failed' => true,
                'completed_at' => now(),
            ]);
        });
    }

    public function canEnter(Character $character, Dungeon $dungeon, ?int $entranceDungeonId = null): bool
    {
        return $this->hasPassItem($character, $dungeon)
            || $entranceDungeonId === $dungeon->id;
    }

    public function startRun(Character $character, Dungeon $dungeon, bool $viaEntrance = false): DungeonRun
    {
        if ($this->getActiveRun($character)) {
            throw new InvalidArgumentException('У вас уже есть активный забег в данже.');
        }

        if (! $viaEntrance && ! $this->hasPassItem($character, $dungeon)) {
            throw new InvalidArgumentException('Нужен пропуск в данж в инвентаре.');
        }

        if ($character->power < $dungeon->min_power) {
            throw new InvalidArgumentException("Недостаточная мощь. Нужно минимум {$dungeon->min_power}.");
        }

        $this->assertAtDungeonEntrance($character, $dungeon);

        $entryEnergy = $dungeon->entry_energy;

        return DB::transaction(function () use ($character, $dungeon, $entryEnergy, $viaEntrance) {
            if (! $viaEntrance) {
                $this->consumePassItem($character, $dungeon);
            }

            $this->energyService->spend($character, $entryEnergy, 'dungeon_entry');

            $maxHp = $this->characterService->getEffectiveMaxHp($character);
            $startHp = min($character->hp, $maxHp);

            $run = DungeonRun::create([
                'character_id' => $character->id,
                'dungeon_id' => $dungeon->id,
                'current_floor' => 1,
                'character_hp' => max(1, $startHp),
                'is_active' => true,
            ]);

            $this->generateFloorStates($run, $dungeon);

            return $run->load(['dungeon', 'floorStates.monster']);
        });
    }

    private function generateFloorStates(DungeonRun $run, Dungeon $dungeon): void
    {
        $floorsTotal = $dungeon->floors_total;
        $rareFloor = $dungeon->rare_floor;
        $bossFloor = $dungeon->boss_floor;

        $normalMonsters = DungeonMonster::where('dungeon_id', $dungeon->id)
            ->where('role', DungeonMonsterRole::Normal)
            ->with('monster')
            ->get();

        $rareMonster = DungeonMonster::where('dungeon_id', $dungeon->id)
            ->where('role', DungeonMonsterRole::Rare)
            ->with('monster')
            ->first();

        $bossMonster = DungeonMonster::where('dungeon_id', $dungeon->id)
            ->where('role', DungeonMonsterRole::Boss)
            ->with('monster')
            ->first();

        if ($normalMonsters->isEmpty() || ! $rareMonster || ! $bossMonster) {
            throw new InvalidArgumentException('Данж не настроен: отсутствуют мобы.');
        }

        for ($floor = 1; $floor <= $floorsTotal; $floor++) {
            if ($floor === $bossFloor) {
                $role = DungeonMonsterRole::Boss;
                $monsterId = $bossMonster->monster_id;
            } elseif ($floor === $rareFloor) {
                $role = DungeonMonsterRole::Rare;
                $monsterId = $rareMonster->monster_id;
            } else {
                $role = DungeonMonsterRole::Normal;
                $monsterId = $normalMonsters->random()->monster_id;
            }

            DungeonFloorState::create([
                'dungeon_run_id' => $run->id,
                'floor' => $floor,
                'monster_id' => $monsterId,
                'mob_role' => $role,
                'has_resource_pile' => random_int(1, 100) <= $dungeon->resource_pile_chance,
                'has_treasure' => random_int(1, 100) <= $dungeon->treasure_chance,
            ]);
        }
    }

    public function advanceFloor(DungeonRun $run): DungeonRun
    {
        if (! $run->is_active) {
            throw new InvalidArgumentException('Забег уже завершён.');
        }

        $run->loadMissing(['dungeon', 'character']);
        $this->assertAtDungeonEntrance($run->character, $run->dungeon);

        $floorState = $run->currentFloorState();

        if (! $floorState || ! $floorState->mob_defeated) {
            throw new InvalidArgumentException('Сначала победите моба на этом этаже.');
        }

        if ($run->current_floor >= $run->dungeon->floors_total) {
            throw new InvalidArgumentException('Вы уже на последнем этаже.');
        }

        $floorEnergy = $run->dungeon->floor_energy;

        return DB::transaction(function () use ($run, $floorEnergy) {
            $this->energyService->spend($run->character, $floorEnergy, 'dungeon_floor');

            $run->increment('current_floor');

            return $run->fresh(['dungeon', 'floorStates.monster']);
        });
    }

    public function fightMob(Character $character, DungeonRun $run): Combat
    {
        if ($run->character_id !== $character->id || ! $run->is_active) {
            throw new InvalidArgumentException('Недействительный забег.');
        }

        $run->loadMissing('dungeon');
        $this->assertAtDungeonEntrance($character, $run->dungeon);

        $floorState = $run->currentFloorState();

        if (! $floorState || $floorState->mob_defeated) {
            throw new InvalidArgumentException('На этом этаже нет активного боя.');
        }

        $energyCost = $run->dungeon->combat_energy;
        $this->energyService->spend($character, $energyCost, 'dungeon_combat');

        $monster = $floorState->monster;

        return $this->combatService->startDungeonCombat($character, $monster, $run, $floorState);
    }

    public function claimResource(Character $character, DungeonRun $run): array
    {
        $floorState = $this->getClaimableFloorState($character, $run, 'resource');

        $energyCost = $run->dungeon->resource_energy;

        return DB::transaction(function () use ($character, $run, $floorState, $energyCost) {
            $this->energyService->spend($character, $energyCost, 'dungeon_resource');

            $loot = $this->rollResourcePileLoot($run->dungeon);

            foreach ($loot['items'] as $entry) {
                $this->inventoryService->addItem($character, $entry['item'], $entry['quantity']);
            }

            $floorState->update(['resource_claimed' => true]);

            return [
                'type' => 'resource',
                'message' => $loot['message'],
                'items' => array_map(fn ($e) => ['name' => $e['item']->name, 'quantity' => $e['quantity']], $loot['items']),
            ];
        });
    }

    public function claimTreasure(Character $character, DungeonRun $run): array
    {
        $floorState = $this->getClaimableFloorState($character, $run, 'treasure');

        $energyCost = $run->dungeon->treasure_energy;

        return DB::transaction(function () use ($character, $run, $floorState, $energyCost) {
            $this->energyService->spend($character, $energyCost, 'dungeon_treasure');

            $loot = $this->rollTreasureLoot($character, $run->dungeon);

            $character->increment('money', $loot['money']);
            $run->increment('run_money_earned', $loot['money']);

            $floorState->update(['treasure_claimed' => true]);

            return [
                'type' => 'treasure',
                'message' => $loot['message'],
                'money' => $loot['money'],
                'items' => $loot['items'],
            ];
        });
    }

    private function getClaimableFloorState(Character $character, DungeonRun $run, string $type): DungeonFloorState
    {
        if ($run->character_id !== $character->id || ! $run->is_active) {
            throw new InvalidArgumentException('Недействительный забег.');
        }

        $run->loadMissing('dungeon');
        $this->assertAtDungeonEntrance($character, $run->dungeon);

        $floorState = $run->currentFloorState();

        if (! $floorState) {
            throw new InvalidArgumentException('Этаж не найден.');
        }

        if ($type === 'resource') {
            if (! $floorState->has_resource_pile || $floorState->resource_claimed) {
                throw new InvalidArgumentException('Ресурсы на этом этаже недоступны.');
            }
        } else {
            if (! $floorState->has_treasure || $floorState->treasure_claimed) {
                throw new InvalidArgumentException('Сокровище на этом этаже недоступно.');
            }
        }

        return $floorState;
    }

    public function leaveDungeon(Character $character, DungeonRun $run): void
    {
        if ($run->character_id !== $character->id || ! $run->is_active) {
            throw new InvalidArgumentException('Недействительный забег.');
        }

        $floorState = $run->currentFloorState();

        if ($floorState && ! $floorState->mob_defeated) {
            throw new InvalidArgumentException('Покинуть данж можно только после победы над мобом этажа.');
        }

        DB::transaction(function () use ($character, $run) {
            $this->syncCharacterHp($character, $run->character_hp);
            $this->closeRun($run);
        });
    }

    public function useHealingPotion(Character $character, DungeonRun $run, InventoryItem $inventoryItem): DungeonRun
    {
        if ($run->character_id !== $character->id || ! $run->is_active) {
            throw new InvalidArgumentException('Недействительный забег.');
        }

        $this->assertAtDungeonEntrance($character, $run->dungeon);

        if ($inventoryItem->character_id !== $character->id) {
            throw new InvalidArgumentException('Это не ваш предмет.');
        }

        $item = $inventoryItem->item;

        if ($item->type !== 'consumable' || ! isset($item->stats['hp_restore'])) {
            throw new InvalidArgumentException('Этот предмет нельзя использовать для лечения.');
        }

        $maxHp = $this->characterService->getEffectiveMaxHp($character);

        if ($run->character_hp >= $maxHp) {
            throw new InvalidArgumentException('HP уже полное.');
        }

        return DB::transaction(function () use ($character, $run, $item, $maxHp) {
            $restore = (int) ($item->stats['hp_restore'] ?? config('game.potion.hp_restore'));
            $run->update([
                'character_hp' => min($run->character_hp + $restore, $maxHp),
            ]);

            $this->inventoryService->removeItem($character, $item, 1);

            return $run->fresh(['dungeon', 'floorStates.monster']);
        });
    }

    /**
     * @return list<array{id: int, name: string, quantity: int, hp_restore: int}>
     */
    public function getHealingPotions(Character $character): array
    {
        return $character->inventoryItems()
            ->with('item')
            ->whereHas('item', fn ($q) => $q
                ->where('type', 'consumable')
                ->whereNotNull('stats->hp_restore'))
            ->get()
            ->map(fn (InventoryItem $inv) => [
                'id' => $inv->id,
                'name' => $inv->item->name,
                'quantity' => $inv->quantity,
                'hp_restore' => (int) ($inv->item->stats['hp_restore'] ?? config('game.potion.hp_restore')),
            ])
            ->values()
            ->all();
    }

    public function handleCombatVictory(Combat $combat): Combat
    {
        return DB::transaction(function () use ($combat) {
            $run = DungeonRun::lockForUpdate()->findOrFail($combat->dungeon_run_id);
            $floorState = DungeonFloorState::findOrFail($combat->dungeon_floor_state_id);
            $monster = $combat->monster;
            $character = $combat->character;
            $dungeon = $run->dungeon;
            $role = $floorState->mob_role->value;

            $xp = (int) floor($monster->xp_reward * $this->getXpMultiplier($dungeon, $role));
            $money = (int) floor($monster->money_reward * $dungeon->mob_money_multiplier);

            $rewards = [
                'xp' => $xp,
                'money' => $money,
                'items' => [],
            ];

            $this->characterService->addXp($character, $xp);
            $character->increment('money', $money);

            $run->increment('run_xp_earned', $xp);
            $run->increment('run_money_earned', $money);

            $rewards['items'] = array_merge(
                $rewards['items'],
                $this->rollMobLoot($character, $dungeon, $role)
            );

            $run->update(['character_hp' => $combat->character_hp]);
            $floorState->update(['mob_defeated' => true]);

            if ($floorState->mob_role === DungeonMonsterRole::Boss && $run->current_floor === $dungeon->boss_floor) {
                $clearRewards = $this->completeRun($run, $character);
                $rewards['items'] = array_merge($rewards['items'], $clearRewards['items']);
                $rewards['xp'] += $clearRewards['bonus_xp'];
                $rewards['money'] += $clearRewards['bonus_money'];
                $rewards['cleared'] = true;
            }

            $combat->update([
                'status' => CombatStatus::Won,
                'rewards' => $rewards,
            ]);

            return $combat->fresh(['rounds', 'monster']);
        });
    }

    public function handleCombatDefeat(Combat $combat): Combat
    {
        return DB::transaction(function () use ($combat) {
            $run = DungeonRun::lockForUpdate()->findOrFail($combat->dungeon_run_id);
            $character = $combat->character;

            $this->failRunOnDeath($character, $run);

            $combat->update([
                'status' => CombatStatus::Lost,
                'character_hp' => 0,
            ]);

            return $combat->fresh(['rounds', 'monster']);
        });
    }

    private function failRunOnDeath(Character $character, DungeonRun $run): void
    {
        if (! $run->is_active) {
            return;
        }

        $this->syncCharacterHp($character, 1);
        $run->update([
            'is_active' => false,
            'failed' => true,
            'completed_at' => now(),
        ]);

        $this->returnCharacterToCity($character);
    }

    private function returnCharacterToCity(Character $character): void
    {
        $city = Location::where('type', 'city')->first();

        if (! $city) {
            return;
        }

        $character->update(['current_location_id' => $city->id]);
        session()->forget('dungeon_entrance_id');
        app(ExplorationService::class)->clearActiveSessions($character);
    }

    /**
     * @return array{bonus_xp: int, bonus_money: int, items: list<array{name: string, quantity: int}>}
     */
    private function completeRun(DungeonRun $run, Character $character): array
    {
        $dungeon = $run->dungeon;
        $bonusXp = (int) floor($run->run_xp_earned * $dungeon->clear_bonus_xp_percent / 100);
        $bonusMoney = $dungeon->clear_bonus_money;

        $items = [];

        if ($bonusXp > 0) {
            $this->characterService->addXp($character, $bonusXp);
        }

        if ($bonusMoney > 0) {
            $character->increment('money', $bonusMoney);
            $run->increment('run_money_earned', $bonusMoney);
        }

        $seal = Item::where('catalog_key', 'explorer_seal')->first();

        if ($seal && $dungeon->explorer_seal_on_clear) {
            $qty = $dungeon->explorer_seal_on_clear;
            $this->inventoryService->addItem($character, $seal, $qty);
            $items[] = ['name' => $seal->name, 'quantity' => $qty];
        }

        $this->syncCharacterHp($character, $run->character_hp);
        $this->closeRun($run, completed: true);

        return [
            'bonus_xp' => $bonusXp,
            'bonus_money' => $bonusMoney,
            'items' => $items,
        ];
    }

    private function closeRun(DungeonRun $run, bool $completed = false): void
    {
        $run->update([
            'is_active' => false,
            'completed' => $completed,
            'completed_at' => now(),
        ]);
    }

    private function syncCharacterHp(Character $character, int $hp): void
    {
        $maxHp = $this->characterService->getEffectiveMaxHp($character);
        $character->update(['hp' => max(1, min($hp, $maxHp))]);
    }

    private function getXpMultiplier(Dungeon $dungeon, string $role): float
    {
        return $dungeon->xpMultiplierForRole($role);
    }

    /**
     * @return list<array{name: string, quantity: int}>
     */
    private function rollMobLoot(Character $character, Dungeon $dungeon, string $role): array
    {
        $items = [];
        $dungeon->loadMissing('lootRules');

        $potionChance = $dungeon->lootChance($role, 'potion');
        $equipChance = $dungeon->lootChance($role, 'set_equipment');

        $potion = Item::where('catalog_key', 'healing_potion')->first();

        if ($potion && random_int(1, 100) <= $potionChance) {
            $this->inventoryService->addItem($character, $potion, 1);
            $items[] = ['name' => $potion->name, 'quantity' => 1];
        }

        if (random_int(1, 100) <= $equipChance) {
            $equip = $this->rollSetEquipment($character, $dungeon);

            if ($equip) {
                $items[] = $equip;
            }
        }

        if ($role === 'boss' && random_int(1, 100) <= $dungeon->lootChance('boss', 'set_equipment_extra')) {
            $equip = $this->rollSetEquipment($character, $dungeon);

            if ($equip) {
                $items[] = $equip;
            }
        }

        if (random_int(1, 100) <= $dungeon->rare_resource_from_mob_chance) {
            $rare = $this->rollRareResource($dungeon);

            if ($rare) {
                $this->inventoryService->addItem($character, $rare, 1);
                $items[] = ['name' => $rare->name, 'quantity' => 1];
            }
        }

        return $items;
    }

    /**
     * @return array{items: list<array{item: Item, quantity: int}>, message: string}
     */
    private function rollResourcePileLoot(Dungeon $dungeon): array
    {
        $poolItems = $dungeon->resourceItemsForPool('common');
        $lootItems = [];
        $names = [];

        $count = random_int($dungeon->resource_quantity_min, $dungeon->resource_quantity_max);

        for ($i = 0; $i < $count; $i++) {
            $item = $poolItems->isNotEmpty() ? $poolItems->random() : null;

            if (! $item) {
                continue;
            }

            $lootItems[] = ['item' => $item, 'quantity' => 1];
            $names[] = $item->name;
        }

        if (random_int(1, 100) <= $dungeon->rare_resource_from_pile_chance) {
            $rare = $this->rollRareResource($dungeon);

            if ($rare) {
                $lootItems[] = ['item' => $rare, 'quantity' => 1];
                $names[] = $rare->name;
            }
        }

        $message = $names !== []
            ? 'Собрано: '.implode(', ', $names)
            : 'Ничего не найдено.';

        return ['items' => $lootItems, 'message' => $message];
    }

    /**
     * @return array{money: int, message: string, items: list<array{name: string, quantity: int}>}
     */
    private function rollTreasureLoot(Character $character, Dungeon $dungeon): array
    {
        $money = random_int($dungeon->treasure_money_min, $dungeon->treasure_money_max);
        $items = [];

        $dungeon->loadMissing('lootRules');

        $potion = Item::where('catalog_key', 'healing_potion')->first();

        if ($potion && random_int(1, 100) <= $dungeon->lootChance('treasure', 'potion')) {
            $this->inventoryService->addItem($character, $potion, 1);
            $items[] = ['name' => $potion->name, 'quantity' => 1];
        }

        if (random_int(1, 100) <= $dungeon->lootChance('treasure', 'set_equipment')) {
            $equip = $this->rollSetEquipment($character, $dungeon);

            if ($equip) {
                $items[] = $equip;
            }
        }

        $seal = Item::where('catalog_key', 'craftsman_seal')->first();
        $sphere = Item::where('catalog_key', 'transformation_sphere')->first();

        if ($seal && random_int(1, 100) <= $dungeon->lootChance('treasure', 'craftsman_seal')) {
            $this->inventoryService->addItem($character, $seal, 1);
            $items[] = ['name' => $seal->name, 'quantity' => 1];
        }

        if ($sphere && random_int(1, 100) <= $dungeon->lootChance('treasure', 'transformation_sphere')) {
            $this->inventoryService->addItem($character, $sphere, 1);
            $items[] = ['name' => $sphere->name, 'quantity' => 1];
        }

        $message = "Сокровище: +{$money} 💰";

        if ($items !== []) {
            $message .= ' · '.implode(', ', array_map(fn ($i) => $i['name'], $items));
        }

        return ['money' => $money, 'message' => $message, 'items' => $items];
    }

    /**
     * @return array{name: string, quantity: int}|null
     */
    private function rollSetEquipment(Character $character, Dungeon $dungeon): ?array
    {
        $setItems = Item::where('set_key', $dungeon->set_key)
            ->where('type', 'equipment')
            ->get();

        if ($setItems->isEmpty()) {
            return null;
        }

        $weighted = $setItems->filter(fn (Item $item) => $item->slot !== 'weapon');
        $pool = $weighted->isNotEmpty() && random_int(1, 100) <= $dungeon->set_equipment_non_weapon_chance ? $weighted : $setItems;

        $item = $pool->random();
        $quality = $this->equipmentService->rollDungeonLootQuality();

        $this->inventoryService->addEquipment($character, $item, [
            'quality' => $quality,
            'source' => 'dungeon',
            'level' => $dungeon->item_level,
        ]);

        return ['name' => $item->name, 'quantity' => 1];
    }

    private function rollRareResource(Dungeon $dungeon): ?Item
    {
        $pool = $dungeon->resourceItemsForPool('rare');

        if ($pool->isEmpty()) {
            return null;
        }

        return $pool->random();
    }

    public function getDungeonForTier(int $tier): ?Dungeon
    {
        return Dungeon::where('tier', $tier)->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function runToArray(DungeonRun $run): array
    {
        $run->loadMissing(['dungeon', 'floorStates.monster']);

        $currentFloor = $run->floorStates->firstWhere('floor', $run->current_floor);

        return [
            'id' => $run->id,
            'current_floor' => $run->current_floor,
            'floors_total' => $run->dungeon->floors_total,
            'character_hp' => $run->character_hp,
            'run_xp_earned' => $run->run_xp_earned,
            'run_money_earned' => $run->run_money_earned,
            'is_active' => $run->is_active,
            'completed' => $run->completed,
            'failed' => $run->failed,
            'dungeon' => [
                'id' => $run->dungeon->id,
                'name' => $run->dungeon->name,
                'tier' => $run->dungeon->tier,
                'min_power' => $run->dungeon->min_power,
            ],
            'current_floor_state' => $currentFloor ? $this->floorStateToArray($currentFloor) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function floorStateToArray(DungeonFloorState $state): array
    {
        return [
            'floor' => $state->floor,
            'mob_role' => $state->mob_role->value,
            'mob_defeated' => $state->mob_defeated,
            'monster_name' => $state->monster->name,
            'has_resource_pile' => $state->has_resource_pile,
            'resource_claimed' => $state->resource_claimed,
            'has_treasure' => $state->has_treasure,
            'treasure_claimed' => $state->treasure_claimed,
        ];
    }
}
