<?php

namespace App\Services;

use App\Enums\AchievementFrequency;
use App\Enums\AchievementType;
use App\Models\AchievementCompletion;
use App\Models\AchievementTemplate;
use App\Models\Character;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AchievementService
{
    public function __construct(
        private EnergyService $energyService,
    ) {}

    public function completeFinancial(Character $character, int $amountRubles, bool $isPrimaryIncome): AchievementCompletion
    {
        if ($amountRubles <= 0) {
            throw new InvalidArgumentException('Сумма должна быть больше нуля.');
        }

        $rate = $isPrimaryIncome
            ? config('game.financial_rates.primary')
            : config('game.financial_rates.secondary');

        $points = (int) floor($amountRubles / $rate);

        if ($points < 1) {
            throw new InvalidArgumentException("Минимум {$rate} рублей для получения 1 очка энергии.");
        }

        return DB::transaction(function () use ($character, $amountRubles, $isPrimaryIncome, $points) {
            $template = AchievementTemplate::firstOrCreate(
                [
                    'user_id' => $character->user_id,
                    'type' => AchievementType::Financial,
                    'title' => $isPrimaryIncome ? 'Основной доход' : 'Дополнительный доход',
                ],
                [
                    'reward_points' => 0,
                    'frequency' => AchievementFrequency::None,
                    'financial_rate' => $isPrimaryIncome
                        ? config('game.financial_rates.primary')
                        : config('game.financial_rates.secondary'),
                    'is_primary_income' => $isPrimaryIncome,
                ]
            );

            $completion = AchievementCompletion::create([
                'character_id' => $character->id,
                'achievement_template_id' => $template->id,
                'completed_at' => $this->gameNow()->utc(),
                'metadata' => [
                    'amount_rubles' => $amountRubles,
                    'is_primary_income' => $isPrimaryIncome,
                    'points_earned' => $points,
                ],
            ]);

            $this->energyService->add(
                $character,
                $points,
                'achievement_financial',
                AchievementCompletion::class,
                $completion->id
            );

            return $completion;
        });
    }

    public function completeTemplate(Character $character, AchievementTemplate $template): AchievementCompletion
    {
        $this->assertCanComplete($character, $template);

        return DB::transaction(function () use ($character, $template) {
            $this->assertCanComplete($character, $template);

            $points = $this->resolveRewardPoints($character, $template);

            $completion = AchievementCompletion::create([
                'character_id' => $character->id,
                'achievement_template_id' => $template->id,
                'completed_at' => $this->gameNow()->utc(),
                'metadata' => ['points_earned' => $points],
            ]);

            $this->energyService->add(
                $character,
                $points,
                'achievement_'.$template->type->value,
                AchievementCompletion::class,
                $completion->id
            );

            return $completion;
        });
    }

    public function createTemplate(Character $character, array $data): AchievementTemplate
    {
        return AchievementTemplate::create([
            'user_id' => $character->user_id,
            'type' => $data['type'],
            'title' => $data['title'],
            'reward_points' => $data['reward_points'],
            'frequency' => $data['frequency'],
            'is_default' => false,
            'difficulty' => $data['difficulty'] ?? null,
        ]);
    }

    public function assertCanComplete(Character $character, AchievementTemplate $template): void
    {
        if ($template->type === AchievementType::Financial) {
            throw new InvalidArgumentException('Финансовые достижения завершаются отдельно.');
        }

        if ($template->user_id !== null && $template->user_id !== $character->user_id) {
            throw new InvalidArgumentException('Это не ваша задача.');
        }

        if ($template->frequency === AchievementFrequency::Once) {
            $exists = AchievementCompletion::where('character_id', $character->id)
                ->where('achievement_template_id', $template->id)
                ->exists();

            if ($exists) {
                throw new InvalidArgumentException('Эта задача уже выполнена.');
            }
        } elseif ($this->frequencyValue($template) !== AchievementFrequency::None->value) {
            [$periodStart, $periodEnd] = $this->getPeriodBounds(
                AchievementFrequency::from($this->frequencyValue($template))
            );

            $exists = AchievementCompletion::where('character_id', $character->id)
                ->where('achievement_template_id', $template->id)
                ->whereBetween('completed_at', [$periodStart, $periodEnd])
                ->exists();

            if ($exists) {
                throw new InvalidArgumentException('Лимит выполнения для этого периода исчерпан.');
            }
        }
    }

    public function getCompletedTemplateIdsForToday(Character $character): array
    {
        [$periodStart, $periodEnd] = $this->getPeriodBounds(AchievementFrequency::Daily);

        return AchievementCompletion::where('character_id', $character->id)
            ->whereBetween('completed_at', [$periodStart, $periodEnd])
            ->pluck('achievement_template_id')
            ->all();
    }

    /**
     * @param  iterable<AchievementTemplate>  $templates
     * @return list<int>
     */
    public function getUnavailableTemplateIds(Character $character, iterable $templates): array
    {
        $ids = [];

        foreach ($templates as $template) {
            try {
                $this->assertCanComplete($character, $template);
            } catch (InvalidArgumentException) {
                $ids[] = $template->id;
            }
        }

        return $ids;
    }

    private function frequencyValue(AchievementTemplate $template): string
    {
        $frequency = $template->frequency;

        return $frequency instanceof AchievementFrequency ? $frequency->value : (string) $frequency;
    }

    private function gameNow(): Carbon
    {
        return now(config('game.timezone'));
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function getPeriodBounds(AchievementFrequency $frequency): array
    {
        $now = $this->gameNow();

        return match ($frequency) {
            AchievementFrequency::Daily => [
                $now->copy()->startOfDay()->utc(),
                $now->copy()->endOfDay()->utc(),
            ],
            AchievementFrequency::Weekly => [
                $now->copy()->startOfWeek()->utc(),
                $now->copy()->endOfWeek()->utc(),
            ],
            AchievementFrequency::Monthly => [
                $now->copy()->startOfMonth()->utc(),
                $now->copy()->endOfMonth()->utc(),
            ],
            default => [
                $now->copy()->subYears(100)->utc(),
                $now->copy()->addYears(100)->utc(),
            ],
        };
    }

    private function resolveRewardPoints(Character $character, AchievementTemplate $template): int
    {
        if ($template->is_default && $template->difficulty) {
            return $template->difficulty;
        }

        return $template->reward_points;
    }

    public function getTodayCompletions(Character $character): array
    {
        [$periodStart, $periodEnd] = $this->getPeriodBounds(AchievementFrequency::Daily);

        return AchievementCompletion::with('template')
            ->where('character_id', $character->id)
            ->whereBetween('completed_at', [$periodStart, $periodEnd])
            ->orderByDesc('completed_at')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->template->title,
                'type' => $c->template->type->value,
                'completed_at' => $c->completed_at->toIso8601String(),
                'metadata' => $c->metadata,
            ])
            ->all();
    }
}
