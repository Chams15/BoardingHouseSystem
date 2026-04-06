<?php

namespace App\Http\Middleware;

use App\Models\LeaseContract;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $hasTenantRoom = false;

        if ($user && strtolower((string) $user->role) === 'tenant') {
            $hasTenantRoom = LeaseContract::where('tenant_id', $user->user_id)
                ->whereNotNull('room_id')
                ->where(function ($query) {
                    $query->whereNull('contract_status')
                        ->orWhere('contract_status', '!=', 'Terminated');
                })
                ->exists();
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user?->load('tenantProfile'),
                'hasTenantRoom' => $hasTenantRoom,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
