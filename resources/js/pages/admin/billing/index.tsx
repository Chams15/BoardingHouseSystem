import { Head, router, usePage } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';

type Payment = {
    payment_id: number;
    amount_paid: string;
    payment_method: string;
    reference_no: string | null;
    payment_date: string;
};

type BillItem = {
    bill_id: number;
    bill_type: string;
    description: string | null;
    amount_due: string;
    due_date: string;
    payment_status: 'Unpaid' | 'Paid' | 'Overdue' | 'Waived';
    payments: Payment[];
    lease_contract: {
        room: {
            room_number: string;
        };
        tenant: {
            email: string;
            tenant_profile?: {
                full_name: string;
            };
        };
    };
};

type Props = {
    bills: BillItem[];
};

const breadcrumbs = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Billing', href: '/admin/billing' },
];

const statusClass: Record<string, string> = {
    Unpaid: 'bg-yellow-100 text-yellow-700',
    Paid: 'bg-green-100 text-green-700',
    Overdue: 'bg-red-100 text-red-700',
    Waived: 'bg-gray-100 text-gray-600',
};

export default function AdminBillingIndex({ bills }: Props) {
    const { props } = usePage();
    const flash = props.flash as { success?: string; error?: string } | undefined;

    function handleGenerate() {
        if (confirm('Generate rent bills for all active tenants for this month?')) {
            router.post('/admin/billing/generate-monthly');
        }
    }

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Billing</h1>
                    <Button onClick={handleGenerate} className="bg-orange-500 hover:bg-orange-600 text-white">
                        <RefreshCw className="mr-2 h-4 w-4" />
                        Generate Monthly Bills
                    </Button>
                </div>

                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        {flash.error}
                    </div>
                )}

                <div className="overflow-hidden rounded-xl border bg-white dark:bg-neutral-900 dark:border-neutral-800 shadow-sm">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-gray-50 dark:bg-neutral-800 dark:border-neutral-700 text-gray-600 dark:text-gray-400">
                            <tr>
                                <th className="px-4 py-3 font-medium">Tenant</th>
                                <th className="px-4 py-3 font-medium">Room</th>
                                <th className="px-4 py-3 font-medium">Description</th>
                                <th className="px-4 py-3 font-medium">Amount</th>
                                <th className="px-4 py-3 font-medium">Due Date</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 font-medium">Payment Method</th>
                                <th className="px-4 py-3 font-medium">Paid On</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y dark:divide-neutral-800">
                            {bills.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        No billing records yet.
                                    </td>
                                </tr>
                            ) : (
                                bills.map((bill) => {
                                    const latestPayment = bill.payments[0] ?? null;
                                    return (
                                        <tr key={bill.bill_id} className="hover:bg-gray-50 dark:hover:bg-neutral-800">
                                            <td className="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                                                {bill.lease_contract.tenant.tenant_profile?.full_name ??
                                                    bill.lease_contract.tenant.email}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                {bill.lease_contract.room.room_number}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                {bill.description ?? bill.bill_type}
                                            </td>
                                            <td className="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                                                ₱{Number(bill.amount_due).toLocaleString()}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                {new Date(bill.due_date).toLocaleDateString()}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span
                                                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${statusClass[bill.payment_status] ?? ''}`}
                                                >
                                                    {bill.payment_status}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                {latestPayment?.payment_method ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                {latestPayment
                                                    ? new Date(latestPayment.payment_date).toLocaleDateString()
                                                    : '—'}
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}
