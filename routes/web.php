<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

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
            $currentBill = \App\Models\Bill::firstOrCreate(
                [
                    'contract_id' => $activeContract->contract_id,
                    'bill_type'   => 'Rent',
                    'due_date'    => now()->startOfMonth()->toDateString(),
                ],
                [
                    'description'    => 'Rent for ' . now()->format('F Y'),
                    'amount_due'     => $activeContract->room->price_monthly,
                    'payment_status' => 'Unpaid',
                ]
            );
        }

        return inertia('dashboard', [
            'activeContract' => $activeContract,
            'currentBill'    => $currentBill,
        ]);
    })->name('dashboard');

    Route::get('rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::post('rooms/{room}/request', [RoomController::class, 'requestRoom'])->name('rooms.request');
    Route::delete('rooms/requests/{roomRequest}/cancel', [RoomController::class, 'cancelRequest'])->name('rooms.request.cancel');
    Route::post('rooms/move-out', [RoomController::class, 'requestMoveOut'])->name('rooms.move-out');
    Route::post('billing/{bill}/pay', [BillingController::class, 'pay'])->name('billing.pay');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
