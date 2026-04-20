<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\LeaseContract;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyBillingHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_billing_generation_creates_one_rent_bill_per_active_contract_for_current_month(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        $contract = $this->createActiveContract(4200);
        $monthStart = now()->startOfMonth()->toDateString();

        $this->actingAs($admin)->post(route('admin.billing.generate-monthly'))->assertRedirect();
        $this->actingAs($admin)->post(route('admin.billing.generate-monthly'))->assertRedirect();

        $this->assertSame(
            1,
            Bill::query()
                ->where('contract_id', $contract->contract_id)
                ->where('bill_type', 'Rent')
                ->whereDate('due_date', $monthStart)
                ->count(),
            'Monthly generation should not duplicate rent bills for the same contract and month.'
        );

        $bill = Bill::query()
            ->where('contract_id', $contract->contract_id)
            ->where('bill_type', 'Rent')
            ->whereDate('due_date', $monthStart)
            ->first();

        $this->assertNotNull($bill);
        $this->assertSame('4200.00', (string) $bill->amount_due);
        $this->assertContains($bill->payment_status, [Bill::PAYMENT_STATUS_UNPAID, Bill::PAYMENT_STATUS_OVERDUE]);
    }

    public function test_monthly_billing_artisan_command_uses_lease_rows_to_generate_bills(): void
    {
        $contract = $this->createActiveContract(3900);
        $monthStart = now()->startOfMonth()->toDateString();

        $this->artisan('billing:generate-monthly')->assertSuccessful();

        $bill = Bill::query()
            ->where('contract_id', $contract->contract_id)
            ->where('bill_type', 'Rent')
            ->whereDate('due_date', $monthStart)
            ->first();

        $this->assertNotNull($bill);
        $this->assertSame('3900.00', (string) $bill->amount_due);
        $this->assertSame(Bill::PAYMENT_STATUS_UNPAID, $bill->payment_status);
    }

    public function test_unpaid_bill_is_tagged_overdue_when_checked_after_due_date(): void
    {
        $contract = $this->createActiveContract(3500);

        $bill = Bill::create([
            'contract_id' => $contract->contract_id,
            'bill_type' => 'Rent',
            'description' => 'Rent for prior month',
            'amount_due' => 3500,
            'due_date' => now()->subDay()->toDateString(),
            'payment_status' => Bill::PAYMENT_STATUS_UNPAID,
            'version' => 1,
        ]);

        $bill->reconcilePaymentStatus();
        $bill->refresh();

        $this->assertSame(Bill::PAYMENT_STATUS_OVERDUE, $bill->payment_status);
    }

    private function createActiveContract(float $monthlyRent): LeaseContract
    {
        $tenant = User::factory()->create([
            'role' => 'Tenant',
        ]);

        $room = Room::create([
            'room_number' => 'C-101',
            'category' => 'Standard',
            'price_monthly' => $monthlyRent,
            'capacity' => 2,
            'status' => 'Occupied',
        ]);

        return LeaseContract::create([
            'tenant_id' => $tenant->user_id,
            'room_id' => $room->room_id,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'security_deposit' => $monthlyRent,
            'contract_status' => 'Active',
        ]);
    }
}
