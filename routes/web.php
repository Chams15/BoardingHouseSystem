<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TenantVisitorController;
use App\Http\Controllers\VerificationController;
use App\Http\Middleware\EnsureTenantHasRoom;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::post('webhooks/paymongo', [BillingController::class, 'webhook'])->name('billing.paymongo.webhook');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        if (auth()->user()->role === 'Admin') {
            return redirect()->route('admin.dashboard');
        }

        $user = auth()->user();
        $activeContract = \App\Models\LeaseContract::where('tenant_id', $user->user_id)
            ->whereIn('contract_status', ['Active', 'Pending_MoveOut'])
            ->with('room')
            ->first();

        $currentBill = null;
        if ($activeContract && $activeContract->contract_status === 'Active') {
            $dueDate = $activeContract->billingDueDateFor();

            $currentBill = \App\Models\Bill::firstOrCreate(
                [
                    'contract_id' => $activeContract->contract_id,
                    'bill_type'   => 'Rent',
                    'billing_period' => $activeContract->billingPeriodFor(),
                ],
                [
                    'due_date'       => $dueDate->toDateString(),
                    'description'    => 'Rent for ' . $dueDate->format('F Y'),
                    'amount_due'     => $activeContract->room->price_monthly,
                    'payment_status' => 'Unpaid',
                ]
            );
        }

        // Get recent visitors (active/recent ones)
        $recentVisitors = \App\Models\VisitorLog::where('tenant_visited', $user->user_id)
            ->orderByDesc('time_in')
            ->take(5)
            ->get()
            ->map(function (\App\Models\VisitorLog $log) {
                return [
                    'log_id' => $log->log_id,
                    'visitor_name' => $log->visitor_name,
                    'visitor_photo_url' => $log->visitor_photo_path ? \Illuminate\Support\Facades\Storage::url($log->visitor_photo_path) : null,
                    'purpose' => $log->purpose,
                    'time_in' => $log->time_in,
                    'time_out' => $log->time_out,
                ];
            });

        // Get recent maintenance tickets
        $recentTickets = \App\Models\MaintenanceTicket::where('reported_by', $user->user_id)
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(function (\App\Models\MaintenanceTicket $ticket) {
                return [
                    'ticket_id' => $ticket->ticket_id,
                    'issue_desc' => $ticket->issue_desc,
                    'issue_photo_url' => $ticket->issue_photo_path ? \Illuminate\Support\Facades\Storage::url($ticket->issue_photo_path) : null,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'created_at' => $ticket->created_at,
                    'resolved_at' => $ticket->resolved_at,
                ];
            });

        // Get payment history
        $paymentHistory = [];
        if ($activeContract && $activeContract->contract_status === 'Active') {
            $paymentHistory = \App\Models\Payment::whereHas('bill', function ($query) use ($activeContract) {
                $query->where('contract_id', $activeContract->contract_id);
            })
            ->orderByDesc('payment_date')
            ->take(10)
            ->get()
            ->map(function (\App\Models\Payment $payment) {
                return [
                    'payment_id' => $payment->payment_id,
                    'amount_paid' => $payment->amount_paid,
                    'payment_method' => $payment->payment_method,
                    'reference_no' => $payment->reference_no,
                    'payment_date' => $payment->payment_date,
                    'provider' => $payment->provider,
                    'provider_status' => $payment->provider_status,
                    'paid_at' => $payment->paid_at,
                    'failure_message' => $payment->failure_message,
                    'receipt_url' => $payment->receipt_url,
                ];
            });
        }

        return inertia('dashboard', [
            'activeContract' => $activeContract,
            'currentBill'    => $currentBill,
            'paymentHistory' => $paymentHistory,
            'recentVisitors' => $recentVisitors,
            'recentTickets'  => $recentTickets,
        ]);
    })->name('dashboard');

    Route::get('rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::post('rooms/{room}/request', [RoomController::class, 'requestRoom'])->name('rooms.request');
    Route::delete('rooms/requests/{roomRequest}/cancel', [RoomController::class, 'cancelRequest'])->name('rooms.request.cancel');
    Route::post('rooms/move-out', [RoomController::class, 'requestMoveOut'])->name('rooms.move-out');
    Route::get('verification', [VerificationController::class, 'index'])->name('verification.index');
    Route::post('verification', [VerificationController::class, 'store'])->name('verification.store');
    Route::get('payments', [BillingController::class, 'index'])->name('payments.index');
    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('billing/{bill}/paymongo/return', [BillingController::class, 'returnFromCheckout'])->name('billing.paymongo.return');
    Route::post('billing/{bill}/pay', [BillingController::class, 'pay'])->name('billing.pay');
    Route::get('billing/{bill}/payment-status', [BillingController::class, 'paymentStatus'])->name('billing.payment-status');
    Route::get('payments/{payment}/receipt/download', [BillingController::class, 'downloadReceipt'])->name('payments.receipt.download');

    Route::middleware([EnsureTenantHasRoom::class])->group(function () {
        Route::get('maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
        Route::post('maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
        Route::post('maintenance/{ticket}/resolve', [MaintenanceController::class, 'resolve'])->name('maintenance.resolve');

        Route::get('visitors', [TenantVisitorController::class, 'index'])->name('visitors.index');
        Route::post('visitors', [TenantVisitorController::class, 'store'])->name('visitors.store');
        Route::post('visitors/{visitorLog}/checkout', [TenantVisitorController::class, 'checkout'])->name('visitors.checkout');
    });
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
