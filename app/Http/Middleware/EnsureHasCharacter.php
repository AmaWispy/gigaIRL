<?php

namespace App\Http\Middleware;

use App\Services\CharacterService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasCharacter
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->hasCompletedProfile()
            && ! $user->character
            && ! $request->routeIs('profile.setup', 'profile.setup.store', 'logout')
        ) {
            app(CharacterService::class)->createForUser($user);
            $user->unsetRelation('character');
        }

        return $next($request);
    }
}
