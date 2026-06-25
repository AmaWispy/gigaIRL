<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && ! $user->hasCompletedProfile()
            && ! $request->routeIs(
                'profile.setup',
                'profile.setup.store',
                'logout',
                'admin.*',
                'verification.notice',
                'verification.verify',
                'verification.send',
            )
        ) {
            return redirect()->route('profile.setup');
        }

        return $next($request);
    }
}
