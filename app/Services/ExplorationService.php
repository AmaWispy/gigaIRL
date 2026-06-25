<?php

namespace App\Services;

use App\Enums\ExplorationActionType;
use App\Models\Character;
use App\Models\Dungeon;
use App\Models\ExplorationAction;
use App\Models\ExplorationSession;
use App\Models\Item;
use App\Models\Monster;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExplorationService
{
    public function __construct(
        private EnergyService $energyService,
        private CombatService $combatService,
        private InventoryService $inventoryService,
        private FlavorService $flavorService,
    ) {}

    public function getActiveSession(Character $character): ?ExplorationSession
    {
        $session = ExplorationSession::with(['actions', 'location'])
            ->where('character_id', $character->id)
            ->where('is_active', true)
            ->first();

        if (! $session) {
            return null;
        }

        if ($session->location_id !== $character->current_location_id) {
            $this->clearActiveSessions($character);

            return null;
        }

        return $session;
    }

    public function clearActiveSessions(Character $character): void
    {
        ExplorationSession::where('character_id', $character->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    public function lookAround(Character $character): ExplorationSession
    {
        $location = $character->currentLocation;

        if (! $location || $location->is_safe) {
            throw new InvalidArgumentException('Осмотреться можно только в опасной зоне.');
        }

        $energyCost = config('game.exploration.look_around_energy');

        return DB::transaction(function () use ($character, $location, $energyCost) {
            ExplorationSession::where('character_id', $character->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $this->energyService->spend($character, $energyCost, 'exploration_look');

            $session = ExplorationSession::create([
                'character_id' => $character->id,
                'location_id' => $location->id,
                'is_active' => true,
            ]);

            if ($character->power < $location->min_power && random_int(1, 100) <= config('game.exploration.ambush_chance') * 100) {
                $monster = Monster::where('location_id', $location->id)
                    ->where('tier', 'normal')
                    ->inRandomOrder()
                    ->first();

                if ($monster) {
                    ExplorationAction::create([
                        'exploration_session_id' => $session->id,
                        'action_type' => ExplorationActionType::Monster,
                        'energy_cost' => config('game.exploration.monster_energy'),
                        'payload' => ['monster_id' => $monster->id, 'ambush' => true],
                        'is_resolved' => false,
                    ]);

                    return $session->load('actions');
                }
            }

            $actionCount = random_int(
                config('game.exploration.actions_min'),
                config('game.exploration.actions_max')
            );

            foreach ($this->rollExplorationActions($location, $actionCount) as $action) {
                ExplorationAction::create([
                    'exploration_session_id' => $session->id,
                    'action_type' => $action['type'],
                    'energy_cost' => $action['energy_cost'],
                    'payload' => $action['payload'],
                    'is_resolved' => false,
                ]);
            }

            $this->maybeInjectDungeonEntrance($session, $location);

            return $session->load('actions');
        });
    }

    /**
     * @return list<array{type: ExplorationActionType, energy_cost: int, payload: array}>
     */
    public function rollExplorationActions($location, int $count): array
    {
        $templates = $this->getActionTemplates($location);

        if ($templates === []) {
            return [];
        }

        $actions = [];

        for ($i = 0; $i < $count; $i++) {
            $template = $templates[array_rand($templates)];
            $actions[] = $this->instantiateAction($template, $location);
        }

        return $actions;
    }

    /**
     * @return list<array{kind: string, type: ExplorationActionType, energy_cost: int, tier?: string}>
     */
    private function getActionTemplates($location): array
    {
        $exploration = config('game.exploration');
        $templates = [];

        if (Monster::where('location_id', $location->id)->where('tier', 'normal')->exists()) {
            $templates[] = [
                'kind' => 'monster',
                'type' => ExplorationActionType::Monster,
                'energy_cost' => $exploration['monster_energy'],
                'tier' => 'normal',
            ];
        }

        $templates[] = [
            'kind' => 'gather',
            'type' => ExplorationActionType::Gather,
            'energy_cost' => $exploration['gather_energy'],
        ];

        $templates[] = [
            'kind' => 'treasure',
            'type' => ExplorationActionType::Treasure,
            'energy_cost' => $exploration['treasure_energy'],
        ];

        $templates[] = [
            'kind' => 'resource_cache',
            'type' => ExplorationActionType::ResourceCache,
            'energy_cost' => $exploration['resource_cache_energy'],
        ];

        if (Monster::where('location_id', $location->id)->where('tier', 'rare')->exists()) {
            $templates[] = [
                'kind' => 'monster',
                'type' => ExplorationActionType::RareMonster,
                'energy_cost' => $exploration['rare_monster_energy'],
                'tier' => 'rare',
            ];
        }

        if (Monster::where('location_id', $location->id)->where('tier', 'boss')->exists()) {
            $templates[] = [
                'kind' => 'monster',
                'type' => ExplorationActionType::Boss,
                'energy_cost' => $exploration['boss_energy'],
                'tier' => 'boss',
            ];
        }

        return $templates;
    }

    /**
     * @param  array{kind: string, type: ExplorationActionType, energy_cost: int, tier?: string}  $template
     * @return array{type: ExplorationActionType, energy_cost: int, payload: array}
     */
    private function instantiateAction(array $template, $location): array
    {
        $payload = [];

        if ($template['kind'] === 'monster') {
            $monster = Monster::where('location_id', $location->id)
                ->where('tier', $template['tier'])
                ->inRandomOrder()
                ->first();

            $payload = ['monster_id' => $monster->id];
        }

        return [
            'type' => $template['type'],
            'energy_cost' => $template['energy_cost'],
            'payload' => $payload,
        ];
    }

    private function maybeInjectDungeonEntrance(ExplorationSession $session, $location): void
    {
        $tier = $location->world_tier;

        if (! $tier) {
            return;
        }

        $dungeon = Dungeon::where('tier', $tier)->first();

        if (! $dungeon) {
            return;
        }

        if (! $dungeon->exploration_entrance_chance || random_int(1, 100) > $dungeon->exploration_entrance_chance) {
            return;
        }

        ExplorationAction::create([
            'exploration_session_id' => $session->id,
            'action_type' => ExplorationActionType::DungeonEntrance,
            'energy_cost' => $dungeon->exploration_entrance_energy,
            'payload' => [
                'dungeon_id' => $dungeon->id,
                'dungeon_name' => $dungeon->name,
            ],
            'is_resolved' => false,
        ]);
    }

    private function discoverDungeonEntrance(Character $character, ExplorationAction $action): array
    {
        $dungeonId = $action->payload['dungeon_id'] ?? null;
        $dungeon = Dungeon::find($dungeonId);

        if (! $dungeon) {
            return ['type' => 'dungeon_entrance', 'message' => 'Вход завален обломками.'];
        }

        session(['dungeon_entrance_id' => $dungeon->id]);

        return [
            'type' => 'dungeon_entrance',
            'dungeon_id' => $dungeon->id,
            'message' => "Найден вход в «{$dungeon->name}». Дойдите до локации на карте и войдите (⚡ {$dungeon->entry_energy} на пороге).",
        ];
    }

    public function resolveAction(Character $character, ExplorationAction $action): array
    {
        if ($action->is_resolved) {
            throw new InvalidArgumentException('Действие уже выполнено.');
        }

        if ($action->session->character_id !== $character->id) {
            throw new InvalidArgumentException('Это не ваше действие.');
        }

        return DB::transaction(function () use ($character, $action) {
            $this->energyService->spend($character, $action->energy_cost, 'exploration_action');

            $result = match ($action->action_type) {
                ExplorationActionType::Monster,
                ExplorationActionType::RareMonster,
                ExplorationActionType::Boss => $this->startMonsterEncounter($character, $action),
                ExplorationActionType::Gather,
                ExplorationActionType::ResourceCache => $this->gatherResources($character, $action),
                ExplorationActionType::Treasure => $this->findTreasure($character),
                ExplorationActionType::DungeonEntrance => $this->discoverDungeonEntrance($character, $action),
            };

            $action->update([
                'is_resolved' => true,
                'payload' => array_merge($action->payload ?? [], ['result' => $result]),
            ]);

            return $result;
        });
    }

    private function startMonsterEncounter(Character $character, ExplorationAction $action): array
    {
        $monsterId = $action->payload['monster_id'] ?? null;
        $monster = Monster::findOrFail($monsterId);

        $combat = $this->combatService->startCombat($character, $monster, spendEnergy: false);

        return ['type' => 'combat', 'combat_id' => $combat->id];
    }

    private function gatherResources(Character $character, ExplorationAction $action): array
    {
        $tier = $action->action_type === ExplorationActionType::ResourceCache ? 'blue' : 'green';
        $item = Item::where('type', 'resource')
            ->when($tier === 'green', fn ($q) => $q->where('tier', 'green'))
            ->when($tier === 'blue', fn ($q) => $q->whereIn('tier', ['green', 'blue']))
            ->inRandomOrder()
            ->first();

        if ($item) {
            $qty = $action->action_type === ExplorationActionType::ResourceCache ? 3 : 1;
            $this->inventoryService->addItem($character, $item, $qty);

            return ['type' => 'gather', 'item' => $item->name, 'quantity' => $qty, 'message' => "Получено: {$item->name} x{$qty}"];
        }

        return ['type' => 'gather', 'item' => null, 'message' => 'Ничего не найдено.'];
    }

    private function findTreasure(Character $character): array
    {
        $money = random_int(10, 50);
        $character->increment('money', $money);

        $extras = [];
        $seal = Item::where('catalog_key', 'craftsman_seal')->first();
        $sphere = Item::where('catalog_key', 'transformation_sphere')->first();

        if ($seal && random_int(1, 100) <= config('equipment.drop.craftsman_seal.treasure')) {
            $this->inventoryService->addItem($character, $seal, 1);
            $extras[] = $seal->name;
        }

        if ($sphere && random_int(1, 100) <= config('equipment.drop.transformation_sphere.treasure')) {
            $this->inventoryService->addItem($character, $sphere, 1);
            $extras[] = $sphere->name;
        }

        $message = "Найдено сокровище: +{$money} 💰";
        if ($extras !== []) {
            $message .= ' · '.implode(', ', $extras);
        }

        return ['type' => 'treasure', 'money' => $money, 'message' => $message];
    }

    public function formatExplorationResult(array $result): string
    {
        if (! empty($result['message'])) {
            return $result['message'];
        }

        return match ($result['type'] ?? '') {
            'combat' => 'Бой завершён.',
            'gather' => isset($result['item'])
                ? "Получено: {$result['item']} x".($result['quantity'] ?? 1)
                : 'Ничего не найдено.',
            'treasure' => 'Найдено сокровище: +'.($result['money'] ?? 0).' 💰',
            'dungeon_entrance' => $result['message'] ?? 'Найден вход в данж.',
            default => 'Действие выполнено.',
        };
    }
}
