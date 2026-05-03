import { Head, router, usePage } from '@inertiajs/react';
import { Eye, RefreshCw, Search } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
    original_amount_due: string | null;
    amount_due: string;
    due_date: string;
    payment_status: 'Unpaid' | 'Pending' | 'Paid' | 'Overdue' | 'Waived';
    discount_amount: string | null;
    discount_reason: string | null;
    waived_amount: string | null;
    waived_reason: string | null;
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
    filters: {
        search: string;
    };
};

const breadcrumbs = [
    { title: 'Admin', href: '/admin/dashboard' },
    { title: 'Billing', href: '/admin/billing' },
];

const billStatusClass: Record<string, string> = {
    Unpaid: 'bg-yellow-100 text-yellow-700',
    Pending: 'bg-blue-100 text-blue-700',
    Paid: 'bg-green-100 text-green-700',
    Overdue: 'bg-red-100 text-red-700',
    Waived: 'bg-gray-100 text-gray-700',
};

const providerStatusClass: Record<string, string> = {
    pending: 'bg-blue-100 text-blue-700',
    paid: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
    expired: 'bg-gray-100 text-gray-600',
    waived: 'bg-indigo-100 text-indigo-700',
    processing: 'bg-purple-100 text-purple-700',
};

export default function AdminBillingIndex({ bills, filters }: Props) {
    const { props } = usePage();
    const flash = props.flash as { success?: string; error?: string } | undefined;
    const [selectedPayment, setSelectedPayment] = useState<Payment | null>(null);
    const [paymentModalOpen, setPaymentModalOpen] = useState(false);
    const [selectedBill, setSelectedBill] = useState<BillItem | null>(null);
    const [actionType, setActionType] = useState<'offline' | 'waive' | null>(null);
    const [actionModalOpen, setActionModalOpen] = useState(false);
    const [amount, setAmount] = useState('');
    const [reason, setReason] = useState('');
    const [referenceNo, setReferenceNo] = useState('');
    const [search, setSearch] = useState(filters.search ?? '');

    function handleGenerate() {
        if (confirm('Generate rent bills for all active tenants for this month?')) {
            router.post('/admin/billing/generate-monthly');
        }
    }

    function refreshResults(nextSearch = search) {
        router.get(
            '/admin/billing',
            { search: nextSearch },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    function viewPaymentDetails(payment: Payment) {
        setSelectedPayment(payment);
        setPaymentModalOpen(true);
    }

    function openAdjustmentModal(bill: BillItem, type: 'offline' | 'waive') {
        setSelectedBill(bill);
        setActionType(type);
        setAmount('');
        setReason('');
        setReferenceNo('');
        setActionModalOpen(true);
    }

    function submitAdjustment() {
        if (!selectedBill || !actionType) {
            return;
        }

        if (actionType === 'offline') {
            router.post(`/admin/billing/${selectedBill.bill_id}/offline-payment`, {
                reference_no: referenceNo,
                notes: reason,
            }, {
                preserveScroll: true,
                onSuccess: () => setActionModalOpen(false),
            });

            return;
        }

        router.post(`/admin/billing/${selectedBill.bill_id}/waive`, {
            amount,
            reason,
        }, {
            preserveScroll: true,
            onSuccess: () => setActionModalOpen(false),
        });
    }

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Billing</h1>
                    <Button onClick={handleGenerate} className="bg-orange-500 text-white hover:bg-orange-600">
                        <RefreshCw className="mr-2 h-4 w-4" />
                        Generate Monthly Bills
                    </Button>
                </div>

                <div className="grid gap-3 rounded-xl border bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:grid-cols-[1fr_auto]">
                    <div className="relative">
                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            value={search}
                            onChange={(e) => {
                                const next = e.target.value;
                                setSearch(next);
                                refreshResults(next);
                            }}
                            placeholder="Search by tenant, room, description, status, or payment reference"
                            className="pl-9"
                        />
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => {
                            setSearch('');
                            refreshResults('');
                        }}
                    >
                        Clear filters
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

                <div className="overflow-hidden rounded-xl border bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-gray-50 text-gray-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-400">
                            <tr>
                                <th className="px-4 py-3 font-medium">Tenant</th>
                                <th className="px-4 py-3 font-medium">Room</th>
                                <th className="px-4 py-3 font-medium">Description</th>
                                <th className="px-4 py-3 font-medium">Current Due</th>
                                <th className="px-4 py-3 font-medium">Due Date</th>
                                <th className="px-4 py-3 font-medium">Bill Status</th>
                                <th className="px-4 py-3 font-medium">Adjustments</th>
                                <th className="px-4 py-3 font-medium">Latest Payment</th>
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
                                                {bill.lease_contract.tenant.tenant_profile?.full_name ?? bill.lease_contract.tenant.email}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{bill.lease_contract.room.room_number}</td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{bill.description ?? bill.bill_type}</td>
                                            <td className="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">₱{Number(bill.amount_due).toLocaleString()}</td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{new Date(bill.due_date).toLocaleDateString()}</td>
                                            <td className="px-4 py-3">
                                                <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${billStatusClass[bill.payment_status] ?? 'bg-gray-100 text-gray-700'}`}>
                                                    {bill.payment_status}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                                <div>Discount: ₱{Number(bill.discount_amount ?? 0).toLocaleString()}</div>
                                                {bill.discount_reason && <div>{bill.discount_reason}</div>}
                                                <div className="mt-1">Waived: ₱{Number(bill.waived_amount ?? 0).toLocaleString()}</div>
                                                {bill.waived_reason && <div>{bill.waived_reason}</div>}
                                            </td>
                                            <td className="px-4 py-3">
                                                {latestPayment ? (
                                                    <div className="space-y-1 text-xs text-gray-600 dark:text-gray-300">
                                                        <div>{latestPayment.payment_method}</div>
                                                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${providerStatusClass[latestPayment.provider_status || ''] ?? 'bg-gray-100 text-gray-700'}`}>
                                                            {latestPayment.provider_status || 'unknown'}
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <span className="text-gray-400">-</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex flex-wrap gap-2">
                                                    {latestPayment && (
                                                        <Button variant="ghost" size="sm" onClick={() => viewPaymentDetails(latestPayment)}>
                                                            <Eye className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                    {bill.payment_status !== 'Paid' && (
                                                        <>
                                                            <Button variant="outline" size="sm" onClick={() => openAdjustmentModal(bill, 'offline')}>
                                                                Offline Payment
                                                            </Button>
                                                            <Button variant="outline" size="sm" onClick={() => openAdjustmentModal(bill, 'waive')}>
                                                                Waive
                                                            </Button>
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>

                <Dialog open={paymentModalOpen} onOpenChange={setPaymentModalOpen}>
                    <DialogContent className="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Payment Details</DialogTitle>
                        </DialogHeader>
                        {selectedPayment && (
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p className="text-xs text-gray-500">Payment ID</p>
                                    <p className="font-medium">#{selectedPayment.payment_id}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">Amount</p>
                                    <p className="font-medium">₱{Number(selectedPayment.amount_paid).toLocaleString()}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">Method</p>
                                    <p className="font-medium">{selectedPayment.payment_method}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">Reference</p>
                                    <p className="font-medium">{selectedPayment.reference_no ?? '-'}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">Date</p>
                                    <p className="font-medium">{new Date(selectedPayment.payment_date).toLocaleString()}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500">Status</p>
                                    <p className="font-medium">{selectedPayment.provider_status ?? 'unknown'}</p>
                                </div>
                            </div>
                        )}
                    </DialogContent>
                </Dialog>

                <Dialog open={actionModalOpen} onOpenChange={setActionModalOpen}>
                    <DialogContent className="max-w-lg">
                        <DialogHeader>
                            <DialogTitle>{actionType === 'offline' ? 'Record Offline Payment' : 'Waive Fees'}</DialogTitle>
                            <DialogDescription>
                                {actionType === 'offline'
                                    ? 'Create a normal settled payment entry tagged as offline.'
                                    : 'Enter the amount and reason. Waiver actions create a payment ledger entry visible to tenants.'}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4">
                            {actionType === 'waive' && (
                                <div className="space-y-2">
                                    <Label htmlFor="amount">Amount</Label>
                                    <Input
                                        id="amount"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        value={amount}
                                        onChange={(event) => setAmount(event.target.value)}
                                        placeholder="0.00"
                                    />
                                </div>
                            )}

                            {actionType === 'offline' && (
                                <div className="space-y-2">
                                    <Label htmlFor="reference_no">Reference Number (optional)</Label>
                                    <Input
                                        id="reference_no"
                                        type="text"
                                        value={referenceNo}
                                        onChange={(event) => setReferenceNo(event.target.value)}
                                        placeholder="OR number, receipt no., etc."
                                    />
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="reason">{actionType === 'offline' ? 'Notes (optional)' : 'Reason'}</Label>
                                <textarea
                                    id="reason"
                                    rows={4}
                                    value={reason}
                                    onChange={(event) => setReason(event.target.value)}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    placeholder={actionType === 'offline' ? 'Add notes about this offline collection' : 'Type the reason'}
                                />
                            </div>
                            <div className="flex justify-end gap-2">
                                <Button type="button" variant="outline" onClick={() => setActionModalOpen(false)}>
                                    Cancel
                                </Button>
                                <Button
                                    type="button"
                                    onClick={submitAdjustment}
                                    disabled={actionType === 'waive' ? (!reason.trim() || !amount) : false}
                                >
                                    Confirm
                                </Button>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </AdminLayout>
    );
}
