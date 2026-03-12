<?php

namespace App\Http\Controllers;

use App\Models\LeaseContract;
use App\Models\Room;
use App\Models\RoomRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): Response
    {
        $rooms = Room::withCount(['roomRequests' => fn ($q) => $q->where('status', 'Pending')])
            ->get();

        $user = $request->user();

        $userRequests = RoomRequest::where('user_id', $user->user_id)
            ->where('status', 'Pending')
            ->pluck('request_id', 'room_id')
            ->toArray();

        $hasActiveContract = LeaseContract::where('tenant_id', $user->user_id)
            ->whereIn('contract_status', ['Active', 'Pending_MoveOut'])
            ->exists();

        return Inertia::render('rooms/index', [
            'rooms' => $rooms,
            'userPendingRequests' => $userRequests,
            'hasActiveContract' => $hasActiveContract,
        ]);
    }

    public function requestRoom(Request $request, Room $room): RedirectResponse
    {
        $user = $request->user();

        // Pessimistic locking: lock the room row for the duration of this
        // transaction so two concurrent requests for the same room cannot
        // both pass the availability check and both get created.
        return DB::transaction(function () use ($request, $room, $user) {
            /** @var Room $lockedRoom */
            $lockedRoom = Room::where('room_id', $room->room_id)->lockForUpdate()->first();

            if ($lockedRoom->status !== 'Available') {
                return back()->with('error', 'This room is not available.');
            }

            $alreadyOccupying = LeaseContract::where('tenant_id', $user->user_id)
                ->whereIn('contract_status', ['Active', 'Pending_MoveOut'])
                ->exists();

            if ($alreadyOccupying) {
                return back()->with('error', 'You already have an assigned room.');
            }

            $existing = RoomRequest::where('user_id', $user->user_id)
                ->where('room_id', $lockedRoom->room_id)
                ->where('status', 'Pending')
                ->exists();

            if ($existing) {
                return back()->with('error', 'You already have a pending request for this room.');
            }

            RoomRequest::create([
                'user_id' => $user->user_id,
                'room_id' => $lockedRoom->room_id,
                'message' => $request->input('message'),
            ]);

            return back()->with('success', 'Room request submitted successfully.');
        });
    }

    public function cancelRequest(Request $request, RoomRequest $roomRequest): RedirectResponse
    {
        if ($roomRequest->user_id !== $request->user()->user_id) {
            abort(403);
        }

        if ($roomRequest->status !== 'Pending') {
            return back()->with('error', 'Only pending requests can be cancelled.');
        }

        $roomRequest->delete();

        return back()->with('success', 'Request cancelled.');
    }

    public function requestMoveOut(Request $request): RedirectResponse
    {
        $user = $request->user();

        $contract = LeaseContract::where('tenant_id', $user->user_id)
            ->where('contract_status', 'Active')
            ->first();

        if (! $contract) {
            return back()->with('error', 'You do not have an active lease.');
        }

        $contract->update([
            'contract_status' => 'Pending_MoveOut',
            'move_out_req_date' => now(),
        ]);

        return back()->with('success', 'Move-out request submitted. Please wait for admin approval.');
    }
}
