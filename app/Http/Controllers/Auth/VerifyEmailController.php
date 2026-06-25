<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($this->redirectAfterVerification($request));
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended($this->redirectAfterVerification($request));
    }

    private function redirectAfterVerification(EmailVerificationRequest $request): string
    {
        if ($request->user()->hasCompletedProfile()) {
            return route('dashboard', absolute: false).'?verified=1';
        }

        return route('profile.setup', absolute: false).'?verified=1';
    }
}
