<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\LeaseContract;
use Carbon\Carbon;

class MonthlyBillingService
{
    /**
     * @return array{created:int, marked_overdue:int, month_start:string}
     */
    public function run(?Carbon $billingDate = null): array
    {
        $billingDate ??= now();
        $monthStart = $billingDate->copy()->startOfMonth();
        $today = now()->startOfDay();

        $created = 0;
        $markedOverdue = 0;
        $createdBillIds = [];

        $contracts = LeaseContract::query()
            ->with('room')
            ->where('contract_status', 'Active')
            ->get();

        foreach ($contracts as $contract) {
            if (! $contract->room) {
                continue;
            }

            $amountDue = (float) $contract->room->price_monthly;

            $existing = Bill::query()
                ->where('contract_id', $contract->contract_id)
                ->where('bill_type', 'Rent')
                ->whereDate('due_date', $monthStart->toDateString())
                ->first();

            if ($existing) {
                continue;
            }

            $bill = Bill::create([
                'contract_id' => $contract->contract_id,
                'bill_type' => 'Rent',
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
            $createdBillIds[] = $bill->bill_id;
        }

        $candidateBills = Bill::query()
            ->whereDate('due_date', '<', $today->toDateString())
            ->whereNotIn('payment_status', [
                Bill::PAYMENT_STATUS_PAID,
                Bill::PAYMENT_STATUS_WAIVED,
                Bill::PAYMENT_STATUS_OVERDUE,
            ])
            ->when($createdBillIds !== [], function ($query) use ($createdBillIds) {
                $query->whereNotIn('bill_id', $createdBillIds);
            })
            ->get();

        foreach ($candidateBills as $bill) {
            $previousStatus = $bill->payment_status;
            $bill->reconcilePaymentStatus();
            $bill->refresh();

            if ($previousStatus !== Bill::PAYMENT_STATUS_OVERDUE && $bill->payment_status === Bill::PAYMENT_STATUS_OVERDUE) {
                $markedOverdue++;
            }
        }

        return [
            'created' => $created,
            'marked_overdue' => $markedOverdue,
            'month_start' => $monthStart->toDateString(),
        ];
    }
}
