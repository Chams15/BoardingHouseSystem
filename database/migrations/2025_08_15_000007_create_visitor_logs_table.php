<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->unsignedBigInteger('tenant_visited');
            $table->string('visitor_name', 100);
            $table->string('purpose', 255)->nullable();
            $table->dateTime('time_in')->useCurrent();
            $table->dateTime('time_out')->nullable();
            $table->timestamps();

            $table->foreign('tenant_visited')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
    }
};
