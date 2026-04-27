<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\LeaseContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyBillingService
{
    /**
     * Generate monthly rent bills for all active tenants.
     *
     * This service ensures:
     * - Each tenant is billed exactly once per month for rent
     * - Database-level constraints prevent duplicate bills
     * - Idempotent: Safe to run multiple times without creating duplicates
     * - Atomic: Uses transactions for data consistency
     * - Concurrent-safe: Uses database locks to prevent race conditions
     *
     * @param  \Carbon\Carbon|null  $billingDate  The date to use for billing (defaults to now)
     * @return array{created:int, skipped:int, marked_overdue:int, month_start:string, errors:array}
     */
    public function run(?Carbon $billingDate = null): array
    {
        $billingDate ??= now();
        $monthStart = $billingDate->copy()->startOfMonth();
        $billingPeriod = Bill::generateBillingPeriod($monthStart);
        $today = now()->startOfDay();

        $created = 0;
        $skipped = 0;
        $markedOverdue = 0;
        $errors = [];

        // Wrap everything in a transaction to ensure atomicity
        try {
            DB::transaction(function () use ($monthStart, $billingPeriod, $today, &$created, &$skipped, &$markedOverdue, &$errors): void {
                // Get all active contracts with pessimistic locking
                $contracts = LeaseContract::query()
                    ->with('room')
                    ->where('contract_status', 'Active')
                    ->lockForUpdate()  // Prevent other processes from modifying these during the transaction
                    ->get();

                foreach ($contracts as $contract) {
                    if (! $contract->room) {
                        continue;
                    }

                    try {
                        $this->createRentBillForContract(
                            $contract,
                            $monthStart,
                            $billingPeriod,
                            $created,
                            $skipped
                        );
                    } catch (\Exception $e) {
                        $errors[] = [
                            'contract_id' => $contract->contract_id,
                            'message' => $e->getMessage(),
                        ];
                    }
                }

                // Mark bills as overdue
                $markedOverdue = $this->markOverdueBills($today);
            });
        } catch (\Exception $e) {
            // Log the error but don't throw to allow partial success
            $errors[] = [
                'type' => 'transaction_failed',
                'message' => $e->getMessage(),
            ];
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'marked_overdue' => $markedOverdue,
            'month_start' => $monthStart->toDateString(),
            'errors' => $errors,
        ];
    }

    /**
     * Create a rent bill for a single contract.
     *
     * Uses the unique constraint to prevent duplicate bills.
     * If a bill already exists for this period, it's silently skipped.
     *
     * @throws \Illuminate\Database\UniqueConstraintViolationException
     */
    private function createRentBillForContract(
        LeaseContract $contract,
        $monthStart,
        string $billingPeriod,
        int &$created,
        int &$skipped
    ): void {
        $amountDue = (float) $contract->room->price_monthly;

        // First, do a simple check before attempting insert
        // This allows us to skip duplicate checks early
        $existingBill = Bill::query()
            ->where('contract_id', $contract->contract_id)
            ->where('bill_type', 'Rent')
            ->where('billing_period', $billingPeriod)
            ->lockForUpdate()  // Lock to prevent race conditions
            ->first();

        if ($existingBill) {
            $skipped++;
            return;
        }

        // Create the new bill
        // The database unique constraint will prevent duplicates if we somehow get here twice
        Bill::create([
            'contract_id' => $contract->contract_id,
            'bill_type' => 'Rent',
            'billing_period' => $billingPeriod,
            'description' => 'Rent for '.$monthStart->format('F Y'),
            'original_amount_due' => $amountDue,
            'amount_due' => $amountDue,
            'due_date' => $monthStart->toDateString(),
            'payment_status' => Bill::PAYMENT_STATUS_UNPAID,
            'discount_amount' => 0,
            'waived_amount' => 0,
            'version' => 1,
        ]);

        $created++;
    }

    /**
     * Mark bills as overdue if they are past due and not yet settled.
     */
    private function markOverdueBills($today): int
    {
        $markedOverdue = 0;

        $candidateBills = Bill::query()
            ->whereDate('due_date', '<', $today->toDateString())
            ->whereNotIn('payment_status', [
                Bill::PAYMENT_STATUS_PAID,
                Bill::PAYMENT_STATUS_WAIVED,
                Bill::PAYMENT_STATUS_OVERDUE,
            ])
            ->lockForUpdate()  // Prevent concurrent modifications
            ->get();

        foreach ($candidateBills as $bill) {
            $previousStatus = $bill->payment_status;

            // Reconcile payment status based on actual payments
            $bill->reconcilePaymentStatus();
            $bill->refresh();

            // Only count if status actually changed to overdue
            if ($previousStatus !== Bill::PAYMENT_STATUS_OVERDUE && $bill->payment_status === Bill::PAYMENT_STATUS_OVERDUE) {
                $markedOverdue++;
            }
        }

        return $markedOverdue;
    }
}
