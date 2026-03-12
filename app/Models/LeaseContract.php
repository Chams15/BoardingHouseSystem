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
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'move_out_req_date' => 'date',
            'security_deposit' => 'decimal:2',
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
}
