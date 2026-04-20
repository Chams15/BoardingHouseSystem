<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantProfile extends Model
{
    protected $primaryKey = 'profile_id';

    public const VERIFICATION_NOT_SUBMITTED = 'Not_Submitted';

    public const VERIFICATION_PENDING = 'Pending';

    public const VERIFICATION_APPROVED = 'Approved';

    public const VERIFICATION_REJECTED = 'Rejected';

    protected $fillable = [
        'user_id',
        'full_name',
        'contact_number',
        'contact_address',
        'id_doc_url',
        'emergency_contact',
        'verification_status',
        'verification_note',
        'verification_submitted_at',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'verification_submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'user_id');
    }

    public function isApproved(): bool
    {
        return $this->verification_status === self::VERIFICATION_APPROVED;
    }
}
