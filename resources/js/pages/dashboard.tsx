import { Head, router, usePage } from '@inertiajs/react';
import { DoorOpen, Home, LogOut, Receipt, Users, Wifi } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Bill = {
    bill_id: number;
    bill_type: string;
    description: string | null;
    amount_due: string;
    due_date: string;
    payment_status: 'Unpaid' | 'Paid' | 'Overdue' | 'Waived';
    version: number;
};

type Room = {
    room_id: number;
    room_number: string;
    category: string;
    price_monthly: string;
    capacity: number;
    amenities: string | null;
};

type ActiveContract = {
    contract_id: number;
    contract_status: 'Active' | 'Pending_MoveOut';
    start_date: string;
    room: Room;
};

type Props = {
    activeContract: ActiveContract | null;
    currentBill: Bill | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

export default function Dashboard({ activeContract, currentBill }: Props) {
    const { props } = usePage();
    const flash = props.flash as { success?: string; error?: string } | undefined;

    const [payOpen, setPayOpen] = useState(false);
    const [paymentMethod, setPaymentMethod] = useState('GCash');
    const [referenceNo, setReferenceNo] = useState('');
    const [paying, setPaying] = useState(false);

    function handleMoveOut() {
        if (confirm('Are you sure you want to request a move-out?')) {
            router.post('/rooms/move-out');
        }
    }

    function handlePay(e: React.FormEvent) {
        e.preventDefault();
        if (!currentBill) return;
        setPaying(true);
        router.post(
            `/billing/${currentBill.bill_id}/pay`,
            { payment_method: paymentMethod, reference_no: referenceNo || undefined, version: currentBill.version },
            {
                onSuccess: () => {
                    setPayOpen(false);
                    setReferenceNo('');
                    setPaying(false);
                },
                onError: () => setPaying(false),
            },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard</h1>

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

                {activeContract ? (
                    <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">
                        {/* Room Card */}
                        <div className="rounded-xl border bg-white shadow-sm dark:bg-neutral-900 dark:border-neutral-800">
                            <div className="flex items-center gap-3 border-b p-4 dark:border-neutral-800">
                                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                    <Home className="h-5 w-5" />
                                </div>
                                <div>
                                    <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Your Room</h2>
                                    <p className="text-sm text-gray-500">
                                        Since {new Date(activeContract.start_date).toLocaleDateString()}
                                    </p>
                                </div>
                            </div>

                            <div className="p-5 space-y-3">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-xl font-bold text-gray-900 dark:text-gray-100">
                                        Room {activeContract.room.room_number}
                                    </h3>
                                    <span
                                        className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                            activeContract.contract_status === 'Active'
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-yellow-100 text-yellow-700'
                                        }`}
                                    >
                                        {activeContract.contract_status === 'Active' ? 'Active' : 'Move-Out Pending'}
                                    </span>
                                </div>

                                <div className="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <p className="flex items-center gap-2">
                                        <DoorOpen className="h-4 w-4" />
                                        {activeContract.room.category}
                                    </p>
                                    <p className="flex items-center gap-2">
                                        <Users className="h-4 w-4" />
                                        Capacity: {activeContract.room.capacity}
                                    </p>
                                    {activeContract.room.amenities && (
                                        <p className="flex items-center gap-2">
                                            <Wifi className="h-4 w-4" />
                                            {activeContract.room.amenities}
                                        </p>
                                    )}
                                </div>

                                <p className="text-xl font-bold text-orange-600">
                                    ₱{Number(activeContract.room.price_monthly).toLocaleString()}
                                    <span className="text-sm font-normal text-gray-500">/mo</span>
                                </p>
                            </div>

                            <div className="border-t p-4 dark:border-neutral-800">
                                {activeContract.contract_status === 'Active' ? (
                                    <Button onClick={handleMoveOut} variant="destructive" className="w-full">
                                        <LogOut className="mr-2 h-4 w-4" />
                                        Request Move Out
                                    </Button>
                                ) : (
                                    <Button disabled className="w-full" variant="outline">
                                        Move-Out Pending Approval
                                    </Button>
                                )}
                            </div>
                        </div>

                        {/* Billing Card */}
                        {currentBill && (
                            <div className="rounded-xl border bg-white shadow-sm dark:bg-neutral-900 dark:border-neutral-800">
                                <div className="flex items-center gap-3 border-b p-4 dark:border-neutral-800">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                        <Receipt className="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">This Month's Bill</h2>
                                        <p className="text-sm text-gray-500">
                                            Due {new Date(currentBill.due_date).toLocaleDateString()}
                                        </p>
                                    </div>
                                </div>

                                <div className="p-5 space-y-3">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-gray-600 dark:text-gray-400">
                                            {currentBill.description ?? currentBill.bill_type}
                                        </span>
                                        <span
                                            className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                currentBill.payment_status === 'Paid'
                                                    ? 'bg-green-100 text-green-700'
                                                    : currentBill.payment_status === 'Overdue'
                                                      ? 'bg-red-100 text-red-700'
                                                      : 'bg-yellow-100 text-yellow-700'
                                            }`}
                                        >
                                            {currentBill.payment_status}
                                        </span>
                                    </div>

                                    <p className="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                        ₱{Number(currentBill.amount_due).toLocaleString()}
                                    </p>
                                </div>

                                <div className="border-t p-4 dark:border-neutral-800">
                                    {currentBill.payment_status === 'Paid' ? (
                                        <Button disabled className="w-full" variant="outline">
                                            Already Paid
                                        </Button>
                                    ) : (
                                        <Button
                                            onClick={() => setPayOpen(true)}
                                            className="w-full bg-blue-600 hover:bg-blue-700 text-white"
                                        >
                                            Pay Now
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                ) : (
                    <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 p-12 text-center dark:border-neutral-700 dark:bg-neutral-900">
                        <Home className="mb-3 h-12 w-12 text-gray-400" />
                        <h2 className="text-lg font-semibold text-gray-700 dark:text-gray-300">No Room Assigned</h2>
                        <p className="mt-1 text-sm text-gray-500">
                            Browse available rooms and submit a request to get started.
                        </p>
                        <Button
                            onClick={() => router.visit('/rooms')}
                            className="mt-4 bg-orange-500 hover:bg-orange-600 text-white"
                        >
                            Browse Rooms
                        </Button>
                    </div>
                )}
            </div>

            {/* Payment Dialog */}
            <Dialog open={payOpen} onOpenChange={setPayOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Pay Your Bill</DialogTitle>
                        <DialogDescription>
                            {currentBill && (
                                <>
                                    Settle your {currentBill.description ?? 'rent'} of{' '}
                                    <strong>₱{Number(currentBill?.amount_due).toLocaleString()}</strong>
                                </>
                            )}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handlePay} className="space-y-4 pt-2">
                        <div className="space-y-1">
                            <Label>Payment Method</Label>
                            <div className="flex gap-2">
                                {['Cash', 'GCash', 'Credit Card'].map((method) => (
                                    <button
                                        key={method}
                                        type="button"
                                        onClick={() => setPaymentMethod(method)}
                                        className={`flex-1 rounded-lg border px-3 py-2 text-sm font-medium transition-colors ${
                                            paymentMethod === method
                                                ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300'
                                                : 'border-gray-200 text-gray-600 hover:border-gray-300 dark:border-neutral-700 dark:text-gray-400'
                                        }`}
                                    >
                                        {method}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="reference_no">
                                Reference No. <span className="text-gray-400 font-normal">(optional)</span>
                            </Label>
                            <Input
                                id="reference_no"
                                placeholder="e.g. GCash reference number"
                                value={referenceNo}
                                onChange={(e) => setReferenceNo(e.target.value)}
                            />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setPayOpen(false)}>
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={paying}
                                className="bg-blue-600 hover:bg-blue-700 text-white"
                            >
                                {paying ? 'Processing...' : 'Confirm Payment'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
