<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    public function pay(Request $request, Bill $bill): RedirectResponse
    {
        $user = $request->user();

        if ($bill->leaseContract->tenant_id !== $user->user_id) {
            abort(403);
        }

        if ($bill->payment_status === 'Paid') {
            return back()->with('error', 'This bill has already been paid.');
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'in:Cash,GCash,Credit Card'],
            'reference_no'   => ['nullable', 'string', 'max:50'],
        ]);

        Payment::create([
            'bill_id'        => $bill->bill_id,
            'amount_paid'    => $bill->amount_due,
            'payment_method' => $validated['payment_method'],
            'reference_no'   => $validated['reference_no'] ?? Str::upper(Str::random(10)),
            'payment_date'   => now(),
        ]);

        $bill->update(['payment_status' => 'Paid']);

        return back()->with('success', 'Payment successful! Your bill has been settled.');
    }
}
