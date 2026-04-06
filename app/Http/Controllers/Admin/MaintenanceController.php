<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $status = $request->string('status')->toString();
        $allowedStatuses = ['Pending', 'In Progress', 'Resolved'];
        $recurringSince = now()->subDays(7);

        $query = MaintenanceTicket::with(['room', 'reporter.tenantProfile'])
            ->orderByRaw("FIELD(status, 'Pending', 'In Progress', 'Resolved')")
            ->orderByDesc('created_at');

        if (in_array($status, $allowedStatuses, true)) {
            $query->where('status', $status);
        }

        $tickets = $query->get()->map(function (MaintenanceTicket $ticket) {
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
                'reporter' => $ticket->reporter,
            ];
        });

        $recurringBaseQuery = MaintenanceTicket::query()
            ->where('maintenance_tickets.created_at', '>=', $recurringSince);

        if (in_array($status, $allowedStatuses, true)) {
            $recurringBaseQuery->where('status', $status);
        }

        $recurringByRoom = (clone $recurringBaseQuery)
            ->leftJoin('rooms', 'maintenance_tickets.room_id', '=', 'rooms.room_id')
            ->selectRaw('maintenance_tickets.room_id')
            ->selectRaw("COALESCE(rooms.room_number, 'Common Area') as room_label")
            ->selectRaw('COUNT(*) as total_tickets')
            ->selectRaw('COUNT(DISTINCT maintenance_tickets.reported_by) as tenant_count')
            ->selectRaw("SUM(CASE WHEN maintenance_tickets.status != 'Resolved' THEN 1 ELSE 0 END) as open_tickets")
            ->groupBy('maintenance_tickets.room_id', 'rooms.room_number')
            ->havingRaw('COUNT(*) >= 2')
            ->orderByDesc('total_tickets')
            ->limit(8)
            ->get();

        $recurringByTenant = (clone $recurringBaseQuery)
            ->join('users', 'maintenance_tickets.reported_by', '=', 'users.user_id')
            ->leftJoin('tenant_profiles', 'users.user_id', '=', 'tenant_profiles.user_id')
            ->selectRaw('maintenance_tickets.reported_by as tenant_id')
            ->selectRaw('COALESCE(tenant_profiles.full_name, users.email) as tenant_name')
            ->selectRaw('users.email as tenant_email')
            ->selectRaw('COUNT(*) as total_tickets')
            ->selectRaw('COUNT(DISTINCT maintenance_tickets.room_id) as affected_rooms')
            ->selectRaw("SUM(CASE WHEN maintenance_tickets.status != 'Resolved' THEN 1 ELSE 0 END) as open_tickets")
            ->groupBy('maintenance_tickets.reported_by', 'tenant_profiles.full_name', 'users.email')
            ->havingRaw('COUNT(*) >= 2')
            ->orderByDesc('total_tickets')
            ->limit(8)
            ->get();

        return Inertia::render('admin/maintenance/index', [
            'tickets' => $tickets,
            'filters' => [
                'status' => $status,
            ],
            'recurringWindowDays' => 7,
            'recurringByRoom' => $recurringByRoom,
            'recurringByTenant' => $recurringByTenant,
        ]);
    }

    public function update(Request $request, MaintenanceTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'priority' => ['required', 'in:Low,Medium,High'],
            'status' => ['required', 'in:Pending,In Progress,Resolved'],
            'contractor_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $ticket->update([
            'priority' => $validated['priority'],
            'status' => $validated['status'],
            'contractor_notes' => $validated['contractor_notes'] ?? null,
            'resolved_at' => $validated['status'] === 'Resolved' ? now() : null,
        ]);

        return back()->with('success', 'Ticket updated successfully.');
    }
}
