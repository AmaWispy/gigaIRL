<?php

namespace App\Services;

use App\Models\Character;
use App\Models\EnergyTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EnergyService
{
    public function add(Character $character, int $amount, string $source, ?string $referenceType = null, ?int $referenceId = null): Character
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be positive.');
        }

        return DB::transaction(function () use ($character, $amount, $source, $referenceType, $referenceId) {
            $character->refresh();
            $character->increment('energy', $amount);

            EnergyTransaction::create([
                'character_id' => $character->id,
                'amount' => $amount,
                'source' => $source,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            return $character->fresh();
        });
    }

    public function spend(Character $character, int $amount, string $source, ?string $referenceType = null, ?int $referenceId = null): Character
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be positive.');
        }

        return DB::transaction(function () use ($character, $amount, $source, $referenceType, $referenceId) {
            $character->refresh();

            if ($character->energy < $amount) {
                throw new InvalidArgumentException('Недостаточно энергии.');
            }

            $character->decrement('energy', $amount);

            EnergyTransaction::create([
                'character_id' => $character->id,
                'amount' => -$amount,
                'source' => $source,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            return $character->fresh();
        });
    }

    public function hasEnough(Character $character, int $amount): bool
    {
        return $character->energy >= $amount;
    }
}
