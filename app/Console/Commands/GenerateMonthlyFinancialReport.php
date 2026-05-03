<?php

namespace App\Console\Commands;

use App\Services\AdminFinancialReportService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class GenerateMonthlyFinancialReport extends Command
{
    protected $signature = 'reports:generate-monthly-financial {--month= : Target month in YYYY-MM format}';

    protected $description = 'Generate and store the monthly financial report PDF for later download';

    public function handle(AdminFinancialReportService $financialReportService): int
    {
        $monthOption = trim((string) $this->option('month'));
        $targetMonth = null;

        if ($monthOption !== '') {
            try {
                $targetMonth = CarbonImmutable::createFromFormat('Y-m', $monthOption)->startOfMonth();
            } catch (\Throwable) {
                $this->error('Invalid month format. Use YYYY-MM.');

                return self::FAILURE;
            }
        }

        if ($targetMonth === null) {
            $today = now();

            if ($today->addDay()->month === $today->month) {
                $this->info('Skipped: monthly financial report is only generated on the last day of the month.');

                return self::SUCCESS;
            }
        }

        $report = $financialReportService->generateAndStoreMonthlyReport($targetMonth?->format('Y-m'));

        $this->info(sprintf('Financial report generated for %s and stored at %s.', $report->report_label, $report->file_path));

        return self::SUCCESS;
    }
}
