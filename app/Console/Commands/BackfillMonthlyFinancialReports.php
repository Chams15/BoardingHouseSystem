<?php

namespace App\Console\Commands;

use App\Services\AdminFinancialReportService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class BackfillMonthlyFinancialReports extends Command
{
    protected $signature = 'reports:backfill-monthly-financial {--months=3 : Number of completed months to generate}';

    protected $description = 'Generate and store archived monthly financial reports for the recent completed months';

    public function handle(AdminFinancialReportService $financialReportService): int
    {
        $monthsOption = (int) $this->option('months');
        $monthsToGenerate = max(1, $monthsOption);
        $generated = 0;

        for ($offset = $monthsToGenerate; $offset >= 1; $offset--) {
            $month = CarbonImmutable::now()->subMonths($offset)->startOfMonth();
            $report = $financialReportService->generateAndStoreMonthlyReport($month->format('Y-m'));

            $generated++;
            $this->line(sprintf('✓ Generated %s -> %s', $report->report_label, $report->file_path));
        }

        $this->info(sprintf('Backfill complete. Generated %d monthly report%s.', $generated, $generated === 1 ? '' : 's'));

        return self::SUCCESS;
    }
}
