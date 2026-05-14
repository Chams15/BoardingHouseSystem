import { Head } from '@inertiajs/react';
import { BarChart3, DoorClosed, DoorOpen, FileDown, Home, TrendingUp, UserCheck, UserX, Users, Wallet } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';

type Props = {
    stats: {
        totalTenants: number;
        activeTenants: number;
        inactiveTenants: number;
        totalRooms: number;
        availableRooms: number;
        occupiedRooms: number;
    };
    financialReport: {
        monthKey: string;
        monthLabel: string;
        monthStart: string;
        monthEnd: string;
        summary: {
            billed_amount: number;
            collected_amount: number;
            waived_amount: number;
            outstanding_amount: number;
            bill_count: number;
            payment_count: number;
            settled_payment_count: number;
            pending_payment_count: number;
            failed_payment_count: number;
        };
        paymentStatusBreakdown: Record<string, number>;
        monthlyHistory: Array<{
            monthKey: string;
            monthLabel: string;
            bill_count: number;
            billed_amount: number;
            collected_amount: number;
            waived_amount: number;
            payment_count: number;
            settled_count: number;
            pending_count: number;
            failed_count: number;
        }>;
        recentPayments: Array<{
            payment_id: number;
            amount_paid: string;
            payment_method: string;
            reference_no: string | null;
            provider_status: string | null;
            paid_at: string;
            bill: {
                bill_id: number | null;
                bill_type: string | null;
                payment_status: string | null;
                billing_period: string | null;
                tenant_name: string | null;
                room_number: string | null;
            };
        }>;
    };
    financialReports: Array<{
        report_id: number;
        report_month: string;
        report_label: string;
        generated_at: string;
        file_path: string;
        download_url: string;
    }>;
};

const breadcrumbs = [{ title: 'Admin', href: '/admin/dashboard' }, { title: 'Dashboard', href: '/admin/dashboard' }];

const currencyFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

function formatMoney(value: number | string) {
    return currencyFormatter.format(Number(value));
}

