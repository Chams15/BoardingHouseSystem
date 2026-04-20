<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TenantProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class VerificationRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());

        $requests = TenantProfile::with(['user', 'verifier'])
            ->whereIn('verification_status', [
                TenantProfile::VERIFICATION_PENDING,
                TenantProfile::VERIFICATION_APPROVED,
                TenantProfile::VERIFICATION_REJECTED,
            ])
            ->when($status && in_array($status, [TenantProfile::VERIFICATION_PENDING, TenantProfile::VERIFICATION_APPROVED, TenantProfile::VERIFICATION_REJECTED], true), function ($query) use ($status): void {
                $query->where('verification_status', $status);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('full_name', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%")
                        ->orWhere('contact_address', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByRaw("CASE verification_status WHEN 'Pending' THEN 0 WHEN 'Rejected' THEN 1 WHEN 'Approved' THEN 2 ELSE 3 END")
            ->orderByDesc('verification_submitted_at')
            ->get()
            ->map(function (TenantProfile $profile): array {
                return [
                    'profile_id' => $profile->profile_id,
                    'verification_status' => $profile->verification_status,
                    'verification_note' => $profile->verification_note,
                    'verification_submitted_at' => $profile->verification_submitted_at,
                    'verified_at' => $profile->verified_at,
                    'full_name' => $profile->full_name,
                    'contact_number' => $profile->contact_number,
                    'contact_address' => $profile->contact_address,
                    'id_doc_url' => $profile->id_doc_url ? Storage::url($profile->id_doc_url) : null,
                    'user' => [
                        'user_id' => $profile->user?->user_id,
                        'email' => $profile->user?->email,
                    ],
                    'verifier' => $profile->verifier ? [
                        'user_id' => $profile->verifier->user_id,
                        'email' => $profile->verifier->email,
                    ] : null,
                ];
            });

        return Inertia::render('admin/verification-requests/index', [
            'requests' => $requests,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function approve(Request $request, TenantProfile $tenantProfile): RedirectResponse
    {
        $tenantProfile->update([
            'verification_status' => TenantProfile::VERIFICATION_APPROVED,
            'verification_note' => null,
            'verified_at' => now(),
            'verified_by' => $request->user()->user_id,
        ]);

        return back()->with('success', 'Tenant verification approved.');
    }

    public function reject(Request $request, TenantProfile $tenantProfile): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ]);

        $tenantProfile->update([
            'verification_status' => TenantProfile::VERIFICATION_REJECTED,
            'verification_note' => $validated['note'],
            'verified_at' => null,
            'verified_by' => $request->user()->user_id,
        ]);

        return back()->with('success', 'Tenant verification rejected.');
    }
}
