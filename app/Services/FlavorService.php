<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Item;

class FlavorService
{
    public function randomLookQuip(): string
    {
        $quips = config('game_flavor.look_around_quips');

        return $quips[array_rand($quips)];
    }

    public function merchantGreeting(string $poiType): ?string
    {
        return config("game_flavor.merchant_greetings.{$poiType}");
    }

    public function randomCombatQuip(string $tier): ?string
    {
        $quips = config("game_flavor.combat_quips.{$tier}")
            ?? config('game_flavor.combat_quips.normal');

        if (empty($quips)) {
            return null;
        }

        return $quips[array_rand($quips)];
    }

    public function rollExplorationEvent(Character $character): ?array
    {
        foreach (config('game_flavor.random_events') as $event) {
            if (random_int(1, 100) > ($event['chance'] ?? 0)) {
                continue;
            }

            $this->applyEventEffect($character, $event);

            return [
                'message' => $event['message'],
                'effect' => $event['effect'] ?? 'none',
            ];
        }

        return null;
    }

    private function applyEventEffect(Character $character, array $event): void
    {
        $character->refresh();

        match ($event['effect'] ?? 'none') {
            'energy' => $character->update([
                'energy' => max(0, $character->energy + ($event['amount'] ?? 0)),
            ]),
            'money' => $character->update([
                'money' => max(0, $character->money + ($event['amount'] ?? 0)),
            ]),
            'item' => $this->grantItemByName($character, $event['item_name'] ?? '', $event['quantity'] ?? 1),
            default => null,
        };
    }

    private function grantItemByName(Character $character, string $name, int $quantity): void
    {
        if ($name === '') {
            return;
        }

        $item = Item::where('name', $name)->first();

        if ($item) {
            app(InventoryService::class)->addItem($character, $item, $quantity);
        }
    }
}