export default function AdminDashboard({ stats, financialReport, financialReports }: Props) {
    const cards = [
        { label: 'Total Tenants', value: stats.totalTenants, icon: Users, color: 'bg-blue-500' },
        { label: 'Active Tenants', value: stats.activeTenants, icon: UserCheck, color: 'bg-green-500' },
        { label: 'Inactive Tenants', value: stats.inactiveTenants, icon: UserX, color: 'bg-red-500' },
        { label: 'Total Rooms', value: stats.totalRooms, icon: Home, color: 'bg-purple-500' },
        { label: 'Available Rooms', value: stats.availableRooms, icon: DoorOpen, color: 'bg-emerald-500' },
        { label: 'Occupied Rooms', value: stats.occupiedRooms, icon: DoorClosed, color: 'bg-orange-500' },
    ];

    const reportCards = [
        { label: 'Billed', value: formatMoney(financialReport.summary.billed_amount), icon: Wallet, color: 'bg-slate-900' },
        { label: 'Collected', value: formatMoney(financialReport.summary.collected_amount), icon: TrendingUp, color: 'bg-emerald-600' },
        { label: 'Outstanding', value: formatMoney(financialReport.summary.outstanding_amount), icon: BarChart3, color: 'bg-orange-500' },
        { label: 'Waived', value: formatMoney(financialReport.summary.waived_amount), icon: UserX, color: 'bg-rose-600' },
        { label: 'Payments', value: financialReport.summary.payment_count, icon: Users, color: 'bg-blue-600' },
    ];

    const pdfUrl = `/admin/dashboard/financial-summary.pdf?month=${financialReport.monthKey}`;

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />

            <div className="space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard</h1>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Monthly report for {financialReport.monthLabel} • {financialReport.monthStart} to {financialReport.monthEnd}
                        </p>
                    </div>

                    <Button asChild className="bg-orange-500 text-white hover:bg-orange-600">
                        <a href={pdfUrl}>
                            <FileDown className="mr-2 h-4 w-4" />
                            Export Financial Report PDF
                        </a>
                    </Button>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {cards.map((card) => (
                        <div key={card.label} className="flex items-center gap-4 rounded-xl border bg-white dark:bg-neutral-900 dark:border-neutral-800 p-5 shadow-sm">
                            <div className={`flex h-12 w-12 items-center justify-center rounded-lg ${card.color}`}>
                                <card.icon className="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">{card.label}</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">{card.value}</p>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="space-y-4 rounded-xl border bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div className="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Monthly Financial Summary</h2>
                            <p className="text-sm text-gray-500 dark:text-gray-400">Billed, collected, and outstanding totals for the selected month.</p>
                        </div>
                        <div className="rounded-full border px-3 py-1 text-xs font-medium text-gray-600 dark:border-neutral-700 dark:text-gray-300">
                            {financialReport.summary.bill_count} bills • {financialReport.summary.settled_payment_count} settled • {financialReport.summary.pending_payment_count} pending
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                        {reportCards.map((card) => (
                            <div key={card.label} className="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/50">
                                <div className="flex items-center justify-between">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{card.label}</p>
                                    <div className={`flex h-9 w-9 items-center justify-center rounded-lg ${card.color}`}>
                                        <card.icon className="h-4 w-4 text-white" />
                                    </div>
                                </div>
                                <p className="mt-4 text-2xl font-bold text-gray-900 dark:text-gray-100">{card.value}</p>
                            </div>
                        ))}
                    </div>

                    <div className="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                        <div className="overflow-hidden rounded-xl border dark:border-neutral-800">
                            <div className="border-b bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700 dark:border-neutral-800 dark:bg-neutral-950 dark:text-gray-200">
                                Historical Payment Data
                            </div>
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-white text-gray-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-gray-400">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">Month</th>
                                        <th className="px-4 py-3 font-medium">Billed</th>
                                        <th className="px-4 py-3 font-medium">Collected</th>
                                        <th className="px-4 py-3 font-medium">Payments</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y dark:divide-neutral-800">
                                    {financialReport.monthlyHistory.map((row) => (
                                        <tr key={row.monthKey} className="hover:bg-gray-50 dark:hover:bg-neutral-800">
                                            <td className="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{row.monthLabel}</td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{formatMoney(row.billed_amount)}</td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{formatMoney(row.collected_amount)}</td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-300">
                                                {row.payment_count} total • {row.settled_count} settled • {row.pending_count} pending
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="overflow-hidden rounded-xl border dark:border-neutral-800">
                            <div className="border-b bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700 dark:border-neutral-800 dark:bg-neutral-950 dark:text-gray-200">
                                Recent Payments
                            </div>
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-white text-gray-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-gray-400">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">Bill</th>
                                        <th className="px-4 py-3 font-medium">Amount</th>
                                        <th className="px-4 py-3 font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y dark:divide-neutral-800">
                                    {financialReport.recentPayments.length === 0 ? (
                                        <tr>
                                            <td colSpan={3} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                                No payments found for this period.
                                            </td>
                                        </tr>
                                    ) : (
                                        financialReport.recentPayments.map((payment) => (
                                            <tr key={payment.payment_id} className="align-top hover:bg-gray-50 dark:hover:bg-neutral-800">
                                                <td className="px-4 py-3">
                                                    <div className="space-y-1">
                                                        <p className="font-medium text-gray-900 dark:text-gray-100">{payment.bill.tenant_name ?? 'Unknown tenant'}</p>
                                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                                            {payment.bill.room_number ?? 'No room'} • {payment.bill.bill_type ?? 'Bill'}
                                                        </p>
                                                        <p className="text-xs text-gray-400 dark:text-gray-500">
                                                            {new Date(payment.paid_at).toLocaleString()}
                                                        </p>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{formatMoney(payment.amount_paid)}</td>
                                                <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{payment.provider_status ?? 'unknown'}</td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        {Object.entries(financialReport.paymentStatusBreakdown).map(([status, count]) => (
                            <span key={status} className="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-neutral-800 dark:text-gray-300">
                                {status}: {count}
                            </span>
                        ))}
                    </div>

                    <div className="overflow-hidden rounded-xl border dark:border-neutral-800">
                        <div className="border-b bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700 dark:border-neutral-800 dark:bg-neutral-950 dark:text-gray-200">
                            Saved Monthly Reports
                        </div>
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-white text-gray-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-gray-400">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Month</th>
                                    <th className="px-4 py-3 font-medium">Generated</th>
                                    <th className="px-4 py-3 font-medium text-right">Download</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y dark:divide-neutral-800">
                                {financialReports.length === 0 ? (
                                    <tr>
                                        <td colSpan={3} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No archived reports yet. The scheduler will generate them at month end.
                                        </td>
                                    </tr>
                                ) : (
                                    financialReports.map((report) => (
                                        <tr key={report.report_id} className="hover:bg-gray-50 dark:hover:bg-neutral-800">
                                            <td className="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{report.report_label}</td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-300">
                                                {new Date(report.generated_at).toLocaleString()}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <a
                                                    href={report.download_url}
                                                    className="inline-flex items-center rounded-md border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-neutral-700 dark:text-gray-200 dark:hover:bg-neutral-800"
                                                >
                                                    Download PDF
                                                </a>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
