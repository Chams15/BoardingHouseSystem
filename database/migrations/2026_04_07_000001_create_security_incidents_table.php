<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_incidents', function (Blueprint $table) {
            $table->id('incident_id');
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->string('title', 150);
            $table->text('description');
            $table->enum('severity', ['Low', 'Medium', 'High'])->default('Medium');
            $table->enum('status', ['Open', 'Investigating', 'Resolved'])->default('Open');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('reported_by')->references('user_id')->on('users')->nullOnDelete();
            $table->index(['status', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_incidents');
    }
};
