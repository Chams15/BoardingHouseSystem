<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add auto-renewal support to lease contracts for monthly renewal system.
     * Leases will auto-renew monthly unless explicitly disabled by the tenant.
     */
    public function up(): void
    {
        Schema::table('lease_contracts', function (Blueprint $table): void {
            // Whether the lease auto-renews each month (default true)
            $table->boolean('auto_renew')->default(true)->after('contract_status');
            
            // The next date when this lease will be auto-renewed
            // If null, lease has been terminated or auto-renewal is disabled
            $table->date('next_renewal_date')->nullable()->after('auto_renew');
            
            // Tracks when tenant requested cancellation of auto-renewal
            // Allows enforcing notice period before lease stops renewing
            $table->date('renewal_cancel_requested_date')->nullable()->after('next_renewal_date');
            
            // Used to track when a lease month completes so tenants can move out at month-end
            $table->timestamp('move_out_final_date')->nullable()->after('renewal_cancel_requested_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lease_contracts', function (Blueprint $table): void {
            $table->dropColumn(['auto_renew', 'next_renewal_date', 'renewal_cancel_requested_date', 'move_out_final_date']);
        });
    }
};