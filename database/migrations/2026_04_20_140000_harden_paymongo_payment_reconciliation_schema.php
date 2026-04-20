<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['bill_id', 'provider_status', 'payment_date'], 'payments_bill_status_date_index');
            $table->index(['provider', 'provider_event_id'], 'payments_provider_event_index');
            $table->index(['provider', 'reference_no'], 'payments_provider_reference_index');
            $table->index(['provider', 'bill_id', 'created_at'], 'payments_provider_bill_created_index');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                "UPDATE payments
                SET
                    provider_status = 'paid',
                    paid_at = COALESCE(
                        paid_at,
                        FROM_UNIXTIME(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(provider_metadata, '$.data.attributes.paid_at')), ''))
                    ),
                    payment_date = COALESCE(
                        paid_at,
                        FROM_UNIXTIME(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(provider_metadata, '$.data.attributes.paid_at')), '')),
                        payment_date
                    )
                WHERE provider = 'paymongo'
                    AND provider_metadata IS NOT NULL
                    AND COALESCE(provider_status, '') IN ('', 'pending', 'processing', 'awaiting_payment_method', 'awaiting_next_action', 'active')
                    AND (
                        LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(provider_metadata, '$.data.attributes.payment_intent.attributes.status')), '')) IN ('paid', 'succeeded', 'approved', 'validated', 'authorized', 'authorised')
                        OR LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(provider_metadata, '$.data.attributes.payments[0].attributes.status')), '')) = 'paid'
                    )"
            );

            DB::statement(
                "UPDATE bills b
                JOIN (
                    SELECT DISTINCT bill_id
                    FROM payments
                    WHERE provider = 'paymongo'
                        AND provider_status IN ('paid', 'succeeded', 'completed', 'approved', 'validated', 'authorized', 'authorised')
                ) settled ON settled.bill_id = b.bill_id
                SET b.payment_status = 'Paid',
                    b.version = b.version + 1,
                    b.updated_at = NOW()
                WHERE b.payment_status NOT IN ('Paid', 'Waived')"
            );

            return;
        }

        $payments = DB::table('payments')
            ->where('provider', 'paymongo')
            ->whereIn('provider_status', ['pending', 'processing', 'awaiting_payment_method', 'awaiting_next_action', 'active', null])
            ->get(['payment_id', 'bill_id', 'provider_status', 'provider_metadata', 'paid_at', 'payment_date']);

        foreach ($payments as $payment) {
            if (! is_string($payment->provider_metadata) || trim($payment->provider_metadata) === '') {
                continue;
            }

            $metadata = json_decode($payment->provider_metadata, true);
            if (! is_array($metadata)) {
                continue;
            }

            $intentStatus = strtolower((string) Arr::get($metadata, 'data.attributes.payment_intent.attributes.status', ''));
            $paymentStatus = strtolower((string) Arr::get($metadata, 'data.attributes.payments.0.attributes.status', ''));

            if (! in_array($intentStatus, ['paid', 'succeeded', 'approved', 'validated', 'authorized', 'authorised'], true) && $paymentStatus !== 'paid') {
                continue;
            }

            DB::table('payments')
                ->where('payment_id', $payment->payment_id)
                ->update([
                    'provider_status' => 'paid',
                    'paid_at' => $payment->paid_at ?: now(),
                    'payment_date' => $payment->payment_date ?: now(),
                    'updated_at' => now(),
                ]);
        }

        $settledBillIds = DB::table('payments')
            ->where('provider', 'paymongo')
            ->whereIn('provider_status', ['paid', 'succeeded', 'completed', 'approved', 'validated', 'authorized', 'authorised'])
            ->distinct()
            ->pluck('bill_id');

        if ($settledBillIds->isNotEmpty()) {
            DB::table('bills')
                ->whereIn('bill_id', $settledBillIds)
                ->whereNotIn('payment_status', ['Paid', 'Waived'])
                ->update([
                    'payment_status' => 'Paid',
                    'version' => DB::raw('version + 1'),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_bill_status_date_index');
            $table->dropIndex('payments_provider_event_index');
            $table->dropIndex('payments_provider_reference_index');
            $table->dropIndex('payments_provider_bill_created_index');
        });
    }
};
