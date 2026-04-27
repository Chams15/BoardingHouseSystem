import { Head, router, usePage } from '@inertiajs/react';
import { DoorOpen, Home, LogOut, Receipt, Users, Wifi, Eye, Download } from 'lucide-react';
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
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Bill = {
    bill_id: number;
    bill_type: string;
    description: string | null;
    amount_due: string;
    due_date: string;
    payment_status: 'Unpaid' | 'Pending' | 'Paid' | 'Overdue' | 'Waived';
    version: number;
    payments?: Payment[];
};

type Payment = {
    payment_id: number;
    amount_paid: string;
    payment_method: string;
    reference_no: string | null;
    payment_date: string;
    provider: string | null;
    provider_status: string | null;
    paid_at: string | null;
    failure_message: string | null;
    receipt_url: string | null;
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
    paymentHistory: Payment[];
    recentVisitors: VisitorLog[];
    recentTickets: MaintenanceTicket[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

export default function Dashboard({ activeContract, currentBill, paymentHistory, recentVisitors, recentTickets }: Props) {
    const { props } = usePage();
    const flash = props.flash as { success?: string; error?: string } | undefined;

    const [payOpen, setPayOpen] = useState(false);
    const [paying, setPaying] = useState(false);
    const [selectedPayment, setSelectedPayment] = useState<Payment | null>(null);
    const [paymentDetailsOpen, setPaymentDetailsOpen] = useState(false);

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
            { version: currentBill.version },
            {
                onSuccess: () => {
                    setPayOpen(false);
                    setPaying(false);
                },
                onError: () => setPaying(false),
            },
        );
    }

    function viewPaymentDetails(payment: Payment) {
        setSelectedPayment(payment);
        setPaymentDetailsOpen(true);
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
                                                                                                                : currentBill.payment_status === 'Pending'
                                                                                                                    ? 'bg-blue-100 text-blue-700'
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

                                        
                                    </div>

                                    <div className="border-t border-gray-100 p-6 dark:border-neutral-800">
                                        {currentBill.payment_status === 'Paid' ? (
                                            <Button disabled className="w-full" variant="outline">
                                                Already Paid
                                            </Button>
                                        ) : currentBill.payment_status === 'Pending' ? (
                                            <Button disabled className="w-full" variant="outline">
                                                Payment In Progress
                                            </Button>
                                        ) : (
                                            <Button
                                                onClick={() => setPayOpen(true)}
                                                className="w-full bg-blue-600 hover:bg-blue-700 text-white"
                                            >
                                                Pay Online
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Recent Activity Sections */}
                        {(recentVisitors.length > 0 || recentTickets.length > 0 || paymentHistory.length > 0) && (
                            <div className="mt-4">
                                <div className="mb-6">
                                    <h2 className="text-xl font-bold text-gray-900 dark:text-gray-100">Recent Activity</h2>
                                    <div className="mt-1 h-1 w-12 rounded bg-gradient-to-r from-orange-400 to-blue-400"></div>
                                </div>

                                <div className="grid gap-6 lg:grid-cols-3">
                                    {/* Payment History */}
                                    {paymentHistory.length > 0 && (
                                        <div>
                                            <h3 className="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">Payment History</h3>
                                            <div className="space-y-3">
                                                {paymentHistory.slice(0, 3).map((payment) => (
                                                    <div key={payment.payment_id} className="rounded-xl border border-gray-100 bg-white p-4 shadow-sm hover:shadow-md transition-shadow dark:bg-neutral-900 dark:border-neutral-800">
                                                        <div className="flex items-start justify-between mb-2">
                                                            <div>
                                                                <p className="text-sm font-medium text-gray-900 dark:text-gray-100">₱{Number(payment.amount_paid).toLocaleString()}</p>
                                                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">{payment.payment_method}</p>
                                                            </div>
                                                            <span
                                                                className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                                                                    payment.provider_status === 'paid'
                                                                        ? 'bg-green-100 text-green-700'
                                                                        : payment.provider_status === 'pending'
                                                                          ? 'bg-blue-100 text-blue-700'
                                                                          : payment.provider_status === 'failed'
                                                                            ? 'bg-red-100 text-red-700'
                                                                            : 'bg-gray-100 text-gray-600'
                                                                }`}
                                                            >
                                                                {payment.provider_status === 'paid' ? '✓' : payment.provider_status === 'pending' ? '⏳' : payment.provider_status === 'failed' ? '✗' : '—'}
                                                            </span>
                                                        </div>
                                                        <p className="text-xs text-gray-500 dark:text-gray-400">{new Date(payment.payment_date).toLocaleDateString()}</p>
                                                        {payment.reference_no && (
                                                            <p className="text-xs text-gray-400 dark:text-gray-500 font-mono mt-2">Ref: {payment.reference_no}</p>
                                                        )}
                                                        <div className="flex gap-2 mt-3">
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                className="flex-1 text-xs h-8"
                                                                onClick={() => viewPaymentDetails(payment)}
                                                            >
                                                                <Eye className="h-3 w-3 mr-1" />
                                                                Details
                                                            </Button>
                                                            {payment.receipt_url && payment.provider_status === 'paid' && (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    className="flex-1 text-xs h-8"
                                                                    asChild
                                                                >
                                                                    <a href={`/payments/${payment.payment_id}/receipt/download`} download>
                                                                        <Download className="h-3 w-3 mr-1" />
                                                                        Receipt
                                                                    </a>
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                            {paymentHistory.length > 3 && (
                                                <Button
                                                    variant="outline"
                                                    className="w-full mt-3 text-xs"
                                                    onClick={() => router.visit('/billing')}
                                                >
                                                    View All Payments
                                                </Button>
                                            )}
                                        </div>
                                    )}

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
                        <DialogTitle>Online Payment</DialogTitle>
                        <DialogDescription>
                            {currentBill && (
                                <>
                                    You will be redirected to PayMongo to settle your {currentBill.description ?? 'rent'} of{' '}
                                    <strong>₱{Number(currentBill?.amount_due).toLocaleString()}</strong>
                                </>
                            )}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handlePay} className="space-y-4 pt-2">
                        <div className="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-blue-300">
                            Online checkout is handled securely by PayMongo. Available options may include card or e-wallet, depending on your account settings.
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
                                {paying ? 'Redirecting...' : 'Continue to PayMongo'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Payment Details Dialog */}
            <Dialog open={paymentDetailsOpen} onOpenChange={setPaymentDetailsOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Payment Details</DialogTitle>
                    </DialogHeader>
                    {selectedPayment && (
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-xs font-medium text-gray-600 dark:text-gray-400">Amount</p>
                                    <p className="text-lg font-bold text-gray-900 dark:text-gray-100 mt-1">₱{Number(selectedPayment.amount_paid).toLocaleString()}</p>
                                </div>
                                <div>
                                    <p className="text-xs font-medium text-gray-600 dark:text-gray-400">Status</p>
                                    <p className="mt-1">
                                        <span
                                            className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                                                selectedPayment.provider_status === 'paid'
                                                    ? 'bg-green-100 text-green-700'
                                                    : selectedPayment.provider_status === 'pending'
                                                      ? 'bg-blue-100 text-blue-700'
                                                      : selectedPayment.provider_status === 'failed'
                                                        ? 'bg-red-100 text-red-700'
                                                        : 'bg-gray-100 text-gray-600'
                                            }`}
                                        >
                                            {selectedPayment.provider_status === 'paid' ? '✓ Confirmed' : selectedPayment.provider_status === 'pending' ? '⏳ Pending' : selectedPayment.provider_status === 'failed' ? '✗ Failed' : 'Unknown'}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs font-medium text-gray-600 dark:text-gray-400">Method</p>
                                    <p className="text-sm text-gray-900 dark:text-gray-100 mt-1">{selectedPayment.payment_method}</p>
                                </div>
                                <div>
                                    <p className="text-xs font-medium text-gray-600 dark:text-gray-400">Date</p>
                                    <p className="text-sm text-gray-900 dark:text-gray-100 mt-1">{new Date(selectedPayment.payment_date).toLocaleDateString()}</p>
                                </div>
                            </div>

                            {selectedPayment.reference_no && (
                                <div className="border-t pt-4">
                                    <p className="text-xs font-medium text-gray-600 dark:text-gray-400">Reference Number</p>
                                    <p className="text-sm font-mono text-gray-900 dark:text-gray-100 mt-1">{selectedPayment.reference_no}</p>
                                </div>
                            )}

                            {selectedPayment.paid_at && (
                                <div className="border-t pt-4">
                                    <p className="text-xs font-medium text-gray-600 dark:text-gray-400">Confirmed At</p>
                                    <p className="text-sm text-gray-900 dark:text-gray-100 mt-1">{new Date(selectedPayment.paid_at).toLocaleString()}</p>
                                </div>
                            )}

                            {selectedPayment.failure_message && (
                                <div className="border-t pt-4">
                                    <p className="text-xs font-medium text-red-600 dark:text-red-400">Failure Reason</p>
                                    <p className="text-sm text-red-600 dark:text-red-400 mt-1">{selectedPayment.failure_message}</p>
                                </div>
                            )}

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setPaymentDetailsOpen(false)}
                                    className="w-full"
                                >
                                    Close
                                </Button>
                            </DialogFooter>
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
