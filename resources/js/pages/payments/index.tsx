import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type PaymentItem = {
    payment_id: number;
    amount_paid: string;
    payment_method: string;
    reference_no: string | null;
    payment_date: string;
    provider: string | null;
    provider_status: string | null;
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
    lease_contract: {
        room: {
            room_number: string;
        };
    };
    payments: PaymentItem[];
};

type Props = {
    bills: BillItem[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Payments',
        href: '/payments',
    },
];

const billStatusClass: Record<string, string> = {
    Unpaid: 'bg-yellow-100 text-yellow-700',
    Pending: 'bg-blue-100 text-blue-700',
    Paid: 'bg-green-100 text-green-700',
    Overdue: 'bg-red-100 text-red-700',
    Waived: 'bg-gray-100 text-gray-700',
};

const paymentStatusClass: Record<string, string> = {
    paid: 'bg-green-100 text-green-700',
    pending: 'bg-blue-100 text-blue-700',
    failed: 'bg-red-100 text-red-700',
    expired: 'bg-gray-100 text-gray-700',
    waived: 'bg-indigo-100 text-indigo-700',
};

export default function PaymentsIndex({ bills }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Payments" />

            <div className="space-y-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Payments</h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Your bill and payment history, including waived and discounted bills.</p>
                </div>

                {bills.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-neutral-700 dark:text-gray-400">
                        No bill or payment records yet.
                    </div>
                ) : (
                    <div className="space-y-4">
                        {bills.map((bill) => (
                            <div key={bill.bill_id} className="rounded-xl border bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h2 className="text-base font-semibold text-gray-900 dark:text-gray-100">
                                            {bill.description ?? bill.bill_type} (Room {bill.lease_contract.room.room_number})
                                        </h2>
                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Due: {new Date(bill.due_date).toLocaleDateString()}
                                        </p>
                                    </div>
                                    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${billStatusClass[bill.payment_status] ?? 'bg-gray-100 text-gray-700'}`}>
                                        {bill.payment_status}
                                    </span>
                                </div>

                                <div className="mt-4 grid gap-3 text-sm md:grid-cols-4">
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">Original Amount</p>
                                        <p className="font-medium text-gray-900 dark:text-gray-100">₱{Number(bill.original_amount_due ?? bill.amount_due).toLocaleString()}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">Discount</p>
                                        <p className="font-medium text-gray-900 dark:text-gray-100">₱{Number(bill.discount_amount ?? 0).toLocaleString()}</p>
                                        {bill.discount_reason && <p className="text-xs text-gray-500 dark:text-gray-400">{bill.discount_reason}</p>}
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">Waived</p>
                                        <p className="font-medium text-gray-900 dark:text-gray-100">₱{Number(bill.waived_amount ?? 0).toLocaleString()}</p>
                                        {bill.waived_reason && <p className="text-xs text-gray-500 dark:text-gray-400">{bill.waived_reason}</p>}
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">Current Amount Due</p>
                                        <p className="font-semibold text-orange-600">₱{Number(bill.amount_due).toLocaleString()}</p>
                                    </div>
                                </div>

                                <div className="mt-4 overflow-x-auto rounded-lg border dark:border-neutral-800">
                                    <table className="w-full text-left text-sm">
                                        <thead className="bg-gray-50 text-gray-600 dark:bg-neutral-800 dark:text-gray-400">
                                            <tr>
                                                <th className="px-3 py-2 font-medium">Date</th>
                                                <th className="px-3 py-2 font-medium">Method</th>
                                                <th className="px-3 py-2 font-medium">Amount</th>
                                                <th className="px-3 py-2 font-medium">Status</th>
                                                <th className="px-3 py-2 font-medium">Reference</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y dark:divide-neutral-800">
                                            {bill.payments.length === 0 ? (
                                                <tr>
                                                    <td colSpan={5} className="px-3 py-4 text-center text-xs text-gray-500 dark:text-gray-400">
                                                        No payment entries for this bill yet.
                                                    </td>
                                                </tr>
                                            ) : (
                                                bill.payments.map((payment) => (
                                                    <tr key={payment.payment_id}>
                                                        <td className="px-3 py-2 text-gray-600 dark:text-gray-300">{new Date(payment.payment_date).toLocaleString()}</td>
                                                        <td className="px-3 py-2 text-gray-600 dark:text-gray-300">{payment.payment_method}</td>
                                                        <td className="px-3 py-2 font-medium text-gray-900 dark:text-gray-100">₱{Number(payment.amount_paid).toLocaleString()}</td>
                                                        <td className="px-3 py-2">
                                                            <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${paymentStatusClass[payment.provider_status || ''] ?? 'bg-gray-100 text-gray-700'}`}>
                                                                {payment.provider_status || 'unknown'}
                                                            </span>
                                                        </td>
                                                        <td className="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">{payment.reference_no ?? '-'}</td>
                                                    </tr>
                                                ))
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
