<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE bills MODIFY payment_status ENUM('Unpaid','Pending','Paid','Overdue','Waived') NOT NULL DEFAULT 'Unpaid'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("UPDATE bills SET payment_status = 'Unpaid' WHERE payment_status = 'Pending'");
        DB::statement("ALTER TABLE bills MODIFY payment_status ENUM('Unpaid','Paid','Overdue','Waived') NOT NULL DEFAULT 'Unpaid'");
    }
};
