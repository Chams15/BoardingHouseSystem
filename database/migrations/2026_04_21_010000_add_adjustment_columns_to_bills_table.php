<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table): void {
            $table->decimal('original_amount_due', 10, 2)->nullable()->after('description');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('original_amount_due');
            $table->string('discount_reason', 500)->nullable()->after('discount_amount');
            $table->decimal('waived_amount', 10, 2)->default(0)->after('discount_reason');
            $table->string('waived_reason', 500)->nullable()->after('waived_amount');
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table): void {
            $table->dropColumn([
                'original_amount_due',
                'discount_amount',
                'discount_reason',
                'waived_amount',
                'waived_reason',
            ]);
        });
    }
};
