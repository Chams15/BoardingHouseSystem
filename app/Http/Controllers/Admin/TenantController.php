<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Http\Controllers\Controller;
use App\Models\LeaseContract;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    use PasswordValidationRules;
    use ProfileValidationRules;

    public function index(Request $request): Response
    {
        $query = User::where('role', 'Tenant')
            ->with('tenantProfile');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhereHas('tenantProfile', function ($q) use ($search) {
                      $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%");
                  });
            });
        }

        $tenants = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('admin/tenants/index', [
            'tenants' => $tenants,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/tenants/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'role' => 'Tenant',
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->tenantProfile()->create([
                'full_name' => $validated['full_name'],
                'contact_number' => $validated['contact_number'],
                'contact_address' => $validated['contact_address'],
                'emergency_contact' => $validated['emergency_contact'] ?? null,
            ]);
        });

        return redirect()->route('admin.tenants.index')->with('success', 'Tenant created successfully.');
    }

    public function edit(User $tenant): Response
    {
        $tenant->load('tenantProfile');

        return Inertia::render('admin/tenants/edit', [
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request, User $tenant): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->profileRules($tenant->user_id),
            'is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($tenant, $validated) {
            $tenant->update([
                'email' => $validated['email'],
                'is_active' => $validated['is_active'],
            ]);

            $tenant->tenantProfile()->updateOrCreate(
                ['user_id' => $tenant->user_id],
                [
                    'full_name' => $validated['full_name'],
                    'contact_number' => $validated['contact_number'],
                    'contact_address' => $validated['contact_address'],
                    'emergency_contact' => $validated['emergency_contact'] ?? null,
                ]
            );
        });

        return redirect()->route('admin.tenants.index')->with('success', 'Tenant updated successfully.');
    }

    public function destroy(User $tenant): RedirectResponse
    {
        return DB::transaction(function () use ($tenant) {
            // Get all active leases for this tenant
            $activeLeases = LeaseContract::where('tenant_id', $tenant->user_id)
                ->whereIn('contract_status', ['Active', 'Pending_MoveOut'])
                ->get();

            // Terminate all active leases and mark rooms as available
            foreach ($activeLeases as $lease) {
                $lease->update(['contract_status' => 'Terminated']);
                Room::where('room_id', $lease->room_id)
                    ->update(['status' => 'Available']);
            }

            // Delete the tenant (this will cascade delete terminated leases, bills, and tenant profile)
            $tenant->delete();

            return redirect()->route('admin.tenants.index')
                ->with('success', sprintf('Tenant deleted successfully. %d active lease(s) were terminated and room(s) marked as available.', count($activeLeases)));
        });
    }
}
