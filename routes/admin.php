<?php

use App\Http\Controllers\Admin\AdminBillingController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\LeaseController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\RoomManagementController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\VerificationRequestController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureUserIsAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard/financial-summary.pdf', [AdminDashboardController::class, 'exportFinancialReport'])->name('dashboard.financial-summary.pdf');
        Route::get('dashboard/financial-reports/{financialReport}/download', [AdminDashboardController::class, 'downloadFinancialReport'])->name('dashboard.financial-reports.download');

        Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('tenants/create', [TenantController::class, 'create'])->name('tenants.create');
        Route::post('tenants', [TenantController::class, 'store'])->name('tenants.store');
        Route::get('tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
        Route::put('tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::delete('tenants/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');

        Route::get('verification-requests', [VerificationRequestController::class, 'index'])->name('verification-requests.index');
        Route::post('verification-requests/{tenantProfile}/approve', [VerificationRequestController::class, 'approve'])->name('verification-requests.approve');
        Route::post('verification-requests/{tenantProfile}/reject', [VerificationRequestController::class, 'reject'])->name('verification-requests.reject');

        Route::get('rooms', [RoomManagementController::class, 'index'])->name('rooms.index');
        Route::get('rooms/create', [RoomManagementController::class, 'createRoom'])->name('rooms.create');
        Route::post('rooms', [RoomManagementController::class, 'storeRoom'])->name('rooms.store');
        Route::get('rooms/{room}/edit', [RoomManagementController::class, 'editRoom'])->name('rooms.edit');
        Route::put('rooms/{room}', [RoomManagementController::class, 'updateRoom'])->name('rooms.update');
        Route::delete('rooms/{room}', [RoomManagementController::class, 'deleteRoom'])->name('rooms.destroy');
        Route::get('rooms/requests', [RoomManagementController::class, 'requests'])->name('rooms.requests');
        Route::post('rooms/requests/{roomRequest}/approve', [RoomManagementController::class, 'approve'])->name('rooms.requests.approve');
        Route::post('rooms/requests/{roomRequest}/reject', [RoomManagementController::class, 'reject'])->name('rooms.requests.reject');
        Route::post('rooms/{room}/remove-tenant', [RoomManagementController::class, 'removeTenant'])->name('rooms.remove-tenant');
        Route::post('rooms/contracts/{contract}/approve-move-out', [RoomManagementController::class, 'approveMoveOut'])->name('rooms.approve-move-out');

        // Lease management routes
        Route::get('leases/create/{room}', [LeaseController::class, 'create'])->name('leases.create');
        Route::post('leases', [LeaseController::class, 'store'])->name('leases.store');
        Route::get('leases/{lease}/edit', [LeaseController::class, 'edit'])->name('leases.edit');
        Route::put('leases/{lease}', [LeaseController::class, 'update'])->name('leases.update');
        Route::delete('leases/{lease}', [LeaseController::class, 'destroy'])->name('leases.destroy');
        Route::delete('leases/{lease}/hard', [LeaseController::class, 'hardDelete'])->name('leases.hard-delete');

        Route::get('billing', [AdminBillingController::class, 'index'])->name('billing.index');
        Route::post('billing/generate-monthly', [AdminBillingController::class, 'generateMonthlyBills'])->name('billing.generate-monthly');
        Route::post('billing/{bill}/discount', [AdminBillingController::class, 'discountBill'])->name('billing.discount');
        Route::post('billing/{bill}/offline-payment', [AdminBillingController::class, 'recordOfflinePayment'])->name('billing.offline-payment');
        Route::post('billing/{bill}/waive', [AdminBillingController::class, 'waiveBill'])->name('billing.waive');

        Route::get('maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
        Route::put('maintenance/{ticket}', [MaintenanceController::class, 'update'])->name('maintenance.update');

        Route::get('security', [SecurityController::class, 'index'])->name('security.index');
        Route::get('security/visitors', [SecurityController::class, 'visitors'])->name('security.visitors.index');
        Route::get('security/incidents', [SecurityController::class, 'incidents'])->name('security.incidents.index');
        Route::get('security/blacklist', [SecurityController::class, 'blacklist'])->name('security.blacklist.index');
        Route::post('security/visitors', [SecurityController::class, 'storeVisitor'])->name('security.visitors.store');
        Route::post('security/visitors/{visitorLog}/checkout', [SecurityController::class, 'checkOutVisitor'])->name('security.visitors.checkout');
        Route::post('security/incidents', [SecurityController::class, 'storeIncident'])->name('security.incidents.store');
        Route::put('security/incidents/{incident}', [SecurityController::class, 'updateIncident'])->name('security.incidents.update');
        Route::post('security/blacklist', [SecurityController::class, 'addToBlacklist'])->name('security.blacklist.store');
        Route::delete('security/blacklist/{blacklist}', [SecurityController::class, 'removeFromBlacklist'])->name('security.blacklist.destroy');
    });
