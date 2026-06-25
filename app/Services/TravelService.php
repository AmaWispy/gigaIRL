<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Item;
use App\Models\Location;
use App\Models\LocationConnection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TravelService
{
    public function __construct(
        private EnergyService $energyService,
        private InventoryService $inventoryService,
        private ExplorationService $explorationService,
        private CharacterService $characterService,
        private DungeonService $dungeonService,
    ) {}

    public function getAvailableDestinations(Character $character): array
    {
        if (! $character->current_location_id) {
            return [];
        }

        return LocationConnection::with('toLocation')
            ->where('from_location_id', $character->current_location_id)
            ->get()
            ->map(function (LocationConnection $conn) use ($character) {
                $destination = $conn->toLocation;
                $dungeon = $this->dungeonService->getDungeonForLocation($destination);
                $canTravel = true;

                if ($dungeon) {
                    $canTravel = $this->dungeonService->canEnter(
                        $character,
                        $dungeon,
                        session('dungeon_entrance_id')
                    );
                }

                return [
                    'id' => $conn->to_location_id,
                    'name' => $destination->name,
                    'type' => $destination->type,
                    'is_safe' => $destination->is_safe,
                    'energy_cost' => $conn->energy_cost,
                    'can_travel' => $canTravel,
                    'requires_dungeon_pass' => $dungeon !== null && ! $canTravel,
                ];
            })
            ->all();
    }

    public function travel(Character $character, Location $destination, bool $useTeleportStone = false): array
    {
        $connection = LocationConnection::where('from_location_id', $character->current_location_id)
            ->where('to_location_id', $destination->id)
            ->first();

        if (! $connection) {
            throw new InvalidArgumentException('Нельзя переместиться в эту локацию.');
        }

        $dungeon = $this->dungeonService->getDungeonForLocation($destination);

        if ($dungeon && ! $this->dungeonService->canEnter($character, $dungeon, session('dungeon_entrance_id'))) {
            throw new InvalidArgumentException('Нужен пропуск в данж. Купите его у гильдмастера в Старограде или найдите вход при исследовании.');
        }

        $dungeonRunAbandoned = false;

        return DB::transaction(function () use ($character, $destination, $connection, $useTeleportStone, &$dungeonRunAbandoned) {
            $currentLocation = $character->currentLocation;

            if ($currentLocation?->type === 'dungeon' && $currentLocation->id !== $destination->id) {
                $activeRun = $this->dungeonService->getActiveRun($character);

                if ($activeRun) {
                    $this->dungeonService->abandonRunOnTravelAway($character, $activeRun);
                    $dungeonRunAbandoned = true;
                }
            }

            if ($useTeleportStone) {
                $stone = Item::where('type', 'teleport_stone')->first();
                if ($stone) {
                    $this->inventoryService->removeItem($character, $stone, 1);
                } else {
                    throw new InvalidArgumentException('У вас нет камня перемещения.');
                }
            } else {
                $this->energyService->spend($character, $connection->energy_cost, 'travel');
            }

            $this->explorationService->clearActiveSessions($character);

            if ($destination->world_tier === null || $destination->is_safe) {
                session()->forget('dungeon_entrance_id');
            }

            $character->update(['current_location_id' => $destination->id]);

            return [
                'character' => $character->fresh(['currentLocation']),
                'dungeon_run_abandoned' => $dungeonRunAbandoned,
            ];
        });
    }

    public function restAtInn(Character $character): Character
    {
        $location = $character->currentLocation;

        if (! $location || ! $location->is_safe) {
            throw new InvalidArgumentException('Гостиница доступна только в безопасных зонах.');
        }

        $cost = config('game.inn.cost');
        $restorePercent = config('game.inn.hp_restore_percent');

        if ($character->money < $cost) {
            throw new InvalidArgumentException('Недостаточно денег для гостиницы.');
        }

        $effectiveMaxHp = $this->characterService->getEffectiveMaxHp($character);
        $restoreAmount = (int) floor($effectiveMaxHp * $restorePercent / 100);

        $character->money -= $cost;
        $character->hp = min($character->hp + $restoreAmount, $effectiveMaxHp);
        $character->save();

        return $character->fresh();
    }
}
