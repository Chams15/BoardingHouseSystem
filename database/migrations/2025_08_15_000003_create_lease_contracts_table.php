<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lease_contracts', function (Blueprint $table) {
            $table->id('contract_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('room_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('security_deposit', 10, 2)->default(0.00);
            $table->enum('contract_status', ['Active', 'Pending_MoveOut', 'Terminated'])->default('Active');
            $table->date('move_out_req_date')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('room_id')->references('room_id')->on('rooms')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_contracts');
    }
};
