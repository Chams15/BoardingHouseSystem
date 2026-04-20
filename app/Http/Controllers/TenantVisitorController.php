<?php

namespace App\Http\Controllers;

use App\Models\VisitorLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TenantVisitorController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $visitorLogs = VisitorLog::where('tenant_visited', $user->user_id)
            ->orderByDesc('time_in')
            ->get()
            ->map(function (VisitorLog $log) {
                return [
                    'log_id' => $log->log_id,
                    'visitor_name' => $log->visitor_name,
                    'visitor_photo_url' => $log->visitor_photo_path ? Storage::url($log->visitor_photo_path) : null,
                    'purpose' => $log->purpose,
                    'time_in' => $log->time_in,
                    'time_out' => $log->time_out,
                ];
            });

        return Inertia::render('visitors/index', [
            'visitorLogs' => $visitorLogs,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'visitor_name' => ['required', 'string', 'max:100'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'visitor_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ], [
            'visitor_photo.uploaded' => 'Visitor photo failed to upload. Please try a smaller file (up to 10MB) or check server upload limits.',
            'visitor_photo.max' => 'Visitor photo must not be greater than 10MB.',
            'visitor_photo.mimes' => 'Visitor photo must be a JPG or PNG image.',
        ]);

        $photoPath = $request->file('visitor_photo')?->store('visitors', 'public');

        VisitorLog::create([
            'tenant_visited' => $request->user()->user_id,
            'visitor_name' => $validated['visitor_name'],
            'visitor_photo_path' => $photoPath,
            'purpose' => $validated['purpose'] ?? null,
            'time_in' => now(),
        ]);

        return back()->with('success', 'Visitor registered successfully.');
    }

    public function checkout(VisitorLog $visitorLog, Request $request): RedirectResponse
    {
        $user = $request->user();

        // Verify the visitor log belongs to the current tenant
        if ($visitorLog->tenant_visited !== $user->user_id) {
            return back()->with('error', 'You cannot check out this visitor.');
        }

        if ($visitorLog->time_out !== null) {
            return back()->with('error', 'Visitor is already checked out.');
        }

        $visitorLog->update([
            'time_out' => now(),
        ]);

        return back()->with('success', 'Visitor checked out successfully.');
    }
}
