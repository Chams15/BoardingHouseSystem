<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
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
}
