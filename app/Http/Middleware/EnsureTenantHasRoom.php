<?php

namespace App\Http\Middleware;

use App\Models\LeaseContract;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantHasRoom
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $role = strtolower((string) ($user->role ?? ''));

        if (! $user || $role !== 'tenant') {
            $debug = sprintf(
                '[DEBUG access] blocked: non-tenant user_id=%s role=%s',
                $user?->user_id ?? 'guest',
                $user?->role ?? 'null'
            );

            return redirect()->route('dashboard')->with('error', 'Only tenants can access this section. '.$debug);
        }

        $leaseCount = LeaseContract::where('tenant_id', $user->user_id)->count();
        $leaseWithRoomCount = LeaseContract::where('tenant_id', $user->user_id)
            ->whereNotNull('room_id')
            ->count();

        $hasRoom = LeaseContract::where('tenant_id', $user->user_id)
            ->whereNotNull('room_id')
            ->where(function ($query) {
                $query->whereNull('contract_status')
                    ->orWhere('contract_status', '!=', 'Terminated');
            })
            ->exists();

        if (! $hasRoom) {
            $statuses = LeaseContract::where('tenant_id', $user->user_id)
                ->pluck('contract_status')
                ->map(fn ($status) => $status ?? 'null')
                ->implode(', ');

            $debug = sprintf(
                '[DEBUG access] blocked: tenant_id=%s leases=%d leases_with_room=%d statuses=[%s]',
                $user->user_id,
                $leaseCount,
                $leaseWithRoomCount,
                $statuses !== '' ? $statuses : 'none'
            );

            return redirect()->route('rooms.index')->with('error', 'You need an assigned room before using maintenance and visitor features. '.$debug);
        }

        return $next($request);
    }
}
