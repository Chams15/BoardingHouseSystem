<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyFinancialReport extends Model
{
    protected $primaryKey = 'report_id';

    protected $fillable = [
        'report_month',
        'report_label',
        'file_path',
        'summary_payload',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'summary_payload' => 'array',
            'generated_at' => 'datetime',
        ];
    }
}
