<?php

namespace App\Console\Commands;

use App\Models\Character;
use App\Services\CharacterService;
use Illuminate\Console\Command;

class ResetDailyHp extends Command
{
    protected $signature = 'game:reset-daily-hp';

    protected $description = 'Восстановить HP всех персонажей (суточный сброс)';

    public function handle(CharacterService $characterService): int
    {
        $count = 0;

        Character::chunk(100, function ($characters) use ($characterService, &$count) {
            foreach ($characters as $character) {
                $characterService->restoreDailyHp($character);
                $count++;
            }
        });

        $this->info("Восстановлено HP для {$count} персонажей.");

        return self::SUCCESS;
    }
}
