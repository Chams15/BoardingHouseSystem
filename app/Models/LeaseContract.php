<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaseContract extends Model
{
    protected $primaryKey = 'contract_id';

    protected $fillable = [
        'tenant_id',
        'room_id',
        'start_date',
        'end_date',
        'security_deposit',
        'contract_status',
        'move_out_req_date',
        'auto_renew',
        'next_renewal_date',
        'renewal_cancel_requested_date',
        'move_out_final_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'move_out_req_date' => 'date',
            'security_deposit' => 'decimal:2',
            'auto_renew' => 'boolean',
            'next_renewal_date' => 'date',
            'renewal_cancel_requested_date' => 'date',
            'move_out_final_date' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id', 'user_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'contract_id', 'contract_id');
    }

    /**
     * Check if this lease is eligible for auto-renewal
     */
    public function isEligibleForAutoRenewal(): bool
    {
        return $this->auto_renew
            && $this->contract_status === 'Active'
            && $this->next_renewal_date !== null
            && $this->next_renewal_date->isPast();
    }

    /**
     * Request cancellation of auto-renewal
     * 
     * Tenant must provide 7 days notice before the lease stops auto-renewing.
     * The lease will complete its current month and then terminate.
     * 
     * @throws \Exception if cancellation request cannot be honored
     */
    public function requestAutoRenewalCancellation(): void
    {
        if ($this->contract_status !== 'Active') {
            throw new \Exception('Cannot cancel auto-renewal for non-active leases');
        }

        $today = now()->startOfDay();
        $renewalDate = $this->next_renewal_date->startOfDay();
        $daysUntilRenewal = $today->diffInDays($renewalDate);

        // Disable auto-renewal immediately
        $this->auto_renew = false;

        // If renewal is in less than 7 days, tenant must wait until next month
        if ($daysUntilRenewal < 7) {
            // Set cancellation to take effect next month
            $this->renewal_cancel_requested_date = $today;
            $this->move_out_final_date = $renewalDate->copy()->addMonth();
            $this->contract_status = 'Pending_MoveOut';
            $this->move_out_req_date = $today;
        } else {
            // Can cancel on current renewal date
            $this->renewal_cancel_requested_date = $today;
            $this->move_out_final_date = $renewalDate;
            $this->contract_status = 'Pending_MoveOut';
            $this->move_out_req_date = $today;
        }

        $this->save();
    }

    /**
     * Auto-renew this lease to the next month
     * 
     * Called by the auto-renewal scheduler. Extends the lease by one month
     * and updates the next renewal date.
     */
    public function autoRenew(): void
    {
        if (!$this->isEligibleForAutoRenewal()) {
            return;
        }

        // Extend end_date by one month
        $newEndDate = $this->end_date->copy()->addMonth();
        $newRenewalDate = $this->next_renewal_date->copy()->addMonth();

        $this->update([
            'end_date' => $newEndDate,
            'next_renewal_date' => $newRenewalDate,
        ]);
    }

    /**
     * Complete the move-out process and terminate the lease
     * 
     * Called when the tenant's final day arrives
     */
    public function completeMoveOut(): void
    {
        $this->contract_status = 'Terminated';
        $this->auto_renew = false;
        $this->next_renewal_date = null;
        $this->save();

        // Update room status to available
        $this->room->update(['status' => 'Available']);
    }
}
