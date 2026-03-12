<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaseContract;
use App\Models\Room;
use App\Models\RoomRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RoomManagementController extends Controller
{
    public function index(): Response
    {
        $rooms = Room::with(['leaseContracts' => function ($q) {
            $q->where('contract_status', 'Active')->with('tenant.tenantProfile');
        }])->withCount(['roomRequests' => fn ($q) => $q->where('status', 'Pending')])
          ->get();

        return Inertia::render('admin/rooms/index', [
            'rooms' => $rooms,
        ]);
    }

    public function requests(): Response
    {
        $requests = RoomRequest::with(['user.tenantProfile', 'room'])
            ->where('status', 'Pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('admin/rooms/requests', [
            'requests' => $requests,
        ]);
    }

    public function approve(RoomRequest $roomRequest): RedirectResponse
    {
        return DB::transaction(function () use ($roomRequest) {
            $roomRequest->update(['status' => 'Approved']);

            $room = $roomRequest->room;
            $room->update(['status' => 'Occupied']);

            LeaseContract::create([
                'tenant_id' => $roomRequest->user_id,
                'room_id' => $roomRequest->room_id,
                'start_date' => now(),
                'end_date' => now()->addYear(),
                'security_deposit' => 0,
                'contract_status' => 'Active',
            ]);

            // Reject all other pending requests for this room
            RoomRequest::where('room_id', $roomRequest->room_id)
                ->where('request_id', '!=', $roomRequest->request_id)
                ->where('status', 'Pending')
                ->update(['status' => 'Rejected']);

            return back()->with('success', 'Request approved. Room is now occupied.');
        });
    }

    public function reject(RoomRequest $roomRequest): RedirectResponse
    {
        $roomRequest->update(['status' => 'Rejected']);

        return back()->with('success', 'Request rejected.');
    }

    public function removeTenant(Room $room): RedirectResponse
    {
        return DB::transaction(function () use ($room) {
            LeaseContract::where('room_id', $room->room_id)
                ->where('contract_status', 'Active')
                ->update(['contract_status' => 'Terminated']);

            $room->update(['status' => 'Available']);

            return back()->with('success', 'Tenant removed from room. Room is now available.');
        });
    }
}
