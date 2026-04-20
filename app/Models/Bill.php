<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Bill extends Model
{
    protected $primaryKey = 'bill_id';

    public const PAYMENT_STATUS_UNPAID = 'Unpaid';

    public const PAYMENT_STATUS_PENDING = 'Pending';

    public const PAYMENT_STATUS_PAID = 'Paid';

    public const PAYMENT_STATUS_OVERDUE = 'Overdue';

    public const PAYMENT_STATUS_WAIVED = 'Waived';

    protected $fillable = [
        'contract_id',
        'bill_type',
        'description',
        'original_amount_due',
        'amount_due',
        'due_date',
        'payment_status',
        'discount_amount',
        'discount_reason',
        'waived_amount',
        'waived_reason',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'original_amount_due' => 'decimal:2',
            'amount_due' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'waived_amount' => 'decimal:2',
            'due_date'   => 'date',
            'version'    => 'integer',
        ];
    }

    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(LeaseContract::class, 'contract_id', 'contract_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'bill_id', 'bill_id');
    }

    public function markAsPending(): void
    {
        $this->updatePaymentStatus(self::PAYMENT_STATUS_PENDING);
    }

    public function markAsPaid(): void
    {
        $this->updatePaymentStatus(self::PAYMENT_STATUS_PAID);
    }

    public function reconcilePaymentStatus(): void
    {
        DB::transaction(function (): void {
            $bill = self::query()->where('bill_id', $this->bill_id)->lockForUpdate()->first();

            if (! $bill || in_array($bill->payment_status, [self::PAYMENT_STATUS_PAID, self::PAYMENT_STATUS_WAIVED], true)) {
                return;
            }

            $nextStatus = $bill->resolvePaymentStatusFromPayments();

            if ($bill->payment_status === $nextStatus) {
                return;
            }

            $bill->update([
                'payment_status' => $nextStatus,
                'version' => $bill->version + 1,
            ]);
        });
    }

    private function updatePaymentStatus(string $status): void
    {
        DB::transaction(function () use ($status): void {
            $bill = self::query()->where('bill_id', $this->bill_id)->lockForUpdate()->first();

            if (! $bill || in_array($bill->payment_status, [self::PAYMENT_STATUS_PAID, self::PAYMENT_STATUS_WAIVED], true)) {
                return;
            }

            if ($bill->payment_status === $status) {
                return;
            }

            $bill->update([
                'payment_status' => $status,
                'version' => $bill->version + 1,
            ]);
        });
    }

    private function resolvePaymentStatusFromPayments(): string
    {
        $successfulPayment = $this->payments()
            ->whereIn('provider_status', Payment::SETTLED_PROVIDER_STATUSES)
            ->latest('paid_at')
            ->first();

        if ($successfulPayment) {
            return self::PAYMENT_STATUS_PAID;
        }

        $pendingPayment = $this->payments()
            ->whereIn('provider_status', Payment::PENDING_PROVIDER_STATUSES)
            ->where(function ($query) {
                $query->whereNull('checkout_expires_at')
                    ->orWhere('checkout_expires_at', '>', now());
            })
            ->exists();

        if ($pendingPayment) {
            return self::PAYMENT_STATUS_PENDING;
        }

        return $this->resolveUnsettledStatus();
    }

    private function resolveUnsettledStatus(): string
    {
        if ($this->due_date && $this->due_date->isPast()) {
            return self::PAYMENT_STATUS_OVERDUE;
        }

        return self::PAYMENT_STATUS_UNPAID;
    }
}
