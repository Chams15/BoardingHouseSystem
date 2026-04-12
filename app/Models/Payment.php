<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Payment extends Model
{
    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'bill_id',
        'amount_paid',
        'payment_method',
        'reference_no',
        'receipt_url',
        'payment_date',
        'provider',
        'provider_checkout_session_id',
        'provider_payment_intent_id',
        'provider_event_id',
        'provider_status',
        'checkout_url',
        'provider_metadata',
        'checkout_expires_at',
        'paid_at',
        'failure_message',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'payment_date' => 'datetime',
            'checkout_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'provider_metadata' => 'json',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'bill_id');
    }

    protected static function booted(): void
    {
        static::saved(function (Payment $payment): void {
            if (! $payment->isSettled()) {
                return;
            }

            DB::transaction(function () use ($payment): void {
                $bill = Bill::where('bill_id', $payment->bill_id)->lockForUpdate()->first();

                if (! $bill || $bill->payment_status === 'Paid') {
                    return;
                }

                $bill->update([
                    'payment_status' => 'Paid',
                    'version' => $bill->version + 1,
                ]);
            });
        });
    }

    private function isSettled(): bool
    {
        $providerStatus = strtolower((string) $this->provider_status);

        if (in_array($providerStatus, ['paid', 'succeeded', 'completed', 'success'], true)) {
            return true;
        }

        return $this->paid_at !== null;
    }
}
