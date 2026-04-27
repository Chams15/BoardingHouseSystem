<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use App\Services\PayMongoService;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class BillingController extends Controller
{
    private const PROVIDER_SUCCESS_STATUSES = ['paid', 'succeeded', 'completed', 'approved', 'validated', 'authorized', 'authorised'];

    private const PROVIDER_FAILURE_STATUSES = ['failed', 'canceled', 'cancelled', 'declined'];

    private const PROVIDER_PENDING_STATUSES = ['pending', 'processing', 'awaiting_payment_method', 'awaiting_next_action', 'active'];

    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $bills = Bill::query()
            ->whereHas('leaseContract', function ($query) use ($user): void {
                $query->where('tenant_id', $user->user_id);
            })
            ->with([
                'leaseContract.room',
                'payments' => function ($query): void {
                    $query->orderByDesc('payment_date');
                },
            ])
            ->orderByDesc('due_date')
            ->get();

        return Inertia::render('payments/index', [
            'bills' => $bills,
        ]);
    }

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

        $payment = null;

        try {
            DB::transaction(function () use ($bill, $validated, $user, &$payment): void {
                /** @var Bill $locked */
                $locked = Bill::with('leaseContract')->where('bill_id', $bill->bill_id)->lockForUpdate()->first();

                if (! $locked) {
                    throw new \RuntimeException('Bill not found.');
                }

                if ($locked->payment_status === 'Paid') {
                    throw new \RuntimeException('This bill has already been paid.');
                }

                if ((int) $locked->version !== (int) $validated['version']) {
                    throw new \RuntimeException('This bill was updated by another process. Please refresh and try again.');
                }

                $payment = Payment::create([
                    'bill_id' => $locked->bill_id,
                    'amount_paid' => $locked->amount_due,
                    'payment_method' => 'Online',
                    'provider' => 'paymongo',
                    'provider_status' => 'pending',
                    'reference_no' => Str::upper(Str::random(10)),
                    'payment_date' => now(),
                ]);

                if ($locked->payment_status !== 'Pending') {
                    $locked->update([
                        'payment_status' => 'Pending',
                        'version' => $locked->version + 1,
                    ]);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (! $payment instanceof Payment) {
            return back()->with('error', 'Unable to initialize payment. Please try again.');
        }

        $checkoutUrl = null;
        $amountInCentavos = (int) round((float) $payment->amount_paid * 100);
        $paymentMethodTypes = config('services.paymongo.payment_method_types', ['card', 'gcash']);

        if (! is_array($paymentMethodTypes) || empty($paymentMethodTypes)) {
            $paymentMethodTypes = ['card', 'gcash'];
        }

        $tenantName = $user->tenantProfile?->full_name ?: $user->email;

        $payload = [
            'billing' => [
                'name' => $tenantName,
                'email' => $user->email,
            ],
            'metadata' => [
                'bill_id' => (string) $payment->bill_id,
                'payment_id' => (string) $payment->payment_id,
                'reference_no' => (string) $payment->reference_no,
                'tenant_id' => (string) $user->user_id,
            ],
            'line_items' => [[
                'currency' => 'PHP',
                'amount' => $amountInCentavos,
                'name' => $bill->bill_type,
                'quantity' => 1,
                'description' => $bill->description ?: 'Boarding house bill',
            ]],
            'payment_method_types' => $paymentMethodTypes,
            'success_url' => route('billing.paymongo.return', [
                'bill' => $bill->bill_id,
                'status' => 'success',
                'payment' => $payment->payment_id,
            ]),
            'cancel_url' => route('billing.paymongo.return', [
                'bill' => $bill->bill_id,
                'status' => 'cancel',
                'payment' => $payment->payment_id,
            ]),
            'description' => $bill->description ?: 'Billing payment',
            'send_email_receipt' => false,
            'show_description' => true,
            'show_line_items' => true,
        ];

        try {
            $session = $payMongo->createCheckoutSession($payload);
            $details = $payMongo->extractCheckoutDetails($session);

            if (blank($details['checkout_session_id']) && ! blank($details['checkout_url'])) {
                $details['checkout_session_id'] = $this->extractCheckoutSessionIdFromUrl((string) $details['checkout_url']);
            }

            if (blank($details['payment_intent_id']) && ! blank($details['checkout_session_id'])) {
                $refreshedSession = $payMongo->retrieveCheckoutSession((string) $details['checkout_session_id']);
                $refreshedDetails = $payMongo->extractCheckoutDetails($refreshedSession);

                $details['payment_intent_id'] = $refreshedDetails['payment_intent_id'] ?: $details['payment_intent_id'];
                $details['checkout_url'] = $refreshedDetails['checkout_url'] ?: $details['checkout_url'];
                $details['expires_at'] = $refreshedDetails['expires_at'] ?: $details['expires_at'];
                $session = $refreshedSession;
            }

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
        } catch (\RuntimeException $e) {
            $payment->update([
                'provider_status' => 'failed',
                'failure_message' => $e->getMessage(),
            ]);

            DB::transaction(function () use ($bill): void {
                $this->updateBillStatusIfNeeded($bill->bill_id);
            });

            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Failed creating PayMongo checkout session.', [
                'payment_id' => $payment->payment_id,
                'bill_id' => $payment->bill_id,
                'message' => $e->getMessage(),
            ]);

            $payment->update([
                'provider_status' => 'failed',
                'failure_message' => 'Unable to start online payment right now. Please try again.',
            ]);

            DB::transaction(function () use ($bill): void {
                $this->updateBillStatusIfNeeded($bill->bill_id);
            });

            return back()->with('error', 'Unable to start online payment right now. Please try again.');
        }

        return Inertia::location($checkoutUrl);
    }

    public function returnFromCheckout(Request $request, Bill $bill, PayMongoService $payMongo): RedirectResponse
    {
        if ($bill->leaseContract->tenant_id !== $request->user()->user_id) {
            abort(403);
        }

        $status = (string) $request->query('status', 'success');
        $paymentId = (int) $request->query('payment', 0);

        if ($status === 'cancel' || $status === 'failed') {
            DB::transaction(function () use ($bill, $paymentId): void {
                if ($paymentId > 0) {
                    $latest = Payment::where('payment_id', $paymentId)->lockForUpdate()->first();

                    if ($latest && $latest->provider_status === 'pending') {
                        $latest->update([
                            'provider_status' => 'cancelled',
                            'failure_message' => 'Checkout was canceled by tenant.',
                        ]);
                    }
                }

                $this->updateBillStatusIfNeeded($bill->bill_id);
            });

            return redirect()->route('dashboard')->with('error', 'Payment was canceled. You can retry anytime.');
        }

        if ($paymentId > 0) {
            /** @var Payment|null $payment */
            $payment = Payment::query()
                ->where('payment_id', $paymentId)
                ->where('bill_id', $bill->bill_id)
                ->where('provider', 'paymongo')
                ->first();

            if ($payment) {
                $payment = $this->refreshPaymentFromCheckoutSession($payment, $payMongo);

                if ($payment->provider_status === 'paid') {
                    return redirect()->route('dashboard')->with('success', 'Payment verified successfully.');
                }
            }
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
        Log::info('PayMongo webhook received', [
            'event_id' => Arr::get($event, 'data.id') ?: Arr::get($event, 'id'),
            'event_type' => Arr::get($event, 'data.attributes.type') ?: Arr::get($event, 'type'),
        ]);

        $eventId = Arr::get($event, 'data.id') ?: Arr::get($event, 'id');
        $eventType = (string) (Arr::get($event, 'data.attributes.type') ?: Arr::get($event, 'type', 'unknown'));
        $resource = Arr::get($event, 'data.attributes.data');

        // Some webhook deliveries contain the resource directly (no data.attributes.data wrapper).
        if (! is_array($resource) || empty($resource)) {
            $candidateResource = Arr::get($event, 'data');
            if (is_array($candidateResource) && Arr::has($candidateResource, 'attributes')) {
                $resource = $candidateResource;
            } else {
                $resource = $event;
            }
        }

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

        $resourceStatus = Arr::get($resource, 'attributes.status');
        $paymentIntentStatus = Arr::get($resource, 'attributes.payment_intent.attributes.status');
        $payments = Arr::get($resource, 'attributes.payments', []);
        $hasPaidPayment = is_array($payments) && collect($payments)->contains(function ($payment): bool {
            return Str::lower((string) Arr::get($payment, 'attributes.status')) === 'paid';
        });

        if ($hasPaidPayment) {
            $resourceStatus = 'paid';
        } elseif (blank($resourceStatus) && ! blank($paymentIntentStatus)) {
            $resourceStatus = $paymentIntentStatus;
        } elseif ($resourceStatus === 'active' && Str::lower((string) $paymentIntentStatus) === 'succeeded') {
            // Checkout sessions can remain active while payment intent is already settled.
            $resourceStatus = 'succeeded';
        }

        $normalizedStatus = $this->normalizeProviderStatus($eventType, $resourceStatus);

        Log::info('PayMongo webhook parsed', [
            'eventType' => $eventType,
            'resourceStatus' => Arr::get($resource, 'attributes.status'),
            'paymentIntentStatus' => $paymentIntentStatus,
            'hasPaidPayment' => $hasPaidPayment,
            'derivedStatus' => $resourceStatus,
            'normalizedStatus' => $normalizedStatus,
            'checkoutSessionId' => $checkoutSessionId,
            'paymentIntentId' => $paymentIntentId,
            'referenceNo' => $referenceNo,
            'metadataPaymentId' => $metadataPaymentId,
            'metadataBillId' => $metadataBillId,
        ]);

        DB::transaction(function () use ($checkoutSessionId, $paymentIntentId, $referenceNo, $metadataPaymentId, $metadataBillId, $eventId, $normalizedStatus, $event): void {
            $query = Payment::query()->where('provider', 'paymongo');

            if (blank($checkoutSessionId) && blank($paymentIntentId) && blank($referenceNo)) {
                Log::warning('PayMongo webhook does not include lookup identifiers.', [
                    'event_id' => $eventId,
                    'event_type' => Arr::get($event, 'data.attributes.type') ?: Arr::get($event, 'type'),
                ]);
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
                    ->whereIn('provider_status', self::PROVIDER_PENDING_STATUSES)
                    ->latest('created_at')
                    ->lockForUpdate()
                    ->first();
            }

            if (! $payment) {
                Log::warning('PayMongo webhook payment not found.', [
                    'checkoutSessionId' => $checkoutSessionId,
                    'paymentIntentId' => $paymentIntentId,
                    'referenceNo' => $referenceNo,
                    'metadataPaymentId' => $metadataPaymentId,
                    'metadataBillId' => $metadataBillId,
                ]);
                return;
            }

            if (! blank($eventId) && $payment->provider_event_id === $eventId) {
                Log::info('PayMongo webhook duplicate event ignored.', [
                    'event_id' => $eventId,
                    'payment_id' => $payment->payment_id,
                ]);
                return;
            }

            if ($payment->paid_at !== null && $normalizedStatus === 'paid') {
                return;
            }

            $updatePayload = [
                'provider_event_id' => $eventId,
                'provider_status' => $normalizedStatus,
                'provider_metadata' => $event,
                'failure_message' => in_array($normalizedStatus, self::PROVIDER_FAILURE_STATUSES, true)
                    ? 'Payment failed via PayMongo.'
                    : null,
            ];

            if ($normalizedStatus === 'paid') {
                $updatePayload['paid_at'] = now();
                $updatePayload['payment_date'] = now();
            }

            $payment->update($updatePayload);
            Log::info('Payment status updated from webhook.', [
                'payment_id' => $payment->payment_id,
                'status' => $normalizedStatus,
            ]);

            $this->updateBillStatusIfNeeded($payment->bill_id);
        });

        return response()->json(['received' => true]);
    }

    private function updateBillStatusIfNeeded(int $billId): void
    {
        $bill = Bill::where('bill_id', $billId)->first();

        if (! $bill) {
            return;
        }

        $bill->reconcilePaymentStatus();
    }

    private function normalizeProviderStatus(string $eventType, ?string $resourceStatus): string
    {
        $event = Str::lower($eventType);
        $status = Str::lower((string) $resourceStatus);

        if (in_array($status, self::PROVIDER_SUCCESS_STATUSES, true)) {
            return 'paid';
        }

        if (in_array($status, self::PROVIDER_FAILURE_STATUSES, true)) {
            return 'failed';
        }

        if ($status === 'expired' || Str::contains($event, 'expired')) {
            return 'expired';
        }

        // Fallback from event naming when resource status is sparse.
        if (Str::contains($event, ['paid', 'succeeded', 'completed'])) {
            return 'paid';
        }

        if (Str::contains($event, ['failed', 'declined', 'cancelled', 'canceled'])) {
            return 'failed';
        }

        return 'pending';
    }

    public function paymentStatus(Request $request, Bill $bill, PayMongoService $payMongo): JsonResponse
    {
        if ($bill->leaseContract->tenant_id !== $request->user()->user_id) {
            abort(403);
        }

        /** @var Payment|null $latestPayment */
        $latestPayment = $bill->payments()->latest('payment_date')->first();

        if (! $latestPayment) {
            return response()->json(['status' => 'unpaid', 'payment' => null]);
        }

        if ($latestPayment->provider === 'paymongo' && in_array($latestPayment->provider_status, self::PROVIDER_PENDING_STATUSES, true)) {
            $latestPayment = $this->refreshPaymentFromCheckoutSession($latestPayment, $payMongo);
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

    private function refreshPaymentFromCheckoutSession(Payment $payment, PayMongoService $payMongo): Payment
    {
        if (blank(config('services.paymongo.secret_key')) || blank($payment->provider_checkout_session_id)) {
            return $payment;
        }

        try {
            $session = $payMongo->retrieveCheckoutSession((string) $payment->provider_checkout_session_id);
            $details = $payMongo->extractCheckoutDetails($session);

            $derivedStatus = $payMongo->extractCheckoutStatus($session);
            $paymentIntentStatus = Str::lower((string) Arr::get($session, 'data.attributes.payment_intent.attributes.status'));
            $hasPaidPayment = collect(Arr::get($session, 'data.attributes.payments', []))->contains(function ($pay): bool {
                return Str::lower((string) Arr::get($pay, 'attributes.status')) === 'paid';
            });

            if ($hasPaidPayment) {
                $derivedStatus = 'paid';
            } elseif (blank($derivedStatus) && $paymentIntentStatus !== '') {
                $derivedStatus = $paymentIntentStatus;
            } elseif (Str::lower((string) $derivedStatus) === 'active' && in_array($paymentIntentStatus, ['paid', 'succeeded'], true)) {
                $derivedStatus = $paymentIntentStatus;
            }

            $normalized = $this->normalizeProviderStatus('checkout_session.sync', $derivedStatus);

            DB::transaction(function () use ($payment, $details, $session, $normalized): void {
                /** @var Payment|null $locked */
                $locked = Payment::where('payment_id', $payment->payment_id)->lockForUpdate()->first();

                if (! $locked) {
                    return;
                }

                $update = [
                    'provider_status' => $normalized,
                    'provider_payment_intent_id' => $details['payment_intent_id'] ?: $locked->provider_payment_intent_id,
                    'provider_metadata' => $session,
                ];

                if (! blank($details['expires_at'])) {
                    $update['checkout_expires_at'] = $details['expires_at'];
                }

                if ($normalized === 'paid' && $locked->paid_at === null) {
                    $update['paid_at'] = now();
                    $update['payment_date'] = now();
                    $update['failure_message'] = null;
                }

                if ($normalized === 'failed' || $normalized === 'expired') {
                    $update['failure_message'] = $normalized === 'expired'
                        ? 'Checkout session expired.'
                        : 'Payment failed via PayMongo.';
                }

                $locked->update($update);
                $this->updateBillStatusIfNeeded($locked->bill_id);
            });

            return $payment->fresh() ?? $payment;
        } catch (\Throwable $e) {
            Log::warning('Unable to refresh payment from PayMongo checkout session.', [
                'payment_id' => $payment->payment_id,
                'checkout_session_id' => $payment->provider_checkout_session_id,
                'message' => $e->getMessage(),
            ]);

            return $payment;
        }
    }

    private function extractCheckoutSessionIdFromUrl(string $checkoutUrl): ?string
    {
        $path = (string) parse_url($checkoutUrl, PHP_URL_PATH);

        if ($path === '') {
            return null;
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        return $segments !== [] ? (string) end($segments) : null;
    }

    public function downloadReceipt(Request $request, Payment $payment, ReceiptService $receiptService)
    {
        // Verify the tenant can only download their own receipt
        if ($payment->bill->leaseContract->tenant_id !== $request->user()->user_id) {
            abort(403, 'Unauthorized');
        }

        // Verify a receipt exists
        if (! $payment->receipt_url) {
            abort(404, 'Receipt not found');
        }

        return $receiptService->downloadReceipt($payment);
    }
}
