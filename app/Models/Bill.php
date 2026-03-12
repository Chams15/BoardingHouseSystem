<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    protected $primaryKey = 'bill_id';

    protected $fillable = [
        'contract_id',
        'bill_type',
        'description',
        'amount_due',
        'due_date',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'amount_due' => 'decimal:2',
            'due_date' => 'date',
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
}
