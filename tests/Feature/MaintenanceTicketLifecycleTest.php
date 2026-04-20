<?php

namespace Tests\Feature;

use App\Models\LeaseContract;
use App\Models\MaintenanceTicket;
use App\Models\Room;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceTicketLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_ticket_is_created_with_pending_status(): void
    {
        [$tenant, $room] = $this->createTenantWithActiveContract();

        $this->actingAs($tenant)
            ->post(route('maintenance.store'), [
                'issue_desc' => 'Leaking faucet in bathroom',
                'priority' => 'High',
                'room_id' => $room->room_id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('maintenance_tickets', [
            'reported_by' => $tenant->user_id,
            'status' => 'Pending',
        ]);
    }

    public function test_admin_reply_marks_ticket_in_progress_and_admin_cannot_directly_resolve(): void
    {
        [$tenant, $room] = $this->createTenantWithActiveContract();
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        $ticket = MaintenanceTicket::create([
            'room_id' => $room->room_id,
            'reported_by' => $tenant->user_id,
            'issue_desc' => 'No water supply',
            'priority' => 'Medium',
            'status' => 'Pending',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.maintenance.update', $ticket), [
                'priority' => 'Medium',
                'status' => 'Resolved',
                'contractor_notes' => 'Plumber dispatched and troubleshooting started.',
            ])
            ->assertRedirect();

        $ticket->refresh();

        $this->assertSame('In Progress', $ticket->status);
        $this->assertNull($ticket->resolved_at);
    }

    public function test_tenant_can_mark_in_progress_ticket_as_resolved(): void
    {
        [$tenant, $room] = $this->createTenantWithActiveContract();

        $ticket = MaintenanceTicket::create([
            'room_id' => $room->room_id,
            'reported_by' => $tenant->user_id,
            'issue_desc' => 'Broken light switch',
            'priority' => 'Low',
            'status' => 'In Progress',
            'contractor_notes' => 'Wiring replaced. Please confirm if issue is fixed.',
        ]);

        $this->actingAs($tenant)
            ->post(route('maintenance.resolve', $ticket))
            ->assertRedirect();

        $ticket->refresh();

        $this->assertSame('Resolved', $ticket->status);
        $this->assertNotNull($ticket->resolved_at);
    }

    /**
     * @return array{0: User, 1: Room}
     */
    private function createTenantWithActiveContract(): array
    {
        $tenant = User::factory()->create([
            'role' => 'Tenant',
        ]);

        TenantProfile::create([
            'user_id' => $tenant->user_id,
            'full_name' => 'Tenant User',
            'contact_number' => '09171234567',
        ]);

        $room = Room::create([
            'room_number' => 'B-101',
            'category' => 'Standard',
            'price_monthly' => 3500,
            'capacity' => 2,
            'status' => 'Occupied',
        ]);

        LeaseContract::create([
            'tenant_id' => $tenant->user_id,
            'room_id' => $room->room_id,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'security_deposit' => 3500,
            'contract_status' => 'Active',
        ]);

        return [$tenant, $room];
    }
}
