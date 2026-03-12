<?php

use App\Http\Controllers\Admin\AdminBillingController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\RoomManagementController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureUserIsAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('tenants/create', [TenantController::class, 'create'])->name('tenants.create');
        Route::post('tenants', [TenantController::class, 'store'])->name('tenants.store');
        Route::get('tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
        Route::put('tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::delete('tenants/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');

        Route::get('rooms', [RoomManagementController::class, 'index'])->name('rooms.index');
        Route::get('rooms/requests', [RoomManagementController::class, 'requests'])->name('rooms.requests');
        Route::post('rooms/requests/{roomRequest}/approve', [RoomManagementController::class, 'approve'])->name('rooms.requests.approve');
        Route::post('rooms/requests/{roomRequest}/reject', [RoomManagementController::class, 'reject'])->name('rooms.requests.reject');
        Route::post('rooms/{room}/remove-tenant', [RoomManagementController::class, 'removeTenant'])->name('rooms.remove-tenant');
        Route::post('rooms/contracts/{contract}/approve-move-out', [RoomManagementController::class, 'approveMoveOut'])->name('rooms.approve-move-out');

        Route::get('billing', [AdminBillingController::class, 'index'])->name('billing.index');
    });
