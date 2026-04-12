import { Head, router, usePage } from '@inertiajs/react';
import { RefreshCw, ChevronDown, Eye } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import AdminLayout from '@/layouts/admin-layout';

type Payment = {
    payment_id: number;
    amount_paid: string;
    payment_method: string;
    reference_no: string | null;
    payment_date: string;
    provider: string | null;
    provider_status: string | null;
    provider_checkout_session_id: string | null;
    provider_payment_intent_id: string | null;
    checkout_url: string | null;
    paid_at: string | null;
    failure_message: string | null;
};

type BillItem = {
    bill_id: number;
    bill_type: string;
    description: string | null;
    amount_due: string;
    due_date: string;
    payment_status: 'Unpaid' | 'Pending' | 'Paid' | 'Overdue' | 'Waived';
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
    Pending: 'bg-blue-100 text-blue-700',
    Paid: 'bg-green-100 text-green-700',
    Overdue: 'bg-red-100 text-red-700',
    Waived: 'bg-gray-100 text-gray-600',
};

const providerStatusClass: Record<string, string> = {
    pending: 'bg-blue-100 text-blue-700',
    paid: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
    expired: 'bg-gray-100 text-gray-600',
    processing: 'bg-purple-100 text-purple-700',
};

export default function AdminBillingIndex({ bills }: Props) {
    const { props } = usePage();
    const flash = props.flash as { success?: string; error?: string } | undefined;
    const [selectedPayment, setSelectedPayment] = useState<Payment | null>(null);
    const [paymentModalOpen, setPaymentModalOpen] = useState(false);

    function handleGenerate() {
        if (confirm('Generate rent bills for all active tenants for this month?')) {
            router.post('/admin/billing/generate-monthly');
        }
    }

    function viewPaymentDetails(payment: Payment) {
        setSelectedPayment(payment);
        setPaymentModalOpen(true);
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
                                <th className="px-4 py-3 font-medium">Bill Status</th>
                                <th className="px-4 py-3 font-medium">Payment Method</th>
                                <th className="px-4 py-3 font-medium">Payment Status</th>
                                <th className="px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y dark:divide-neutral-800">
                            {bills.length === 0 ? (
                                <tr>
                                    <td colSpan={9} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
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
                                            <td className="px-4 py-3">
                                                {latestPayment?.provider_status ? (
                                                    <span
                                                        className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${providerStatusClass[latestPayment.provider_status] ?? 'bg-gray-100 text-gray-600'}`}
                                                    >
                                                        {latestPayment.provider_status === 'paid' ? '✓ Confirmed' : latestPayment.provider_status === 'pending' ? '⏳ Pending' : latestPayment.provider_status === 'failed' ? '✗ Failed' : latestPayment.provider_status === 'expired' ? '⌛ Expired' : latestPayment.provider_status}
                                                    </span>
                                                ) : (
                                                    <span className="text-gray-400">—</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {latestPayment && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => viewPaymentDetails(latestPayment)}
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                    </Button>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Payment Details Modal */}
                <Dialog open={paymentModalOpen} onOpenChange={setPaymentModalOpen}>
                    <DialogContent className="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Payment Details</DialogTitle>
                        </DialogHeader>
                        {selectedPayment && (
                            <div className="space-y-6">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <p className="text-xs font-medium text-gray-900 dark:text-gray-100">Payment ID</p>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">#{selectedPayment.payment_id}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium text-gray-900 dark:text-gray-100">Amount Paid</p>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">₱{Number(selectedPayment.amount_paid).toLocaleString()}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium text-gray-900 dark:text-gray-100">Payment Method</p>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">{selectedPayment.payment_method}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium text-gray-900 dark:text-gray-100">Reference No</p>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">{selectedPayment.reference_no || '—'}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium text-gray-900 dark:text-gray-100">Payment Date</p>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">{new Date(selectedPayment.payment_date).toLocaleString()}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium text-gray-900 dark:text-gray-100">Status</p>
                                        <p className="text-sm mt-1">
                                            <span
                                                className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${providerStatusClass[selectedPayment.provider_status || ''] ?? 'bg-gray-100 text-gray-600'}`}
                                            >
                                                {selectedPayment.provider_status === 'paid' ? '✓ Confirmed' : selectedPayment.provider_status === 'pending' ? '⏳ Pending' : selectedPayment.provider_status === 'failed' ? '✗ Failed' : selectedPayment.provider_status === 'expired' ? '⌛ Expired' : selectedPayment.provider_status || 'Unknown'}
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                {selectedPayment.provider && (
                                    <div className="border-t pt-4">
                                        <p className="text-xs font-medium text-gray-900 dark:text-gray-100 mb-3">Provider Information</p>
                                        <div className="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <p className="text-xs text-gray-600 dark:text-gray-400">Provider</p>
                                                <p className="text-sm text-gray-900 dark:text-gray-100 mt-1 font-medium capitalize">{selectedPayment.provider}</p>
                                            </div>
                                            {selectedPayment.provider_checkout_session_id && (
                                                <div>
                                                    <p className="text-xs text-gray-600 dark:text-gray-400">Session ID</p>
                                                    <p className="text-sm text-gray-900 dark:text-gray-100 mt-1 font-mono text-xs break-all">{selectedPayment.provider_checkout_session_id}</p>
                                                </div>
                                            )}
                                            {selectedPayment.provider_payment_intent_id && (
                                                <div>
                                                    <p className="text-xs text-gray-600 dark:text-gray-400">Payment Intent ID</p>
                                                    <p className="text-sm text-gray-900 dark:text-gray-100 mt-1 font-mono text-xs break-all">{selectedPayment.provider_payment_intent_id}</p>
                                                </div>
                                            )}
                                            {selectedPayment.paid_at && (
                                                <div>
                                                    <p className="text-xs text-gray-600 dark:text-gray-400">Confirmed At</p>
                                                    <p className="text-sm text-gray-900 dark:text-gray-100 mt-1">{new Date(selectedPayment.paid_at).toLocaleString()}</p>
                                                </div>
                                            )}
                                            {selectedPayment.failure_message && (
                                                <div className="col-span-2">
                                                    <p className="text-xs text-gray-600 dark:text-gray-400">Failure Reason</p>
                                                    <p className="text-sm text-red-600 dark:text-red-400 mt-1">{selectedPayment.failure_message}</p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </DialogContent>
                </Dialog>
            </div>
        </AdminLayout>
    );
}
