<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use App\Services\PayMongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class BillingController extends Controller
{
    public function pay(Request $request, Bill $bill, PayMongoService $payMongo): RedirectResponse|Response
    {
        $user = $request->user();

        if ($bill->leaseContract->tenant_id !== $user->user_id) {
            abort(403);
        }

        $validated = $request->validate([
            'version' => ['required', 'integer'],
        ]);

        if (blank(config('services.paymongo.secret_key'))) {
            return back()->with('error', 'PayMongo is not configured yet. Add PAYMONGO_SECRET_KEY in your .env file.');
        }

        $checkoutUrl = null;

        try {
            DB::transaction(function () use ($bill, $validated, $user, $payMongo, &$checkoutUrl) {
                /** @var Bill $locked */
                $locked = Bill::with('leaseContract')->where('bill_id', $bill->bill_id)->lockForUpdate()->first();

                if ($locked->payment_status === 'Paid') {
                    throw new \RuntimeException('This bill has already been paid.');
                }

                if ((int) $locked->version !== (int) $validated['version']) {
                    throw new \RuntimeException('This bill was updated by another process. Please refresh and try again.');
                }

                $payment = Payment::create([
                    'bill_id'        => $locked->bill_id,
                    'amount_paid'    => $locked->amount_due,
                    'payment_method' => 'Online',
                    'provider'       => 'paymongo',
                    'provider_status' => 'pending',
                    'reference_no'   => Str::upper(Str::random(10)),
                    'payment_date'   => now(),
                ]);

                if ($locked->payment_status !== 'Pending') {
                    $locked->update([
                        'payment_status' => 'Pending',
                        'version' => $locked->version + 1,
                    ]);
                }

                $tenantName = $user->tenantProfile?->full_name ?: $user->email;
                $amountInCentavos = (int) round((float) $locked->amount_due * 100);
                $paymentMethodTypes = config('services.paymongo.payment_method_types', ['card', 'gcash']);

                if (! is_array($paymentMethodTypes) || empty($paymentMethodTypes)) {
                    $paymentMethodTypes = ['card', 'gcash'];
                }

                $session = $payMongo->createCheckoutSession([
                    'billing' => [
                        'name' => $tenantName,
                        'email' => $user->email,
                    ],
                    'metadata' => [
                        'bill_id' => (string) $locked->bill_id,
                        'payment_id' => (string) $payment->payment_id,
                        'reference_no' => (string) $payment->reference_no,
                        'tenant_id' => (string) $user->user_id,
                    ],
                    'line_items' => [[
                        'currency' => 'PHP',
                        'amount' => $amountInCentavos,
                        'name' => $locked->bill_type,
                        'quantity' => 1,
                        'description' => $locked->description ?: 'Boarding house bill',
                    ]],
                    'payment_method_types' => $paymentMethodTypes,
                    'success_url' => route('billing.paymongo.return', ['bill' => $locked->bill_id, 'status' => 'success']),
                    'cancel_url' => route('billing.paymongo.return', ['bill' => $locked->bill_id, 'status' => 'cancel']),
                    'description' => $locked->description ?: 'Billing payment',
                    'send_email_receipt' => false,
                    'show_description' => true,
                    'show_line_items' => true,
                ]);

                $details = $payMongo->extractCheckoutDetails($session);
                $checkoutUrl = $details['checkout_url'];

                if (blank($checkoutUrl)) {
                    throw new \RuntimeException('Unable to create PayMongo checkout URL. Please try again.');
                }

                $payment->update([
                    'provider_checkout_session_id' => $details['checkout_session_id'],
                    'provider_payment_intent_id' => $details['payment_intent_id'],
                    'checkout_url' => $checkoutUrl,
                    'checkout_expires_at' => $details['expires_at'],
                    'provider_metadata' => $session,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return Inertia::location($checkoutUrl);
    }

    public function returnFromCheckout(Request $request, Bill $bill): RedirectResponse
    {
        if ($bill->leaseContract->tenant_id !== $request->user()->user_id) {
            abort(403);
        }

        $status = (string) $request->query('status', 'success');

        if ($status === 'cancel') {
            DB::transaction(function () use ($bill): void {
                $lockedBill = Bill::where('bill_id', $bill->bill_id)->lockForUpdate()->first();

                if (! $lockedBill || in_array($lockedBill->payment_status, ['Paid', 'Waived'], true)) {
                    return;
                }

                $lockedBill->update([
                    'payment_status' => $this->resolveUnsettledBillStatus($lockedBill),
                    'version' => $lockedBill->version + 1,
                ]);
            });

            return redirect()->route('dashboard')->with('error', 'Payment was canceled. You can retry anytime.');
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Payment submitted. We are verifying it with PayMongo now.');
    }

    public function webhook(Request $request, PayMongoService $payMongo): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Paymongo-Signature') ?: $request->header('paymongo-signature');

        if (! $payMongo->verifyWebhookSignature($payload, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->json()->all();
        \Log::info('PayMongo Webhook Received', ['event' => $event]);

        $eventId = Arr::get($event, 'data.id') ?: Arr::get($event, 'id');
        $eventType = (string) (Arr::get($event, 'data.attributes.type') ?: Arr::get($event, 'type', 'unknown'));
        $resource = Arr::get($event, 'data.attributes.data', []);

        $resourceType = (string) Arr::get($resource, 'type', '');
        $resourceId = Arr::get($resource, 'id');
        $checkoutSessionId = null;

        if (Str::contains(Str::lower($resourceType), 'checkout_session') && ! blank($resourceId)) {
            $checkoutSessionId = $resourceId;
        } elseif (blank($resourceType) && is_string($resourceId) && Str::startsWith($resourceId, 'cs_')) {
            $checkoutSessionId = $resourceId;
        }

        $checkoutSessionId = $checkoutSessionId
            ?: Arr::get($resource, 'attributes.checkout_session_id')
            ?: Arr::get($resource, 'attributes.checkout_session.id');
        $paymentIntentId = Arr::get($resource, 'attributes.payment_intent.id')
            ?: Arr::get($resource, 'attributes.payment_intent_id')
            ?: Arr::get($resource, 'attributes.id')
            ?: Arr::get($resource, 'id');

        if (blank($paymentIntentId) && is_string($resourceId) && Str::startsWith($resourceId, 'pi_')) {
            $paymentIntentId = $resourceId;
        }
        $referenceNo = Arr::get($resource, 'attributes.reference_number')
            ?: Arr::get($resource, 'attributes.metadata.reference_no')
            ?: Arr::get($resource, 'attributes.metadata.reference_number')
            ?: Arr::get($resource, 'attributes.payment_intent.attributes.metadata.reference_no')
            ?: Arr::get($resource, 'attributes.payment_intent.attributes.metadata.reference_number');
        $metadataPaymentId = Arr::get($resource, 'attributes.metadata.payment_id')
            ?: Arr::get($resource, 'attributes.payment_intent.attributes.metadata.payment_id');
        $metadataBillId = Arr::get($resource, 'attributes.metadata.bill_id')
            ?: Arr::get($resource, 'attributes.payment_intent.attributes.metadata.bill_id');

        $normalizedStatus = $this->normalizeProviderStatus($eventType, Arr::get($resource, 'attributes.status'));

        \Log::info('Webhook Parsed', [
            'eventType' => $eventType,
            'resourceStatus' => Arr::get($resource, 'attributes.status'),
            'normalizedStatus' => $normalizedStatus,
            'checkoutSessionId' => $checkoutSessionId,
            'paymentIntentId' => $paymentIntentId,
            'referenceNo' => $referenceNo,
            'metadataPaymentId' => $metadataPaymentId,
            'metadataBillId' => $metadataBillId,
        ]);

        DB::transaction(function () use ($checkoutSessionId, $paymentIntentId, $referenceNo, $metadataPaymentId, $metadataBillId, $eventId, $normalizedStatus, $event) {
            $query = Payment::query()->where('provider', 'paymongo');

            if (blank($checkoutSessionId) && blank($paymentIntentId) && blank($referenceNo)) {
                \Log::warning('No checkout or payment intent ID found in webhook');
                return;
            }

            $query->where(function ($match) use ($checkoutSessionId, $paymentIntentId, $referenceNo) {
                if (! blank($checkoutSessionId)) {
                    $match->orWhere('provider_checkout_session_id', $checkoutSessionId);
                }

                if (! blank($paymentIntentId)) {
                    $match->orWhere('provider_payment_intent_id', $paymentIntentId);
                }

                if (! blank($referenceNo)) {
                    $match->orWhere('reference_no', $referenceNo);
                }
            });

            /** @var Payment|null $payment */
            $payment = $query->lockForUpdate()->first();

            if (! $payment) {
                if (! blank($metadataPaymentId)) {
                    $payment = Payment::query()
                        ->where('provider', 'paymongo')
                        ->where('payment_id', (int) $metadataPaymentId)
                        ->lockForUpdate()
                        ->first();
                }
            }

            if (! $payment && ! blank($metadataBillId)) {
                $payment = Payment::query()
                    ->where('provider', 'paymongo')
                    ->where('bill_id', (int) $metadataBillId)
                    ->whereIn('provider_status', ['pending', 'processing'])
                    ->latest('created_at')
                    ->lockForUpdate()
                    ->first();
            }

            if (! $payment) {
                \Log::warning('Payment not found for webhook', [
                    'checkoutSessionId' => $checkoutSessionId,
                    'paymentIntentId' => $paymentIntentId,
                    'referenceNo' => $referenceNo,
                    'metadataPaymentId' => $metadataPaymentId,
                    'metadataBillId' => $metadataBillId,
                ]);
                return;
            }

            if (! blank($eventId) && $payment->provider_event_id === $eventId) {
                \Log::info('Duplicate webhook event, skipping');
                return;
            }

            $updatePayload = [
                'provider_event_id' => $eventId,
                'provider_status' => $normalizedStatus,
                'provider_metadata' => $event,
                'failure_message' => $normalizedStatus === 'failed' ? 'Payment failed via PayMongo.' : null,
            ];

            if ($normalizedStatus === 'paid') {
                $updatePayload['paid_at'] = now();
                $updatePayload['payment_date'] = now();
            }

            $payment->update($updatePayload);
            \Log::info('Payment updated', ['paymentId' => $payment->payment_id, 'status' => $normalizedStatus]);

            if ($normalizedStatus === 'paid') {
                $bill = Bill::where('bill_id', $payment->bill_id)->lockForUpdate()->first();

                if ($bill && $bill->payment_status !== 'Paid') {
                    $bill->update([
                        'payment_status' => 'Paid',
                        'version' => $bill->version + 1,
                    ]);
                    \Log::info('Bill marked as Paid', ['billId' => $bill->bill_id]);
                } else {
                    \Log::info('Bill not updated', [
                        'billFound' => (bool) $bill,
                        'billStatus' => $bill?->payment_status,
                    ]);
                }
            } else {
                // Reconcile bill status when payment is not yet settled.
                $this->updateBillStatusIfNeeded($payment->bill_id);
            }
        });

        return response()->json(['received' => true]);
    }

    private function updateBillStatusIfNeeded(int $billId): void
    {
        $bill = Bill::where('bill_id', $billId)->lockForUpdate()->first();
        if (! $bill || in_array($bill->payment_status, ['Paid', 'Waived'], true)) {
            return;
        }

        // Prefer settled status when a paid event exists.
        $successfulPayment = Payment::where('bill_id', $billId)
            ->where('provider_status', 'paid')
            ->latest('paid_at')
            ->first();

        if ($successfulPayment && $bill->payment_status !== 'Paid') {
            $bill->update([
                'payment_status' => 'Paid',
                'version' => $bill->version + 1,
            ]);
            \Log::info('Bill marked as Paid via payment check', ['billId' => $bill->bill_id]);

            return;
        }

        $pendingPayment = Payment::where('bill_id', $billId)
            ->where('provider_status', 'pending')
            ->where(function ($query) {
                $query->whereNull('checkout_expires_at')
                    ->orWhere('checkout_expires_at', '>', now());
            })
            ->exists();

        $nextStatus = $pendingPayment ? 'Pending' : $this->resolveUnsettledBillStatus($bill);

        if ($bill->payment_status !== $nextStatus) {
            $bill->update([
                'payment_status' => $nextStatus,
                'version' => $bill->version + 1,
            ]);

            \Log::info('Bill status reconciled from payment state', [
                'billId' => $bill->bill_id,
                'status' => $nextStatus,
            ]);
        }
    }

    private function resolveUnsettledBillStatus(Bill $bill): string
    {
        if ($bill->due_date && $bill->due_date->isPast()) {
            return 'Overdue';
        }

        return 'Unpaid';
    }

    private function normalizeProviderStatus(string $eventType, ?string $resourceStatus): string
    {
        $event = Str::lower($eventType);
        $status = Str::lower((string) $resourceStatus);

        // Success indicators
        if ($status === 'paid' || 
            $status === 'succeeded' || 
            $status === 'completed' ||
            $status === 'approved' ||
            $status === 'authorized' ||
            $status === 'authorised' ||
            $status === 'authorizedd' ||
            Str::contains($event, 'paid') || 
            Str::contains($event, 'succeeded') ||
            Str::contains($event, 'completed') ||
            Str::contains($event, 'approved') ||
            Str::contains($event, 'authorized') ||
            Str::contains($event, 'authorised') ||
            Str::contains($event, 'success')
        ) {
            return 'paid';
        }

        // Failure indicators
        if (in_array($status, ['failed', 'canceled', 'cancelled', 'declined'], true) || 
            Str::contains($event, 'failed') ||
            Str::contains($event, 'declined')
        ) {
            return 'failed';
        }

        // Expiration indicators
        if ($status === 'expired' || Str::contains($event, 'expired')) {
            return 'expired';
        }

        return 'pending';
    }

    public function paymentStatus(Request $request, Bill $bill): JsonResponse
    {
        if ($bill->leaseContract->tenant_id !== $request->user()->user_id) {
            abort(403);
        }

        $latestPayment = $bill->payments()->latest('payment_date')->first();

        if (!$latestPayment) {
            return response()->json(['status' => 'unpaid', 'payment' => null]);
        }

        return response()->json([
            'status' => $latestPayment->provider_status ?? 'unknown',
            'payment' => [
                'payment_id' => $latestPayment->payment_id,
                'amount_paid' => $latestPayment->amount_paid,
                'payment_method' => $latestPayment->payment_method,
                'reference_no' => $latestPayment->reference_no,
                'payment_date' => $latestPayment->payment_date,
                'provider_status' => $latestPayment->provider_status,
                'paid_at' => $latestPayment->paid_at,
                'failure_message' => $latestPayment->failure_message,
            ],
        ]);
    }
}
