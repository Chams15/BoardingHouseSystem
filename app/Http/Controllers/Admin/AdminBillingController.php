<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminBillingController extends Controller
{
    public function index(): Response
    {
        $bills = Bill::with([
            'leaseContract.tenant.tenantProfile',
            'leaseContract.room',
            'payments',
        ])
            ->orderBy('due_date', 'desc')
            ->get();

        return Inertia::render('admin/billing/index', [
            'bills' => $bills,
        ]);
    }

    public function generateMonthlyBills(): RedirectResponse
    {
        // Calls sp_generate_monthly_bills — the procedure uses a cursor to
        // loop all Active leases and inserts Rent bills for the current
        // month only if they do not already exist (idempotent).
        DB::statement('CALL sp_generate_monthly_bills()');

        return back()->with('success', 'Monthly rent bills generated for all active tenants.');
    }
}
