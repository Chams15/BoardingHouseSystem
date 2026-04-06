<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $primaryKey = 'log_id';

    protected $fillable = [
        'actor_user_id',
        'event_type',
        'table_name',
        'record_pk_column',
        'record_pk',
        'old_values',
        'new_values',
        'action_meta',
        'rollback_of_log_id',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'action_meta' => 'array',
            'rollback_of_log_id' => 'integer',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'user_id');
    }

    public function rolledBackTarget(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rollback_of_log_id', 'log_id');
    }
}
