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
            $q->whereIn('contract_status', ['Active', 'Pending_MoveOut'])->with('tenant.tenantProfile');
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
        // Pessimistic lock: wrap in a transaction and call the stored procedure.
        // sp_approve_room_request acquires FOR UPDATE locks on both the
        // room_requests and rooms rows, preventing two concurrent approvals
        // from creating duplicate contracts.
        try {
            DB::transaction(function () use ($roomRequest) {
                DB::statement('CALL sp_approve_room_request(?)', [$roomRequest->request_id]);
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Request approved. Room is now occupied.');
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

    public function approveMoveOut(LeaseContract $contract): RedirectResponse
    {
        // Delegates to sp_process_move_out which acquires a FOR UPDATE lock
        // on the lease_contract row before terminating it.
        try {
            DB::transaction(function () use ($contract) {
                DB::statement('CALL sp_process_move_out(?)', [$contract->contract_id]);
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Move-out approved. Room is now available.');
    }
}
