<?php

namespace Tests\Feature;

use App\Models\LeaseContract;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyLeaseAutoRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-04-28'));
    }

    private function createRoom(float $price = 3000): Room
    {
        static $roomCounter = 0;
        $roomCounter++;

        return Room::create([
            'room_number' => 'TEST-' . $roomCounter,
            'category' => 'Standard',
            'price_monthly' => $price,
            'capacity' => 2,
            'status' => 'Available',
        ]);
    }

    private function createTenant(): User
    {
        return User::factory()->create(['role' => 'Tenant']);
    }

    public function test_new_lease_has_auto_renewal_enabled_by_default(): void
    {
        $tenant = $this->createTenant();
        $room = $this->createRoom();
        $startDate = now()->toDateString();

        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        // Create lease via admin controller
        $this->post(route('admin.leases.store'), [
            'tenant_id' => $tenant->user_id,
            'room_id' => $room->room_id,
            'start_date' => $startDate,
        ]);

        $lease = LeaseContract::where('tenant_id', $tenant->user_id)->first();

        $this->assertTrue($lease->auto_renew);
        $this->assertNotNull($lease->next_renewal_date);
        $this->assertEquals(
            now()->addMonth()->toDateString(),
            $lease->next_renewal_date->toDateString()
        );
    }

    public function test_lease_auto_renews_when_renewal_date_arrives(): void
    {
        $tenant = $this->createTenant();
        $room = $this->createRoom();

        $startDate = now()->subMonth();
        $endDate = $startDate->copy()->addMonth();
        $renewalDate = $endDate->copy();  // Same as end_date

        $lease = LeaseContract::create([
            'tenant_id' => $tenant->user_id,
            'room_id' => $room->room_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'contract_status' => 'Active',
            'auto_renew' => true,
            'next_renewal_date' => $renewalDate,
        ]);

        // Move time forward so renewal date is in the past
        Carbon::setTestNow(now()->addDays(1));

        $lease->refresh();  // Re-fetch from database
        $lease->autoRenew();
        $lease->refresh();

        // End date should have extended by one month
        $this->assertEquals(
            $renewalDate->copy()->addMonth()->toDateString(),
            $lease->end_date->toDateString()
        );
        // Next renewal should also be one month later
        $this->assertEquals(
            $renewalDate->copy()->addMonth()->toDateString(),
            $lease->next_renewal_date->toDateString()
        );
    }

    public function test_tenant_can_request_move_out_with_7_day_notice(): void
    {
        $tenant = $this->createTenant();
        $room = $this->createRoom();

        $renewalDate = now()->addDays(15);

        $lease = LeaseContract::create([
            'tenant_id' => $tenant->user_id,
            'room_id' => $room->room_id,
            'start_date' => now()->subMonth(),
            'end_date' => $renewalDate->copy()->subDay(),
            'contract_status' => 'Active',
            'auto_renew' => true,
            'next_renewal_date' => $renewalDate,
        ]);

        $this->actingAs($tenant);
        $this->post(route('rooms.move-out'));

        $lease->refresh();

        $this->assertEquals('Pending_MoveOut', $lease->contract_status);
        $this->assertFalse($lease->auto_renew);
        $this->assertNotNull($lease->move_out_req_date);
        $this->assertNotNull($lease->move_out_final_date);
    }

    public function test_move_out_after_7_days_uses_current_renewal_date(): void
    {
        $tenant = $this->createTenant();
        $room = $this->createRoom();

        $renewalDate = now()->addDays(10);

        $lease = LeaseContract::create([
            'tenant_id' => $tenant->user_id,
            'room_id' => $room->room_id,
            'start_date' => now()->subMonth(),
            'end_date' => $renewalDate->copy()->subDay(),
            'contract_status' => 'Active',
            'auto_renew' => true,
            'next_renewal_date' => $renewalDate,
        ]);

        $this->actingAs($tenant);
        $this->post(route('rooms.move-out'));

        $lease->refresh();

        // Should move out on the renewal date since there's 10 days notice
        $this->assertEquals(
            $renewalDate->toDateString(),
            $lease->move_out_final_date->toDateString()
        );
    }

    public function test_move_out_with_less_than_7_days_notice_defers_to_next_month(): void
    {
        $tenant = $this->createTenant();
        $room = $this->createRoom();

        // Renewal date is only 5 days away
        $renewalDate = now()->addDays(5);

        $lease = LeaseContract::create([
            'tenant_id' => $tenant->user_id,
            'room_id' => $room->room_id,
            'start_date' => now()->subMonth(),
            'end_date' => $renewalDate->copy()->subDay(),
            'contract_status' => 'Active',
            'auto_renew' => true,
            'next_renewal_date' => $renewalDate,
        ]);

        $this->actingAs($tenant);
        $this->post(route('rooms.move-out'));

        $lease->refresh();

        // Should defer move-out to next month (7+ days away)
        $expectedMoveOutDate = $renewalDate->copy()->addMonth();
        $this->assertEquals(
            $expectedMoveOutDate->toDateString(),
            $lease->move_out_final_date->toDateString()
        );
    }

    public function test_lease_does_not_renew_if_auto_renew_is_disabled(): void
    {
        $tenant = $this->createTenant();
        $room = $this->createRoom();

        $lease = LeaseContract::create([
            'tenant_id' => $tenant->user_id,
            'room_id' => $room->room_id,
            'start_date' => now()->subMonth(),
            'end_date' => now(),
            'contract_status' => 'Active',
            'auto_renew' => false,
            'next_renewal_date' => now(),
        ]);

        $originalEndDate = $lease->end_date->copy();
        $this->assertFalse($lease->isEligibleForAutoRenewal());

        $lease->autoRenew();

        // Should not have renewed
        $this->assertEquals(
            $originalEndDate->toDateString(),
            $lease->end_date->toDateString()
        );
    }

    public function test_complete_move_out_terminates_lease_and_marks_room_available(): void
    {
        $tenant = $this->createTenant();
        $room = $this->createRoom();

        $lease = LeaseContract::create([
            'tenant_id' => $tenant->user_id,
            'room_id' => $room->room_id,
            'start_date' => now()->subMonth(),
            'end_date' => now(),
            'contract_status' => 'Pending_MoveOut',
            'auto_renew' => false,
            'next_renewal_date' => now(),
        ]);

        // Mark room as occupied first
        $room->update(['status' => 'Occupied']);

        $lease->completeMoveOut();

        $lease->refresh();
        $room->refresh();

        $this->assertEquals('Terminated', $lease->contract_status);
        $this->assertFalse($lease->auto_renew);
        $this->assertNull($lease->next_renewal_date);
        $this->assertEquals('Available', $room->status);
    }

    public function test_artisan_command_auto_renews_eligible_leases(): void
    {
        $tenant1 = $this->createTenant();
        $tenant2 = $this->createTenant();
        $room1 = $this->createRoom(3000);
        $room2 = $this->createRoom(4000);

        $renewalDate1 = now()->subDay();  // Past - eligible for renewal
        $renewalDate2 = now()->addMonth();  // Future - not eligible

        // Lease eligible for renewal (renewal date in past)
        $lease1 = LeaseContract::create([
            'tenant_id' => $tenant1->user_id,
            'room_id' => $room1->room_id,
            'start_date' => $renewalDate1->copy()->subMonth(),
            'end_date' => $renewalDate1,
            'contract_status' => 'Active',
            'auto_renew' => true,
            'next_renewal_date' => $renewalDate1,
        ]);

        // Lease not eligible (renewal in future)
        $lease2 = LeaseContract::create([
            'tenant_id' => $tenant2->user_id,
            'room_id' => $room2->room_id,
            'start_date' => now(),
            'end_date' => $renewalDate2,
            'contract_status' => 'Active',
            'auto_renew' => true,
            'next_renewal_date' => $renewalDate2,
        ]);

        $this->artisan('leases:auto-renew')->assertExitCode(0);

        $lease1->refresh();
        $lease2->refresh();

        // Only lease1 should have renewed
        $this->assertEquals(
            $renewalDate1->copy()->addMonth()->toDateString(),
            $lease1->end_date->toDateString()
        );
        $this->assertEquals(
            $renewalDate2->toDateString(),
            $lease2->end_date->toDateString()
        );
    }
}
