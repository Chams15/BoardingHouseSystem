<?php

namespace App\Console\Commands;

use App\Models\LeaseContract;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoRenewLeases extends Command
{
    protected $signature = 'leases:auto-renew';

    protected $description = 'Auto-renew active leases on their renewal date unless cancellation was requested';

    public function handle(): int
    {
        $today = now()->startOfDay();
        $renewed = 0;
        $failed = 0;
        $errors = [];

        try {
            DB::transaction(function () use ($today, &$renewed, &$failed, &$errors): void {
                // Find all leases eligible for auto-renewal
                $leases = LeaseContract::query()
                    ->where('auto_renew', true)
                    ->where('contract_status', 'Active')
                    ->where('next_renewal_date', '<=', $today)
                    ->whereNull('renewal_cancel_requested_date')
                    ->lockForUpdate()
                    ->get();

                foreach ($leases as $lease) {
                    try {
                        $lease->autoRenew();
                        $renewed++;
                        $this->line("✓ Renewed lease #{$lease->contract_id} for tenant {$lease->tenant->name}");
                    } catch (\Exception $e) {
                        $failed++;
                        $errors[] = "Lease #{$lease->contract_id}: {$e->getMessage()}";
                        $this->error("✗ Failed to renew lease #{$lease->contract_id}: {$e->getMessage()}");
                    }
                }

                // Handle leases where move-out final date has arrived
                $moveOutLeases = LeaseContract::query()
                    ->where('contract_status', 'Pending_MoveOut')
                    ->whereNotNull('move_out_final_date')
                    ->where('move_out_final_date', '<=', now())
                    ->lockForUpdate()
                    ->get();

                foreach ($moveOutLeases as $lease) {
                    try {
                        $lease->completeMoveOut();
                        $this->line("✓ Completed move-out for lease #{$lease->contract_id}");
                    } catch (\Exception $e) {
                        $this->error("✗ Failed to complete move-out for lease #{$lease->contract_id}: {$e->getMessage()}");
                    }
                }
            });

            $this->info("\n═══════════════════════════════════════");
            $this->info("Auto-Renewal Summary");
            $this->info("═══════════════════════════════════════");
            $this->info("Leases renewed: {$renewed}");
            $this->info("Failures: {$failed}");

            if (!empty($errors)) {
                $this->error("\nErrors encountered:");
                foreach ($errors as $error) {
                    $this->error("  - {$error}");
                }
            }

            return $failed > 0 ? 1 : 0;
        } catch (\Exception $e) {
            $this->error("Fatal error during auto-renewal: {$e->getMessage()}");

            return 1;
        }
    }
}
