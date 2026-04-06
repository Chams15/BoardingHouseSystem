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

type VisitorLog = {
    log_id: number;
    visitor_name: string;
    visitor_photo_url: string | null;
    purpose: string | null;
    time_in: string;
    time_out: string | null;
};

type MaintenanceTicket = {
    ticket_id: number;
    issue_desc: string;
    issue_photo_url: string | null;
    priority: string;
    status: string;
    created_at: string;
    resolved_at: string | null;
};

type Props = {
    activeContract: ActiveContract | null;
    currentBill: Bill | null;
    recentVisitors: VisitorLog[];
    recentTickets: MaintenanceTicket[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

export default function Dashboard({ activeContract, currentBill, recentVisitors, recentTickets }: Props) {
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
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100">Dashboard</h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Welcome back! Here's your residence overview.</p>
                </div>

                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        {flash.error}
                    </div>
                )}

                {activeContract ? (
                    <>
                        {/* Primary Cards - Room & Billing */}
                        <div className="grid gap-6 lg:grid-cols-2">
                            {/* Room Card */}
                            <div className="rounded-2xl border border-gray-100 bg-white shadow-md hover:shadow-lg transition-shadow dark:bg-neutral-900 dark:border-neutral-800">
                                <div className="flex items-center gap-3 border-b border-gray-100 p-6 dark:border-neutral-800">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-orange-100 to-orange-50 text-orange-600">
                                        <Home className="h-6 w-6" />
                                    </div>
                                    <div>
                                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Your Room</h2>
                                        <p className="text-xs text-gray-500">
                                            Since {new Date(activeContract.start_date).toLocaleDateString()}
                                        </p>
                                    </div>
                                </div>

                                <div className="p-6 space-y-4">
                                    <div className="flex items-center justify-between">
                                        <h3 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                            Room {activeContract.room.room_number}
                                        </h3>
                                        <span
                                            className={`inline-flex rounded-full px-3 py-1 text-xs font-medium ${
                                                activeContract.contract_status === 'Active'
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-yellow-100 text-yellow-700'
                                            }`}
                                        >
                                            {activeContract.contract_status === 'Active' ? 'Active' : 'Move-Out Pending'}
                                        </span>
                                    </div>

                                    <div className="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                        <p className="flex items-center gap-3">
                                            <span className="p-2 rounded-lg bg-gray-50 dark:bg-neutral-800">
                                                <DoorOpen className="h-4 w-4 text-orange-600" />
                                            </span>
                                            <span>{activeContract.room.category}</span>
                                        </p>
                                        <p className="flex items-center gap-3">
                                            <span className="p-2 rounded-lg bg-gray-50 dark:bg-neutral-800">
                                                <Users className="h-4 w-4 text-blue-600" />
                                            </span>
                                            <span>Capacity: {activeContract.room.capacity} person(s)</span>
                                        </p>
                                        {activeContract.room.amenities && (
                                            <p className="flex items-center gap-3">
                                                <span className="p-2 rounded-lg bg-gray-50 dark:bg-neutral-800">
                                                    <Wifi className="h-4 w-4 text-green-600" />
                                                </span>
                                                <span>{activeContract.room.amenities}</span>
                                            </p>
                                        )}
                                    </div>

                                    <div className="border-t border-gray-100 pt-4 dark:border-neutral-800">
                                        <p className="text-xs text-gray-500 mb-2">Monthly Rate</p>
                                        <p className="text-3xl font-bold text-orange-600">
                                            ₱{Number(activeContract.room.price_monthly).toLocaleString()}
                                            <span className="text-sm font-normal text-gray-500">/mo</span>
                                        </p>
                                    </div>
                                </div>

                                <div className="border-t border-gray-100 p-6 dark:border-neutral-800">
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
                                <div className="rounded-2xl border border-gray-100 bg-white shadow-md hover:shadow-lg transition-shadow dark:bg-neutral-900 dark:border-neutral-800">
                                    <div className="flex items-center gap-3 border-b border-gray-100 p-6 dark:border-neutral-800">
                                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-blue-50 text-blue-600">
                                            <Receipt className="h-6 w-6" />
                                        </div>
                                        <div>
                                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">This Month's Bill</h2>
                                            <p className="text-xs text-gray-500">
                                                Due {new Date(currentBill.due_date).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="p-6 space-y-4">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                {currentBill.description ?? currentBill.bill_type}
                                            </span>
                                            <span
                                                className={`inline-flex rounded-full px-3 py-1 text-xs font-medium ${
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

                                        <div className="border-t border-gray-100 pt-4 dark:border-neutral-800">
                                            <p className="text-xs text-gray-500 mb-2">Amount Due</p>
                                            <p className="text-4xl font-bold text-gray-900 dark:text-gray-100">
                                                ₱{Number(currentBill.amount_due).toLocaleString()}
                                            </p>
                                        </div>

                                        <div className="bg-blue-50 rounded-lg p-3 dark:bg-neutral-800">
                                            <p className="text-xs text-blue-600 dark:text-blue-400">
                                                💡 Pay on time to avoid overdue charges
                                            </p>
                                        </div>
                                    </div>

                                    <div className="border-t border-gray-100 p-6 dark:border-neutral-800">
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

                        {/* Recent Activity Sections */}
                        {(recentVisitors.length > 0 || recentTickets.length > 0) && (
                            <div className="mt-4">
                                <div className="mb-6">
                                    <h2 className="text-xl font-bold text-gray-900 dark:text-gray-100">Recent Activity</h2>
                                    <div className="mt-1 h-1 w-12 rounded bg-gradient-to-r from-orange-400 to-blue-400"></div>
                                </div>

                                <div className="grid gap-6 lg:grid-cols-2">
                                    {/* Recent Visitors - Left Column */}
                                    {recentVisitors.length > 0 && (
                                        <div>
                                            <h3 className="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">Recent Visitors</h3>
                                            <div className="space-y-3">
                                                {recentVisitors.map((visitor) => (
                                                    <div key={visitor.log_id} className="flex gap-4 rounded-xl border border-gray-100 bg-white p-4 shadow-sm hover:shadow-md transition-shadow dark:bg-neutral-900 dark:border-neutral-800">
                                                        {visitor.visitor_photo_url && (
                                                            <img
                                                                src={visitor.visitor_photo_url}
                                                                alt={visitor.visitor_name}
                                                                className="h-16 w-16 rounded-lg object-cover flex-shrink-0"
                                                            />
                                                        )}
                                                        <div className="flex-1 min-w-0">
                                                            <h4 className="font-semibold text-gray-900 dark:text-gray-100 truncate">{visitor.visitor_name}</h4>
                                                            {visitor.purpose && (
                                                                <p className="text-xs text-gray-600 dark:text-gray-400 truncate">{visitor.purpose}</p>
                                                            )}
                                                            <div className="mt-2 space-y-1">
                                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                                    In: {new Date(visitor.time_in).toLocaleDateString()} {new Date(visitor.time_in).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                                </p>
                                                                {visitor.time_out ? (
                                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                                        Out: {new Date(visitor.time_out).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                                    </p>
                                                                ) : (
                                                                    <p className="text-xs text-orange-600 dark:text-orange-400 font-medium">🟠 Currently checked in</p>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {/* Recent Maintenance - Right Column */}
                                    {recentTickets.length > 0 && (
                                        <div>
                                            <h3 className="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">Recent Maintenance</h3>
                                            <div className="space-y-3">
                                                {recentTickets.map((ticket) => (
                                                    <div key={ticket.ticket_id} className="flex gap-4 rounded-xl border border-gray-100 bg-white p-4 shadow-sm hover:shadow-md transition-shadow dark:bg-neutral-900 dark:border-neutral-800">
                                                        {ticket.issue_photo_url && (
                                                            <img
                                                                src={ticket.issue_photo_url}
                                                                alt="Issue"
                                                                className="h-16 w-16 rounded-lg object-cover flex-shrink-0"
                                                            />
                                                        )}
                                                        <div className="flex-1 min-w-0">
                                                            <div className="flex items-start justify-between gap-2 mb-1">
                                                                <span
                                                                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium flex-shrink-0 ${
                                                                        ticket.priority === 'High'
                                                                            ? 'bg-red-100 text-red-700'
                                                                            : ticket.priority === 'Medium'
                                                                              ? 'bg-yellow-100 text-yellow-700'
                                                                              : 'bg-blue-100 text-blue-700'
                                                                    }`}
                                                                >
                                                                    {ticket.priority}
                                                                </span>
                                                                <span
                                                                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium flex-shrink-0 ${
                                                                        ticket.status === 'Open'
                                                                            ? 'bg-orange-100 text-orange-700'
                                                                            : ticket.status === 'In Progress'
                                                                              ? 'bg-blue-100 text-blue-700'
                                                                              : 'bg-green-100 text-green-700'
                                                                    }`}
                                                                >
                                                                    {ticket.status}
                                                                </span>
                                                            </div>
                                                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100 line-clamp-2">{ticket.issue_desc}</p>
                                                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                                Reported: {new Date(ticket.created_at).toLocaleDateString()}
                                                            </p>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </>
                ) : (
                    <div className="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 p-16 text-center dark:border-neutral-700 dark:bg-neutral-900">
                        <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-neutral-800">
                            <Home className="h-8 w-8 text-gray-400" />
                        </div>
                        <h2 className="text-lg font-semibold text-gray-700 dark:text-gray-300">No Room Assigned</h2>
                        <p className="mt-2 text-sm text-gray-500 max-w-sm">
                            You haven't been assigned a room yet. Browse available rooms and submit a request to get started.
                        </p>
                        <Button
                            onClick={() => router.visit('/rooms')}
                            className="mt-6 bg-orange-500 hover:bg-orange-600 text-white px-6"
                        >
                            <Home className="mr-2 h-4 w-4" />
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
