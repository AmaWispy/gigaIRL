<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CharacterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileSetupController extends Controller
{
    /**
     * Display the profile setup form.
     */
    public function create(Request $request): RedirectResponse|Response
    {
        if ($request->user()->hasCompletedProfile()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/ProfileSetup');
    }

    /**
     * Store nickname and status.
     */
    public function store(Request $request, CharacterService $characterService): RedirectResponse
    {
        if ($request->user()->hasCompletedProfile()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'nickname' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'alpha_dash',
                Rule::unique(User::class),
            ],
            'status' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->update($validated);

        if (! $user->character) {
            $characterService->createForUser($user);
        }

        return redirect()->route('dashboard');
    }
}
