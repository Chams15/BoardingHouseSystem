<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Payment;
use App\Services\MonthlyBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminBillingController extends Controller
{
    public function index(): Response
    {
        $bills = Bill::with([
            'leaseContract.tenant.tenantProfile',
            'leaseContract.room',
            'payments' => function ($query): void {
                $query->orderByDesc('payment_date');
            },
        ])
            ->orderBy('due_date', 'desc')
            ->get();

        return Inertia::render('admin/billing/index', [
            'bills' => $bills,
        ]);
    }

    public function generateMonthlyBills(MonthlyBillingService $monthlyBilling): RedirectResponse
    {
        $result = $monthlyBilling->run();

        return back()->with('success', sprintf(
            'Monthly billing completed for %s. Created: %d, Marked overdue: %d.',
            $result['month_start'],
            $result['created'],
            $result['marked_overdue'],
        ));
    }

    public function discountBill(Request $request, Bill $bill): RedirectResponse
    {
        return $this->applyBillAdjustment($request, $bill, 'discount');
    }

    public function waiveBill(Request $request, Bill $bill): RedirectResponse
    {
        return $this->applyBillAdjustment($request, $bill, 'waive');
    }

    public function recordOfflinePayment(Request $request, Bill $bill): RedirectResponse
    {
        $validated = $request->validate([
            'reference_no' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            DB::transaction(function () use ($bill, $validated): void {
                /** @var Bill|null $lockedBill */
                $lockedBill = Bill::query()->where('bill_id', $bill->bill_id)->lockForUpdate()->first();

                if (! $lockedBill) {
                    throw new \RuntimeException('Bill not found.');
                }

                if (in_array($lockedBill->payment_status, [Bill::PAYMENT_STATUS_PAID, Bill::PAYMENT_STATUS_WAIVED], true)) {
                    throw new \RuntimeException('This bill is already settled.');
                }

                $amount = round((float) $lockedBill->amount_due, 2);

                if ($amount <= 0) {
                    throw new \RuntimeException('Bill has no remaining balance.');
                }

                Payment::create([
                    'bill_id' => $lockedBill->bill_id,
                    'amount_paid' => $amount,
                    'payment_method' => 'Offline',
                    'provider' => 'offline',
                    'provider_status' => 'paid',
                    'reference_no' => $validated['reference_no'] ?? 'OFF-'.strtoupper(Str::random(8)),
                    'payment_date' => now(),
                    'paid_at' => now(),
                    'provider_metadata' => [
                        'source' => 'admin_offline_payment',
                        'notes' => $validated['notes'] ?? null,
                    ],
                ]);

                $lockedBill->reconcilePaymentStatus();
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Offline payment recorded successfully.');
    }

    private function applyBillAdjustment(Request $request, Bill $bill, string $type): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $amount = round((float) $validated['amount'], 2);

        if ($bill->payment_status === Bill::PAYMENT_STATUS_PAID) {
            return back()->with('error', 'Paid bills cannot be adjusted.');
        }

        try {
            DB::transaction(function () use ($bill, $amount, $validated, $type): void {
                /** @var Bill|null $lockedBill */
                $lockedBill = Bill::query()->where('bill_id', $bill->bill_id)->lockForUpdate()->first();

                if (! $lockedBill) {
                    throw new \RuntimeException('Bill not found.');
                }

                $originalAmount = round((float) ($lockedBill->original_amount_due ?? $lockedBill->amount_due), 2);
                $currentDiscount = round((float) ($lockedBill->discount_amount ?? 0), 2);
                $currentWaived = round((float) ($lockedBill->waived_amount ?? 0), 2);
                $remainingAmount = round($originalAmount - $currentDiscount - $currentWaived, 2);

                if ($amount > $remainingAmount) {
                    throw new \RuntimeException('Adjustment amount exceeds the remaining bill balance.');
                }

                $newDiscount = $currentDiscount;
                $newWaived = $currentWaived;

                if ($type === 'discount') {
                    $newDiscount = round($newDiscount + $amount, 2);
                }

                if ($type === 'waive') {
                    $newWaived = round($newWaived + $amount, 2);
                }

                $newAmountDue = round(max(0, $originalAmount - $newDiscount - $newWaived), 2);

                $lockedBill->update([
                    'original_amount_due' => $lockedBill->original_amount_due ?? $lockedBill->amount_due,
                    'amount_due' => $newAmountDue,
                    'discount_amount' => $newDiscount,
                    'discount_reason' => $type === 'discount' ? $validated['reason'] : $lockedBill->discount_reason,
                    'waived_amount' => $newWaived,
                    'waived_reason' => $type === 'waive' ? $validated['reason'] : $lockedBill->waived_reason,
                    'payment_status' => $newAmountDue <= 0 ? Bill::PAYMENT_STATUS_WAIVED : $lockedBill->payment_status,
                    'version' => $lockedBill->version + 1,
                ]);

                if ($type === 'waive') {
                    Payment::create([
                        'bill_id' => $lockedBill->bill_id,
                        'amount_paid' => $amount,
                        'payment_method' => 'Waiver',
                        'provider' => null,
                        'provider_status' => 'waived',
                        'reference_no' => 'WV-'.strtoupper(Str::random(8)),
                        'payment_date' => now(),
                        'paid_at' => now(),
                        'provider_metadata' => [
                            'source' => 'admin_waiver',
                            'reason' => $validated['reason'],
                            'amount' => $amount,
                        ],
                    ]);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $type === 'discount' ? 'Discount applied successfully.' : 'Bill waived successfully.');
    }
}
