<?php

namespace App\Http\Controllers;

use App\Models\LeaseContract;
use App\Models\MaintenanceTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $tickets = MaintenanceTicket::with('room')
            ->where('reported_by', $user->user_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (MaintenanceTicket $ticket) {
                return [
                    'ticket_id' => $ticket->ticket_id,
                    'issue_desc' => $ticket->issue_desc,
                    'issue_photo_url' => $ticket->issue_photo_path ? Storage::url($ticket->issue_photo_path) : null,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'contractor_notes' => $ticket->contractor_notes,
                    'created_at' => $ticket->created_at,
                    'resolved_at' => $ticket->resolved_at,
                    'room' => $ticket->room,
                ];
            });

        $activeContract = LeaseContract::where('tenant_id', $user->user_id)
            ->whereIn('contract_status', ['Active', 'Pending_MoveOut'])
            ->with('room')
            ->first();

        return Inertia::render('maintenance/index', [
            'tickets' => $tickets,
            'activeContract' => $activeContract,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'issue_desc' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'in:Low,Medium,High'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,room_id'],
            'issue_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ], [
            'issue_photo.uploaded' => 'Issue photo failed to upload. Please try a smaller file (up to 10MB) or check server upload limits.',
            'issue_photo.max' => 'Issue photo must not be greater than 10MB.',
            'issue_photo.mimes' => 'Issue photo must be a JPG or PNG image.',
        ]);

        $photoPath = $request->file('issue_photo')?->store('maintenance', 'public');

        MaintenanceTicket::create([
            'room_id' => $validated['room_id'] ?? null,
            'reported_by' => $request->user()->user_id,
            'issue_desc' => $validated['issue_desc'],
            'issue_photo_path' => $photoPath,
            'priority' => $validated['priority'],
            'status' => 'Pending',
        ]);

        return back()->with('success', 'Maintenance request submitted.');
    }
}
