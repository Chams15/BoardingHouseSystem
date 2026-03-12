<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $primaryKey = 'room_id';

    protected $fillable = [
        'room_number',
        'category',
        'price_monthly',
        'capacity',
        'status',
        'amenities',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
        ];
    }

    public function leaseContracts(): HasMany
    {
        return $this->hasMany(LeaseContract::class, 'room_id', 'room_id');
    }

    public function maintenanceTickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class, 'room_id', 'room_id');
    }

    public function roomRequests(): HasMany
    {
        return $this->hasMany(RoomRequest::class, 'room_id', 'room_id');
    }
}
