<?php

namespace App\Http\Controllers;

use App\Models\TenantProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect('/settings/verification');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ], [
            'id_document.mimes' => 'Valid ID must be a JPG, PNG, or PDF file.',
            'id_document.max' => 'Valid ID must not be greater than 10MB.',
        ]);

        $user = $request->user();
        $profile = $user->tenantProfile;

        if (! $profile) {
            return back()->with('error', 'Please complete your profile information first.');
        }

        if ($profile->verification_status === TenantProfile::VERIFICATION_APPROVED) {
            return back()->with('error', 'Your verification has already been approved. You cannot submit another request.');
        }

        if (blank($profile->contact_address)) {
            return back()->with('error', 'Please add your contact address in profile settings before submitting verification.');
        }

        $idDocPath = $request->file('id_document')->store('verification/ids', 'public');

        if ($profile->id_doc_url && Storage::disk('public')->exists($profile->id_doc_url)) {
            Storage::disk('public')->delete($profile->id_doc_url);
        }

        $profile->update([
            'id_doc_url' => $idDocPath,
            'verification_status' => TenantProfile::VERIFICATION_PENDING,
            'verification_note' => null,
            'verification_submitted_at' => now(),
            'verified_at' => null,
            'verified_by' => null,
        ]);

        return back()->with('success', 'Verification request submitted. Please wait for admin review.');
    }
}
