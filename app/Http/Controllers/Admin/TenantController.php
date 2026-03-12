<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
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
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users', 'email')],
            'contact_number' => ['required', 'string', 'max:20'],
            'emergency_contact' => ['nullable', 'string', 'max:150'],
            'password' => ['required', 'string', 'min:8'],
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
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users', 'email')->ignore($tenant->user_id, 'user_id')],
            'contact_number' => ['required', 'string', 'max:20'],
            'emergency_contact' => ['nullable', 'string', 'max:150'],
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
                    'emergency_contact' => $validated['emergency_contact'] ?? null,
                ]
            );
        });

        return redirect()->route('admin.tenants.index')->with('success', 'Tenant updated successfully.');
    }

    public function destroy(User $tenant): RedirectResponse
    {
        $tenant->delete();

        return redirect()->route('admin.tenants.index')->with('success', 'Tenant deleted successfully.');
    }
}
