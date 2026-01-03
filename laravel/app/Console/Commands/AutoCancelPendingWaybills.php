<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AutoCancelPendingWaybills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waybills:auto-cancel-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-cancel pending waybills older than 5 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = 5;
        $cutoffDate = now()->subDays($days);

        // Cancel waybills that have been in 'issue_pending' status for more than 5 days
        $count = \App\Models\Waybill::where('status', 'issue_pending')
            ->where('marked_pending_at', '<', $cutoffDate)
            ->update(['status' => 'cancelled']);

        $this->info("Cancelled {$count} pending waybills older than {$days} days.");
        
        // Log the cancellation (optional, but good for audit)
        if ($count > 0) {
            \Illuminate\Support\Facades\Log::info("Auto-cancelled {$count} pending waybills older than {$days} days.");
        }
    }
}
