<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $primaryKey = 'payment_id';

    public const SETTLED_PROVIDER_STATUSES = ['paid', 'validated', 'approved', 'authorized', 'authorised', 'succeeded', 'completed', 'success'];

    public const PENDING_PROVIDER_STATUSES = ['pending', 'processing', 'awaiting_payment_method', 'awaiting_next_action', 'active'];

    public const FAILED_PROVIDER_STATUSES = ['failed', 'canceled', 'cancelled', 'declined', 'expired'];

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

    public function isSettled(): bool
    {
        $providerStatus = strtolower((string) $this->provider_status);

        if (in_array($providerStatus, self::SETTLED_PROVIDER_STATUSES, true)) {
            return true;
        }

        return $this->paid_at !== null;
    }
}
