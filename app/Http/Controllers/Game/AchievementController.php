<?php

namespace App\Http\Controllers\Game;

use App\Enums\AchievementFrequency;
use App\Enums\AchievementType;
use App\Http\Controllers\Controller;
use App\Models\AchievementTemplate;
use App\Services\AchievementService;
use App\Services\CharacterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AchievementController extends Controller
{
    public function index(Request $request, AchievementService $achievementService, CharacterService $characterService): Response
    {
        $user = $request->user();
        $character = $user->character;

        $defaults = AchievementTemplate::where('is_default', true)
            ->where('type', AchievementType::Routine)
            ->get();

        $userRoutine = AchievementTemplate::where('user_id', $user->id)
            ->where('type', AchievementType::Routine)
            ->where('is_default', false)
            ->get();

        $userRare = AchievementTemplate::where('user_id', $user->id)
            ->where('type', AchievementType::Rare)
            ->get();

        $allTemplates = $defaults->concat($userRoutine)->concat($userRare);

        return Inertia::render('Game/Achievements/Index', [
            'character' => $characterService->toArray($character),
            'defaults' => $defaults,
            'userRoutine' => $userRoutine,
            'userRare' => $userRare,
            'todayCompletions' => $achievementService->getTodayCompletions($character),
            'unavailableTemplateIds' => $achievementService->getUnavailableTemplateIds($character, $allTemplates),
            'financialRates' => config('game.financial_rates'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Game/Achievements/Create', [
            'frequencies' => collect(AchievementFrequency::cases())
                ->map(fn ($f) => ['value' => $f->value, 'label' => $this->frequencyLabel($f)])
                ->values(),
        ]);
    }

    public function store(Request $request, AchievementService $achievementService): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:routine,rare',
            'title' => 'required|string|max:255',
            'reward_points' => 'required|integer|min:1|max:100',
            'frequency' => 'required|in:none,daily,weekly,monthly,once',
        ]);

        $character = $request->user()->character;

        $achievementService->createTemplate($character, [
            'type' => $validated['type'] === 'rare' ? AchievementType::Rare : AchievementType::Routine,
            'title' => $validated['title'],
            'reward_points' => $validated['reward_points'],
            'frequency' => $validated['type'] === 'rare'
                ? AchievementFrequency::Once
                : AchievementFrequency::from($validated['frequency']),
        ]);

        return redirect()->route('achievements.index');
    }

    public function completeFinancial(Request $request, AchievementService $achievementService): RedirectResponse
    {
        $validated = $request->validate([
            'amount_rubles' => 'required|integer|min:1',
            'is_primary_income' => 'required|boolean',
        ]);

        try {
            $achievementService->completeFinancial(
                $request->user()->character,
                $validated['amount_rubles'],
                $validated['is_primary_income']
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['amount_rubles' => $e->getMessage()]);
        }

        return back()->with('success', 'Энергия начислена!');
    }

    public function complete(Request $request, AchievementTemplate $template, AchievementService $achievementService): RedirectResponse
    {
        try {
            $achievementService->completeTemplate($request->user()->character, $template);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['achievement' => $e->getMessage()]);
        }

        return back()->with('success', 'Задача выполнена!');
    }

    private function frequencyLabel(AchievementFrequency $frequency): string
    {
        return match ($frequency) {
            AchievementFrequency::Daily => 'Раз в день',
            AchievementFrequency::Weekly => 'Раз в неделю',
            AchievementFrequency::Monthly => 'Раз в месяц',
            AchievementFrequency::Once => 'Один раз',
            AchievementFrequency::None => 'Без ограничений',
            default => $frequency->value,
        };
    }
}
