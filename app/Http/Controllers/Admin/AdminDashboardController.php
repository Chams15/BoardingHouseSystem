<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonthlyFinancialReport;
use App\Models\Room;
use App\Models\User;
use App\Services\AdminFinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDashboardController extends Controller
{
    public function index(Request $request, AdminFinancialReportService $financialReportService): Response
    {
        $financialReport = $financialReportService->buildMonthlyReport($request->string('month')->toString());

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'totalTenants' => User::where('role', 'Tenant')->count(),
                'activeTenants' => User::where('role', 'Tenant')->where('is_active', true)->count(),
                'inactiveTenants' => User::where('role', 'Tenant')->where('is_active', false)->count(),
                'totalRooms' => Room::count(),
                'availableRooms' => Room::where('status', 'Available')->count(),
                'occupiedRooms' => Room::where('status', 'Occupied')->count(),
            ],
            'financialReport' => $financialReport,
            'financialReports' => MonthlyFinancialReport::query()
                ->orderByDesc('report_month')
                ->limit(6)
                ->get()
                ->map(function (MonthlyFinancialReport $report): array {
                    return [
                        'report_id' => $report->report_id,
                        'report_month' => $report->report_month,
                        'report_label' => $report->report_label,
                        'generated_at' => $report->generated_at,
                        'file_path' => $report->file_path,
                        'download_url' => route('admin.dashboard.financial-reports.download', ['financialReport' => $report->report_id]),
                    ];
                }),
        ]);
    }

    public function exportFinancialReport(Request $request, AdminFinancialReportService $financialReportService): StreamedResponse
    {
        $report = $financialReportService->generateAndStoreMonthlyReport($request->string('month')->toString());

        return Storage::disk('private')->download(
            $report->file_path,
            sprintf('financial-summary-%s.pdf', $report->report_month),
        );
    }

    public function downloadFinancialReport(MonthlyFinancialReport $financialReport): StreamedResponse
    {
        abort_unless(Storage::disk('private')->exists($financialReport->file_path), 404, 'Financial report not found.');

        return Storage::disk('private')->download(
            $financialReport->file_path,
            sprintf('financial-summary-%s.pdf', $financialReport->report_month),
        );
    }
}
