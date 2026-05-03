<?php

namespace App\Http\Controllers;

use App\Models\LeaseContract;
use App\Models\Room;
use App\Models\RoomRequest;
use App\Models\TenantProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());

        $rooms = Room::withCount(['roomRequests' => fn ($q) => $q->where('status', 'Pending')])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('room_number', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('amenities', 'like', "%{$search}%");
                });
            })
            ->get()
            ->map(function (Room $room): array {
                return [
                    'room_id' => $room->room_id,
                    'room_number' => $room->room_number,
                    'category' => $room->category,
                    'price_monthly' => $room->price_monthly,
                    'capacity' => $room->capacity,
                    'status' => $room->status,
                    'amenities' => $room->amenities,
                    'room_image_url' => $room->room_image_path ? Storage::url($room->room_image_path) : null,
                    'room_requests_count' => $room->room_requests_count,
                ];
            });

        $user = $request->user();

        $userRequests = RoomRequest::where('user_id', $user->user_id)
            ->where('status', 'Pending')
            ->pluck('request_id', 'room_id')
            ->toArray();

        $hasActiveContract = LeaseContract::where('tenant_id', $user->user_id)
            ->whereIn('contract_status', ['Active', 'Pending_MoveOut'])
            ->exists();

        $verificationStatus = $user->tenantProfile?->verification_status ?? TenantProfile::VERIFICATION_NOT_SUBMITTED;
        $canRequestRooms = $verificationStatus === TenantProfile::VERIFICATION_APPROVED;

        return Inertia::render('rooms/index', [
            'rooms' => $rooms,
            'userPendingRequests' => $userRequests,
            'hasActiveContract' => $hasActiveContract,
            'canRequestRooms' => $canRequestRooms,
            'verificationStatus' => $verificationStatus,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function requestRoom(Request $request, Room $room): RedirectResponse
    {
        $user = $request->user();

        //pessimistic lock
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

            $verificationStatus = $user->tenantProfile?->verification_status ?? TenantProfile::VERIFICATION_NOT_SUBMITTED;

            if ($verificationStatus !== TenantProfile::VERIFICATION_APPROVED) {
                return back()->with('error', 'Please complete tenant verification before requesting a room.');
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
            ->lockForUpdate()
            ->first();

        if (! $contract) {
            return back()->with('error', 'You do not have an active lease.');
        }

        try {
            $contract->requestAutoRenewalCancellation();

            $moveOutDate = $contract->move_out_final_date->toDateString();
            $daysRemaining = now()->diffInDays($contract->move_out_final_date);

            return back()->with('success', 
                "Move-out requested successfully. Your lease will terminate on {$moveOutDate} ({$daysRemaining} days from now)."
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to process move-out request: ' . $e->getMessage());
        }
    }
}
