<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomRequest;
use App\Models\User;
use App\Services\RoomRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createRoom(string $number = 'TEST-01'): Room
    {
        return Room::create([
            'room_number' => $number,
            'category' => 'Standard',
            'price_monthly' => 3000,
            'capacity' => 2,
            'status' => 'Available',
        ]);
    }

    private function createTenant(): User
    {
        return User::factory()->create(['role' => 'Tenant']);
    }

    public function test_approve_request_creates_monthly_lease_with_auto_renewal(): void
    {
        $tenant = $this->createTenant();
        $room = $this->createRoom();
        $service = new RoomRequestService();

        // Create a room request
        $roomRequest = RoomRequest::create([
            'user_id' => $tenant->user_id,
            'room_id' => $room->room_id,
            'status' => 'Pending',
            'message' => 'Test request',
        ]);

        // Approve the request
        $service->approveRequest($roomRequest);

        // Verify request was approved
        $this->assertEquals('Approved', $roomRequest->fresh()->status);

        // Verify room is now occupied
        $this->assertEquals('Occupied', $room->fresh()->status);

        // Verify monthly lease was created
        $lease = $room->leaseContracts()->first();
        $this->assertNotNull($lease);
        $this->assertEquals($tenant->user_id, $lease->tenant_id);
        $this->assertEquals('Active', $lease->contract_status);
        $this->assertTrue($lease->auto_renew);
        $this->assertEquals(30, $lease->start_date->diffInDays($lease->end_date));
    }

    public function test_approve_request_rejects_other_pending_requests(): void
    {
        $tenant1 = $this->createTenant();
        $tenant2 = $this->createTenant();
        $tenant3 = $this->createTenant();
        $room = $this->createRoom();
        $service = new RoomRequestService();

        // Create multiple pending requests for the same room
        $request1 = RoomRequest::create([
            'user_id' => $tenant1->user_id,
            'room_id' => $room->room_id,
            'status' => 'Pending',
            'message' => 'First request',
        ]);

        $request2 = RoomRequest::create([
            'user_id' => $tenant2->user_id,
            'room_id' => $room->room_id,
            'status' => 'Pending',
            'message' => 'Second request',
        ]);

        $request3 = RoomRequest::create([
            'user_id' => $tenant3->user_id,
            'room_id' => $room->room_id,
            'status' => 'Pending',
            'message' => 'Third request',
        ]);

        // Approve the first request
        $service->approveRequest($request1);

        // Verify only first request is approved
        $this->assertEquals('Approved', $request1->fresh()->status);
        $this->assertEquals('Rejected', $request2->fresh()->status);
        $this->assertEquals('Rejected', $request3->fresh()->status);

        // Verify only one lease was created
        $this->assertEquals(1, $room->leaseContracts()->count());
    }

    public function test_approve_request_with_unavailable_room_fails(): void
    {
        $tenant1 = $this->createTenant();
        $tenant2 = $this->createTenant();
        $room = $this->createRoom();
        $service = new RoomRequestService();

        // Create first request and approve it
        $request1 = RoomRequest::create([
            'user_id' => $tenant1->user_id,
            'room_id' => $room->room_id,
            'status' => 'Pending',
            'message' => 'First request',
        ]);
        $service->approveRequest($request1);

        // Try to approve a second request for the same room (now occupied)
        $request2 = RoomRequest::create([
            'user_id' => $tenant2->user_id,
            'room_id' => $room->room_id,
            'status' => 'Pending',
            'message' => 'Second request',
        ]);

        // This should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Room is no longer available');
        $service->approveRequest($request2);
    }

    public function test_reject_request(): void
    {
        $tenant = $this->createTenant();
        $room = $this->createRoom();
        $service = new RoomRequestService();

        $roomRequest = RoomRequest::create([
            'user_id' => $tenant->user_id,
            'room_id' => $room->room_id,
            'status' => 'Pending',
            'message' => 'Test request',
        ]);

        $service->rejectRequest($roomRequest);

        $this->assertEquals('Rejected', $roomRequest->fresh()->status);
        $this->assertEquals('Available', $room->fresh()->status);
        $this->assertEquals(0, $room->leaseContracts()->count());
    }

    public function test_approve_request_uses_pessimistic_locking(): void
    {
        $tenant = $this->createTenant();
        $room = $this->createRoom();
        $service = new RoomRequestService();

        $roomRequest = RoomRequest::create([
            'user_id' => $tenant->user_id,
            'room_id' => $room->room_id,
            'status' => 'Pending',
            'message' => 'Test request',
        ]);

        // This should succeed with pessimistic locking
        $service->approveRequest($roomRequest);

        $this->assertEquals('Approved', $roomRequest->fresh()->status);
        $this->assertEquals('Occupied', $room->fresh()->status);
    }
}
