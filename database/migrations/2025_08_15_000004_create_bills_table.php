<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id('bill_id');
            $table->unsignedBigInteger('contract_id');
            $table->enum('bill_type', ['Rent', 'Utility', 'Repair', 'Misc']);
            $table->string('description', 255)->nullable();
            $table->decimal('amount_due', 10, 2);
            $table->date('due_date');
            $table->enum('payment_status', ['Unpaid', 'Paid', 'Overdue', 'Waived'])->default('Unpaid');
            $table->timestamps();

            $table->foreign('contract_id')->references('contract_id')->on('lease_contracts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
