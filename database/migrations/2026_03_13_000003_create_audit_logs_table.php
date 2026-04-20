<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id('audit_log_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('event_type', 32); // created, updated, deleted, rolled_back
            $table->string('table_name', 100);
            $table->string('record_pk_column', 100);
            $table->string('record_pk', 100);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('action_meta')->nullable();
            $table->unsignedBigInteger('rollback_of_audit_log_id')->nullable();
            $table->timestamps();

            $table->foreign('actor_user_id')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('rollback_of_audit_log_id')->references('audit_log_id')->on('audit_logs')->nullOnDelete();

            $table->index(['table_name', 'record_pk']);
            $table->index(['event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
