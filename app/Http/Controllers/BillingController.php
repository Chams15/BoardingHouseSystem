<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    public function pay(Request $request, Bill $bill): RedirectResponse
    {
        $user = $request->user();

        if ($bill->leaseContract->tenant_id !== $user->user_id) {
            abort(403);
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'in:Cash,GCash,Credit Card'],
            'reference_no'   => ['nullable', 'string', 'max:50'],
            'version'        => ['required', 'integer'],
        ]);

        // Pessimistic lock + Optimistic concurrency check:
        //   1. lockForUpdate() prevents another request from reading/writing
        //      this row until the transaction commits (pessimistic).
        //   2. Checking version ensures no other process already updated the
        //      bill between the tenant opening the page and submitting payment
        //      (optimistic — stale-read protection).
        try {
            DB::transaction(function () use ($bill, $validated, $user) {
                /** @var Bill $locked */
                $locked = Bill::where('bill_id', $bill->bill_id)->lockForUpdate()->first();

                if ($locked->payment_status === 'Paid') {
                    throw new \RuntimeException('This bill has already been paid.');
                }

                if ((int) $locked->version !== (int) $validated['version']) {
                    throw new \RuntimeException('This bill was updated by another process. Please refresh and try again.');
                }

                Payment::create([
                    'bill_id'        => $locked->bill_id,
                    'amount_paid'    => $locked->amount_due,
                    'payment_method' => $validated['payment_method'],
                    'reference_no'   => $validated['reference_no'] ?? Str::upper(Str::random(10)),
                    'payment_date'   => now(),
                ]);

                $locked->update([
                    'payment_status' => 'Paid',
                    'version'        => $locked->version + 1,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment successful! Your bill has been settled.');
    }
}
