<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\MonthlyFinancialReport;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class AdminFinancialReportService
{
    private const HISTORY_MONTHS = 3;

    public function buildMonthlyReport(?string $monthInput = null): array
    {
        $reportMonth = $this->resolveReportMonth($monthInput);
        $monthKey = $reportMonth->format('Y-m');
        $monthLabel = $reportMonth->format('F Y');
        $monthStart = $reportMonth->copy()->startOfMonth();
        $monthEnd = $reportMonth->copy()->endOfMonth();
        $historyStart = $reportMonth->copy()->subMonths(self::HISTORY_MONTHS - 1)->startOfMonth();

        $monthlyBills = Bill::with([
            'leaseContract.room',
            'leaseContract.tenant.tenantProfile',
            'payments' => function ($query): void {
                $query->orderByDesc('payment_date');
            },
        ])
            ->where('billing_period', $monthKey)
            ->get();

        $monthlyPayments = Payment::with([
            'bill.leaseContract.room',
            'bill.leaseContract.tenant.tenantProfile',
        ])
            ->where(function ($query) use ($monthStart, $monthEnd): void {
                $query->whereBetween('paid_at', [$monthStart, $monthEnd])
                    ->orWhereBetween('payment_date', [$monthStart, $monthEnd]);
            })
            ->orderByDesc('payment_date')
            ->get();

        $historyBills = Bill::query()
            ->whereBetween('due_date', [$historyStart, $monthEnd])
            ->get()
            ->groupBy('billing_period');

        $historyPayments = Payment::with([
            'bill.leaseContract.room',
            'bill.leaseContract.tenant.tenantProfile',
        ])
            ->where(function ($query) use ($historyStart, $monthEnd): void {
                $query->whereBetween('paid_at', [$historyStart, $monthEnd])
                    ->orWhereBetween('payment_date', [$historyStart, $monthEnd]);
            })
            ->orderBy('payment_date')
            ->get();

        $billedAmount = round($monthlyBills->sum(function (Bill $bill): float {
            return round((float) ($bill->original_amount_due ?? $bill->amount_due), 2);
        }), 2);

        $discountAmount = round($monthlyBills->sum(function (Bill $bill): float {
            return round((float) ($bill->discount_amount ?? 0), 2);
        }), 2);

        $waivedAmount = round($monthlyBills->sum(function (Bill $bill): float {
            return round((float) ($bill->waived_amount ?? 0), 2);
        }), 2);

        $outstandingAmount = round($monthlyBills->sum(function (Bill $bill): float {
            if (in_array($bill->payment_status, [Bill::PAYMENT_STATUS_PAID, Bill::PAYMENT_STATUS_WAIVED], true)) {
                return 0.0;
            }

            return round((float) $bill->amount_due, 2);
        }), 2);

        $collectedPayments = $monthlyPayments->filter(function (Payment $payment): bool {
            return $this->isCashCollection($payment);
        });

        $waiverPayments = $monthlyPayments->filter(function (Payment $payment): bool {
            return strtolower((string) $payment->provider_status) === 'waived';
        });

        $summary = [
            'billed_amount' => $billedAmount,
            'collected_amount' => round($collectedPayments->sum(fn (Payment $payment): float => round((float) $payment->amount_paid, 2)), 2),
            'waived_amount' => round($waiverPayments->sum(fn (Payment $payment): float => round((float) $payment->amount_paid, 2)), 2),
            'discount_amount' => $discountAmount,
            'outstanding_amount' => $outstandingAmount,
            'bill_count' => $monthlyBills->count(),
            'payment_count' => $monthlyPayments->count(),
            'settled_payment_count' => $collectedPayments->count(),
            'pending_payment_count' => $monthlyPayments->filter(function (Payment $payment): bool {
                return in_array(strtolower((string) $payment->provider_status), Payment::PENDING_PROVIDER_STATUSES, true);
            })->count(),
            'failed_payment_count' => $monthlyPayments->filter(function (Payment $payment): bool {
                return in_array(strtolower((string) $payment->provider_status), Payment::FAILED_PROVIDER_STATUSES, true);
            })->count(),
        ];

        $paymentStatusBreakdown = collect($monthlyBills)
            ->groupBy('payment_status')
            ->map(fn (Collection $items): int => $items->count())
            ->all();

        $historyRows = collect(range(0, self::HISTORY_MONTHS - 1))
            ->map(function (int $offset) use ($historyStart): array {
                $month = $historyStart->copy()->addMonths($offset);

                return [
                    'monthKey' => $month->format('Y-m'),
                    'monthLabel' => $month->format('M Y'),
                ];
            })
            ->all();

        $historyPaymentsByMonth = [];

        foreach ($historyPayments as $payment) {
            $date = $payment->paid_at ?? $payment->payment_date;
            if (! $date) {
                continue;
            }

            $monthKeyValue = $date->format('Y-m');
            $historyPaymentsByMonth[$monthKeyValue] ??= collect();
            $historyPaymentsByMonth[$monthKeyValue] = $historyPaymentsByMonth[$monthKeyValue]->push($payment);
        }

        $monthlyHistory = collect($historyRows)->map(function (array $row) use ($historyBills, $historyPaymentsByMonth): array {
            /** @var Collection<int, Bill> $bills */
            $bills = $historyBills[$row['monthKey']] ?? collect();
            /** @var Collection<int, Payment> $payments */
            $payments = $historyPaymentsByMonth[$row['monthKey']] ?? collect();

            $cashCollectedPayments = $payments->filter(fn (Payment $payment): bool => $this->isCashCollection($payment));
            $waivedPayments = $payments->filter(fn (Payment $payment): bool => strtolower((string) $payment->provider_status) === 'waived');

            return [
                'monthKey' => $row['monthKey'],
                'monthLabel' => $row['monthLabel'],
                'bill_count' => $bills->count(),
                'billed_amount' => round($bills->sum(fn (Bill $bill): float => round((float) ($bill->original_amount_due ?? $bill->amount_due), 2)), 2),
                'collected_amount' => round($cashCollectedPayments->sum(fn (Payment $payment): float => round((float) $payment->amount_paid, 2)), 2),
                'waived_amount' => round($waivedPayments->sum(fn (Payment $payment): float => round((float) $payment->amount_paid, 2)), 2),
                'payment_count' => $payments->count(),
                'settled_count' => $cashCollectedPayments->count(),
                'pending_count' => $payments->filter(function (Payment $payment): bool {
                    return in_array(strtolower((string) $payment->provider_status), Payment::PENDING_PROVIDER_STATUSES, true);
                })->count(),
                'failed_count' => $payments->filter(function (Payment $payment): bool {
                    return in_array(strtolower((string) $payment->provider_status), Payment::FAILED_PROVIDER_STATUSES, true);
                })->count(),
            ];
        })->all();

        $recentPayments = $monthlyPayments
            ->take(12)
            ->map(function (Payment $payment): array {
                $bill = $payment->bill;
                $tenant = $bill?->leaseContract?->tenant;
                $room = $bill?->leaseContract?->room;

                return [
                    'payment_id' => $payment->payment_id,
                    'amount_paid' => $payment->amount_paid,
                    'payment_method' => $payment->payment_method,
                    'reference_no' => $payment->reference_no,
                    'provider_status' => $payment->provider_status,
                    'paid_at' => $payment->paid_at ?? $payment->payment_date,
                    'bill' => [
                        'bill_id' => $bill?->bill_id,
                        'bill_type' => $bill?->bill_type,
                        'payment_status' => $bill?->payment_status,
                        'billing_period' => $bill?->billing_period,
                        'tenant_name' => $tenant?->tenantProfile?->full_name ?? $tenant?->email,
                        'room_number' => $room?->room_number,
                    ],
                ];
            })
            ->values()
            ->all();

        $allPayments = $monthlyPayments
            ->map(function (Payment $payment): array {
                $bill = $payment->bill;
                $tenant = $bill?->leaseContract?->tenant;
                $room = $bill?->leaseContract?->room;

                return [
                    'payment_id' => $payment->payment_id,
                    'amount_paid' => $payment->amount_paid,
                    'payment_method' => $payment->payment_method,
                    'reference_no' => $payment->reference_no,
                    'provider_status' => $payment->provider_status,
                    'paid_at' => $payment->paid_at ?? $payment->payment_date,
                    'bill' => [
                        'bill_id' => $bill?->bill_id,
                        'bill_type' => $bill?->bill_type,
                        'payment_status' => $bill?->payment_status,
                        'billing_period' => $bill?->billing_period,
                        'tenant_name' => $tenant?->tenantProfile?->full_name ?? $tenant?->email,
                        'room_number' => $room?->room_number,
                    ],
                ];
            })
            ->values()
            ->all();

        return [
            'monthKey' => $monthKey,
            'monthLabel' => $monthLabel,
            'monthStart' => $monthStart->toDateString(),
            'monthEnd' => $monthEnd->toDateString(),
            'summary' => $summary,
            'paymentStatusBreakdown' => $paymentStatusBreakdown,
            'monthlyHistory' => $monthlyHistory,
            'recentPayments' => $recentPayments,
            'allPayments' => $allPayments,
        ];
    }

    public function generateAndStoreMonthlyReport(?string $monthInput = null): MonthlyFinancialReport
    {
        $report = $this->buildMonthlyReport($monthInput);
        $filePath = sprintf('reports/financial/financial-summary-%s.pdf', $report['monthKey']);

        $pdf = Pdf::loadView('admin.reports.financial-summary', [
            'report' => $report,
        ]);

        Storage::disk('private')->put($filePath, $pdf->output());

        return MonthlyFinancialReport::updateOrCreate(
            ['report_month' => $report['monthKey']],
            [
                'report_label' => $report['monthLabel'],
                'file_path' => $filePath,
                'summary_payload' => $report,
                'generated_at' => now(),
            ],
        );
    }

    private function resolveReportMonth(?string $monthInput): CarbonImmutable
    {
        try {
            return $monthInput ? CarbonImmutable::parse($monthInput)->startOfMonth() : now()->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    private function isCashCollection(Payment $payment): bool
    {
        if (strtolower((string) $payment->provider_status) === 'waived') {
            return false;
        }

        return $payment->isSettled();
    }
}
