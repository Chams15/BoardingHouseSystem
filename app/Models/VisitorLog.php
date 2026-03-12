<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorLog extends Model
{
    protected $primaryKey = 'log_id';

    protected $fillable = [
        'tenant_visited',
        'visitor_name',
        'purpose',
        'time_in',
        'time_out',
    ];

    protected function casts(): array
    {
        return [
            'time_in' => 'datetime',
            'time_out' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_visited', 'user_id');
    }
}
