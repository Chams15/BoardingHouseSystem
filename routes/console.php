<?php

use App\Services\MonthlyBillingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('billing:generate-monthly {--month= : Target month in YYYY-MM format}', function (MonthlyBillingService $monthlyBilling): int {
    $monthOption = $this->option('month');
    $billingDate = null;

    if (is_string($monthOption) && trim($monthOption) !== '') {
        $parsed = \Carbon\Carbon::createFromFormat('Y-m', trim($monthOption));

        if ($parsed === false) {
            $this->error('Invalid month format. Use YYYY-MM.');

            return self::FAILURE;
        }

        $billingDate = $parsed;
    }

    $result = $monthlyBilling->run($billingDate);

    $this->info(sprintf(
        'Monthly billing completed for %s. Created: %d, Marked overdue: %d.',
        $result['month_start'],
        $result['created'],
        $result['marked_overdue'],
    ));

    return self::SUCCESS;
})->purpose('Generate monthly rent bills and mark overdue unpaid bills');

Schedule::command('billing:generate-monthly')->monthlyOn(1, '00:10');
Schedule::command('reports:generate-monthly-financial')->dailyAt('23:55')->withoutOverlapping();
