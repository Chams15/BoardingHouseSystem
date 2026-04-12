<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('bill_id');
            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_method', 20);
            $table->string('provider', 50)->nullable();
            $table->string('provider_checkout_session_id', 100)->nullable();
            $table->string('provider_payment_intent_id', 100)->nullable();
            $table->string('provider_event_id', 100)->nullable();
            $table->string('provider_status', 30)->nullable();
            $table->string('reference_no', 50)->nullable();
            $table->string('receipt_url', 255)->nullable();
            $table->string('checkout_url', 500)->nullable();
            $table->json('provider_metadata')->nullable();
            $table->timestamp('checkout_expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('payment_date')->useCurrent();
            $table->timestamps();

            $table->foreign('bill_id')->references('bill_id')->on('bills')->onDelete('cascade');
            $table->index(['provider', 'provider_status'], 'payments_provider_status_index');
            $table->index('provider_checkout_session_id', 'payments_checkout_session_index');
            $table->index('provider_payment_intent_id', 'payments_payment_intent_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
