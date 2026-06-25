<?php

namespace App\Http\Middleware;

use App\Admin\AdminLookupService;
use App\Admin\AdminResourceRegistry;
use App\Services\CharacterService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $character = null;

        if ($request->user()?->character) {
            $character = app(CharacterService::class)->toArray($request->user()->character);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'is_admin' => (bool) $request->user()?->isAdmin(),
            ],
            'character' => $character,
            'admin' => fn () => $request->user()?->isAdmin() ? [
                'navigation' => AdminResourceRegistry::navigation(),
                'lookups' => app(AdminLookupService::class)->options(),
            ] : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'warning' => fn () => $request->session()->get('warning'),
                'error' => fn () => $request->session()->get('error'),
                'flavor' => fn () => $request->session()->get('flavor'),
            ],
        ];
    }
}
