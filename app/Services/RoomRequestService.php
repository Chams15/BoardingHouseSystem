<?php

namespace App\Services;

use App\Models\LeaseContract;
use App\Models\Room;
use App\Models\RoomRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RoomRequestService
{
    /**
     * Approve a room request and create a monthly auto-renewing lease
     * 
     * Uses pessimistic locking to prevent race conditions
     * 
     * @throws \Exception if approval fails
     */
    public function approveRequest(RoomRequest $roomRequest): void
    {
        DB::transaction(function () use ($roomRequest): void {
            // Lock and fetch the room request
            $request = RoomRequest::where('request_id', $roomRequest->request_id)
                ->where('status', 'Pending')
                ->lockForUpdate()
                ->first();

            if (!$request) {
                throw new \Exception('Request not found or already processed.');
            }

            // Lock and verify room is still available
            $room = Room::where('room_id', $request->room_id)
                ->lockForUpdate()
                ->first();

            if (!$room) {
                throw new \Exception('Room not found.');
            }

            if ($room->status !== 'Available') {
                throw new \Exception('Room is no longer available.');
            }

            // 1. Create monthly auto-renewing lease
            $today = now()->startOfDay();
            LeaseContract::create([
                'tenant_id' => $request->user_id,
                'room_id' => $request->room_id,
                'start_date' => $today,
                'end_date' => $today->copy()->addMonth(),
                'security_deposit' => 0,
                'contract_status' => 'Active',
                'auto_renew' => true,
                'next_renewal_date' => $today->copy()->addMonth(),
            ]);

            // 2. Approve the request
            $request->update([
                'status' => 'Approved',
                'updated_at' => now(),
            ]);

            // 3. Mark room as occupied
            $room->update([
                'status' => 'Occupied',
                'updated_at' => now(),
            ]);

            // 4. Reject all other pending requests for this room
            RoomRequest::where('room_id', $request->room_id)
                ->where('request_id', '!=', $request->request_id)
                ->where('status', 'Pending')
                ->update([
                    'status' => 'Rejected',
                    'updated_at' => now(),
                ]);
        });
    }

    /**
     * Reject a room request
     */
    public function rejectRequest(RoomRequest $roomRequest): void
    {
        $roomRequest->update([
            'status' => 'Rejected',
            'updated_at' => now(),
        ]);
    }
}
