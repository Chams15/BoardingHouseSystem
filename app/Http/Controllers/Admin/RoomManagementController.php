<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaseContract;
use App\Models\Room;
use App\Models\RoomRequest;
use App\Services\RoomRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RoomManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());

        $rooms = Room::with(['leaseContracts' => function ($q) {
            $q->whereIn('contract_status', ['Active', 'Pending_MoveOut'])->with('tenant.tenantProfile');
        }])->withCount(['roomRequests' => fn ($q) => $q->where('status', 'Pending')])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('room_number', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('amenities', 'like', "%{$search}%")
                        ->orWhereHas('leaseContracts.tenant.tenantProfile', function ($tenantProfileQuery) use ($search): void {
                            $tenantProfileQuery->where('full_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('leaseContracts.tenant', function ($tenantQuery) use ($search): void {
                            $tenantQuery->where('email', 'like', "%{$search}%");
                        });
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
                  'lease_contracts' => $room->leaseContracts,
                  'room_requests_count' => $room->room_requests_count,
              ];
          });

        return Inertia::render('admin/rooms/index', [
            'rooms' => $rooms,
            'filters' => [
                'search' => $search,
            ],
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

    public function approve(RoomRequest $roomRequest, RoomRequestService $roomRequestService): RedirectResponse
    {
        // Uses pessimistic locking to prevent concurrent approvals
        // and ensure data consistency
        try {
            $roomRequestService->approveRequest($roomRequest);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Request approved. Room is now occupied.');
    }

    public function reject(RoomRequest $roomRequest, RoomRequestService $roomRequestService): RedirectResponse
    {
        $roomRequestService->rejectRequest($roomRequest);

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
        // Uses pessimistic locking to prevent concurrent move-out completions
        try {
            DB::transaction(function () use ($contract) {
                // Lock the contract and validate it's in Pending_MoveOut status
                $lockedContract = LeaseContract::where('contract_id', $contract->contract_id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedContract) {
                    throw new \Exception('Contract not found.');
                }

                if ($lockedContract->contract_status !== 'Pending_MoveOut') {
                    throw new \Exception('Contract does not have a pending move-out request.');
                }

                $lockedContract->completeMoveOut();
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
