<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add a unique constraint to prevent duplicate rent bills for the same contract
     * in the same month. This ensures each tenant is only billed once per month for rent.
     */
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table): void {
            // Add a computed billing period column (YYYY-MM format)
            // This will be used for the unique constraint
            $table->string('billing_period', 7)->nullable()->after('bill_type');
        });

        // Populate existing bills with billing_period (database agnostic)
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement(
                "UPDATE bills SET billing_period = DATE_FORMAT(due_date, '%Y-%m') WHERE bill_type = 'Rent'"
            );
        } elseif ($driver === 'sqlite') {
            DB::statement(
                "UPDATE bills SET billing_period = strftime('%Y-%m', due_date) WHERE bill_type = 'Rent'"
            );
        } else {
            // PostgreSQL and others
            DB::statement(
                "UPDATE bills SET billing_period = TO_CHAR(due_date, 'YYYY-MM') WHERE bill_type = 'Rent'"
            );
        }

        // Delete duplicate rent bills, keeping only the most recent one for each contract/period
        // This prevents constraint violations when adding the unique index
        DB::statement("
            DELETE FROM bills 
            WHERE bill_id NOT IN (
                SELECT bill_id FROM (
                    SELECT MAX(bill_id) as bill_id
                    FROM bills
                    WHERE bill_type = 'Rent'
                    GROUP BY contract_id, billing_period
                ) AS keep_ids
            )
            AND bill_type = 'Rent'
        ");

        // Create unique constraint for Rent bills
        // This ensures (contract_id, bill_type='Rent', billing_period) is unique
        Schema::table('bills', function (Blueprint $table): void {
            $table->unique(['contract_id', 'bill_type', 'billing_period'], 'bills_contract_type_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table): void {
            $table->dropUnique('bills_contract_type_period_unique');
            $table->dropColumn('billing_period');
        });
    }
};
