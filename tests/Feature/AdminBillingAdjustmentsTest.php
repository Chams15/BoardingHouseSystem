<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\LeaseContract;
use App\Models\Room;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBillingAdjustmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_apply_discount_to_a_bill(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        [, $bill] = $this->createTenantAndBill(3000);

        $this->actingAs($admin)
            ->post(route('admin.billing.discount', $bill), [
                'amount' => 500,
                'reason' => 'Loyalty discount',
            ])
            ->assertRedirect();

        $bill->refresh();

        $this->assertSame('2500.00', (string) $bill->amount_due);
        $this->assertSame('3000.00', (string) $bill->original_amount_due);
        $this->assertSame('500.00', (string) $bill->discount_amount);
        $this->assertSame('Loyalty discount', $bill->discount_reason);
    }

    public function test_admin_waive_creates_payment_ledger_entry(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        [, $bill] = $this->createTenantAndBill(3000);

        $this->actingAs($admin)
            ->post(route('admin.billing.waive', $bill), [
                'amount' => 300,
                'reason' => 'Water outage compensation',
            ])
            ->assertRedirect();

        $bill->refresh();

        $this->assertSame('2700.00', (string) $bill->amount_due);
        $this->assertSame('300.00', (string) $bill->waived_amount);
        $this->assertSame('Water outage compensation', $bill->waived_reason);

        $this->assertDatabaseHas('payments', [
            'bill_id' => $bill->bill_id,
            'payment_method' => 'Waiver',
            'provider_status' => 'waived',
            'amount_paid' => 300,
        ]);
    }

    public function test_admin_can_record_offline_payment_as_settled_payment(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        [, $bill] = $this->createTenantAndBill(3000);

        $this->actingAs($admin)
            ->post(route('admin.billing.offline-payment', $bill), [
                'reference_no' => 'OR-2026-0001',
                'notes' => 'Collected by front desk',
            ])
            ->assertRedirect();

        $bill->refresh();

        $this->assertSame('Paid', $bill->payment_status);

        $this->assertDatabaseHas('payments', [
            'bill_id' => $bill->bill_id,
            'payment_method' => 'Offline',
            'provider' => 'offline',
            'provider_status' => 'paid',
            'reference_no' => 'OR-2026-0001',
            'amount_paid' => 3000,
        ]);
    }

    public function test_tenant_payments_page_shows_associated_bill_and_payment_entries(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        [$tenant, $bill] = $this->createTenantAndBill(3000);

        $this->actingAs($admin)->post(route('admin.billing.waive', $bill), [
            'amount' => 200,
            'reason' => 'Courtesy waiver',
        ])->assertRedirect();

        $this->actingAs($tenant)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('payments/index')
            ->assertSee((string) $bill->bill_id)
            ->assertSee('Waiver');
    }

    /**
     * @return array{0: User, 1: Bill}
     */
    private function createTenantAndBill(float $amount): array
    {
        $tenant = User::factory()->create([
            'role' => 'Tenant',
        ]);

        TenantProfile::create([
            'user_id' => $tenant->user_id,
            'full_name' => 'Tenant Test',
            'contact_number' => '09171234567',
        ]);

        $room = Room::create([
            'room_number' => 'A-201',
            'category' => 'Standard',
            'price_monthly' => 3000,
            'capacity' => 2,
            'status' => 'Occupied',
        ]);

        $contract = LeaseContract::create([
            'tenant_id' => $tenant->user_id,
            'room_id' => $room->room_id,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
            'security_deposit' => 3000,
            'contract_status' => 'Active',
        ]);

        $bill = Bill::create([
            'contract_id' => $contract->contract_id,
            'bill_type' => 'Rent',
            'description' => 'Monthly rent',
            'amount_due' => $amount,
            'due_date' => now()->addDays(7)->toDateString(),
            'payment_status' => 'Unpaid',
            'version' => 1,
        ]);

        return [$tenant, $bill];
    }
}
