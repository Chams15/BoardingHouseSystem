<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaseContract;
use App\Models\Room;
use App\Models\RoomRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RoomManagementController extends Controller
{
    public function index(): Response
    {
        $rooms = Room::with(['leaseContracts' => function ($q) {
            $q->whereIn('contract_status', ['Active', 'Pending_MoveOut'])->with('tenant.tenantProfile');
        }])->withCount(['roomRequests' => fn ($q) => $q->where('status', 'Pending')])
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
                  'lease_contracts' => $room->leaseContracts,
                  'room_requests_count' => $room->room_requests_count,
              ];
          });

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

    /**
     * Show the create room form
     */
    public function createRoom(): Response
    {
        return Inertia::render('admin/rooms/create');
    }

    /**
     * Store a new room
     */
    public function storeRoom(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'room_number' => 'required|string|unique:rooms,room_number',
            'category' => 'required|string',
            'price_monthly' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:Available,Occupied,Maintenance',
            'amenities' => 'nullable|string',
            'room_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $roomImagePath = $request->file('room_image')?->store('rooms', 'public');

        Room::create([
            ...$validated,
            'room_image_path' => $roomImagePath,
        ]);

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Room created successfully.');
    }

    /**
     * Show the edit room form
     */
    public function editRoom(Room $room): Response
    {
        return Inertia::render('admin/rooms/edit', [
            'room' => $room->load([
                'leaseContracts' => function ($q) {
                    $q->with(['tenant.tenantProfile'])->orderBy('created_at', 'desc');
                }
            ]),
            'room_image_url' => $room->room_image_path ? Storage::url($room->room_image_path) : null,
        ]);
    }

    /**
     * Update a room
     */
    public function updateRoom(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'room_number' => 'required|string|unique:rooms,room_number,' . $room->room_id . ',room_id',
            'category' => 'required|string',
            'price_monthly' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:Available,Occupied,Maintenance',
            'amenities' => 'nullable|string',
            'room_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $roomImagePath = $room->room_image_path;

        if ($request->hasFile('room_image')) {
            if ($roomImagePath && Storage::disk('public')->exists($roomImagePath)) {
                Storage::disk('public')->delete($roomImagePath);
            }

            $roomImagePath = $request->file('room_image')->store('rooms', 'public');
        }

        $room->update([
            ...$validated,
            'room_image_path' => $roomImagePath,
        ]);

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Room updated successfully.');
    }

    /**
     * Delete a room
     */
    public function deleteRoom(Room $room): RedirectResponse
    {
        // Check if room has any active leases
        $activeLeases = LeaseContract::where('room_id', $room->room_id)
            ->whereIn('contract_status', ['Active', 'Pending_MoveOut'])
            ->count();

        if ($activeLeases > 0) {
            return back()->with('error', 'Cannot delete a room with active leases. Please terminate all leases first.');
        }

        $room->delete();

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Room deleted successfully.');
    }
}
