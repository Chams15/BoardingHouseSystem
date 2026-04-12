<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\LeaseContract;
use App\Models\Payment;
use App\Models\Room;
use App\Models\TenantProfile;
use App\Models\User;
use App\Services\PayMongoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class BillingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_initiation_sets_bill_to_pending_and_creates_pending_payment(): void
    {
        config()->set('services.paymongo.secret_key', 'sk_test_123');

        [$tenant, $bill] = $this->createTenantAndBill(now()->addDays(5)->toDateString(), 'Unpaid');

        $this->mock(PayMongoService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn([
                    'data' => [
                        'id' => 'cs_test_123',
                        'attributes' => [
                            'checkout_url' => 'https://checkout.test/session/cs_test_123',
                            'payment_intent' => ['id' => 'pi_test_123'],
                            'expires_at' => now()->addMinutes(30)->toIso8601String(),
                        ],
                    ],
                ]);

            $mock->shouldReceive('extractCheckoutDetails')
                ->once()
                ->andReturn([
                    'checkout_session_id' => 'cs_test_123',
                    'checkout_url' => 'https://checkout.test/session/cs_test_123',
                    'payment_intent_id' => 'pi_test_123',
                    'expires_at' => now()->addMinutes(30),
                ]);
        });

        $response = $this->actingAs($tenant)->post(route('billing.pay', $bill), [
            'version' => $bill->version,
        ]);

        $this->assertContains($response->status(), [302, 303, 409]);

        $bill->refresh();
        $this->assertSame('Pending', $bill->payment_status);

        $this->assertDatabaseHas('payments', [
            'bill_id' => $bill->bill_id,
            'payment_method' => 'Online',
            'provider' => 'paymongo',
            'provider_status' => 'pending',
            'provider_checkout_session_id' => 'cs_test_123',
            'provider_payment_intent_id' => 'pi_test_123',
        ]);
    }

    public function test_cancelled_checkout_restores_unsettled_bill_status(): void
    {
        [$tenant, $bill] = $this->createTenantAndBill(now()->addDays(3)->toDateString(), 'Pending');

        $this->actingAs($tenant)
            ->get(route('billing.paymongo.return', ['bill' => $bill->bill_id, 'status' => 'cancel']))
            ->assertRedirect(route('dashboard'));

        $bill->refresh();
        $this->assertSame('Unpaid', $bill->payment_status);

        $overdueBill = Bill::create([
            'contract_id' => $bill->contract_id,
            'bill_type' => 'Rent',
            'description' => 'Old rent',
            'amount_due' => 2000,
            'due_date' => now()->subDay()->toDateString(),
            'payment_status' => 'Pending',
            'version' => 1,
        ]);

        $this->actingAs($tenant)
            ->get(route('billing.paymongo.return', ['bill' => $overdueBill->bill_id, 'status' => 'cancel']))
            ->assertRedirect(route('dashboard'));

        $overdueBill->refresh();
        $this->assertSame('Overdue', $overdueBill->payment_status);
    }

    public function test_paid_webhook_marks_payment_and_bill_as_paid(): void
    {
        config()->set('services.paymongo.webhook_secret', 'whsec_test_123');

        [, $bill] = $this->createTenantAndBill(now()->addDays(5)->toDateString(), 'Pending');

        $payment = Payment::create([
            'bill_id' => $bill->bill_id,
            'amount_paid' => $bill->amount_due,
            'payment_method' => 'Online',
            'provider' => 'paymongo',
            'provider_status' => 'pending',
            'provider_checkout_session_id' => 'cs_live_999',
            'payment_date' => now(),
            'reference_no' => 'REF999',
        ]);

        $payload = [
            'data' => [
                'id' => 'evt_123',
                'attributes' => [
                    'type' => 'checkout_session.payment.paid',
                    'data' => [
                        'id' => 'cs_live_999',
                        'attributes' => [
                            'status' => 'paid',
                            'payment_intent' => [
                                'id' => 'pi_live_999',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $jsonPayload, 'whsec_test_123');

        $this->postJson(route('billing.paymongo.webhook'), $payload, [
            'Paymongo-Signature' => $signature,
        ])->assertOk();

        $payment->refresh();
        $bill->refresh();

        $this->assertSame('paid', $payment->provider_status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('Paid', $bill->payment_status);
    }

    /**
     * @return array{0: User, 1: Bill}
     */
    private function createTenantAndBill(string $dueDate, string $billStatus): array
    {
        $tenant = User::factory()->create([
            'role' => 'Tenant',
        ]);

        TenantProfile::create([
            'user_id' => $tenant->user_id,
            'full_name' => 'Test Tenant',
            'contact_number' => '09171234567',
        ]);

        $room = Room::create([
            'room_number' => 'A-101',
            'category' => 'Standard',
            'price_monthly' => 3000,
            'capacity' => 2,
            'status' => 'Occupied',
        ]);

        $contract = LeaseContract::create([
            'tenant_id' => $tenant->user_id,
            'room_id' => $room->room_id,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'security_deposit' => 3000,
            'contract_status' => 'Active',
        ]);

        $bill = Bill::create([
            'contract_id' => $contract->contract_id,
            'bill_type' => 'Rent',
            'description' => 'Test monthly rent',
            'amount_due' => 3000,
            'due_date' => $dueDate,
            'payment_status' => $billStatus,
            'version' => 1,
        ]);

        return [$tenant, $bill];
    }
}
