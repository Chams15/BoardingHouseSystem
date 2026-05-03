<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_financial_reports', function (Blueprint $table): void {
            $table->bigIncrements('report_id');
            $table->string('report_month', 7)->unique();
            $table->string('report_label');
            $table->string('file_path');
            $table->json('summary_payload')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_financial_reports');
    }
};
