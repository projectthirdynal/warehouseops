<?php

namespace App\Console\Commands;

use App\Models\LeadSnapshot;
use App\Services\SnapshotService;
use Illuminate\Console\Command;

class RestoreLeadSnapshotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:restore {snapshot_id : The ID of the snapshot to restore from}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore a lead to a previous snapshot state.';

    /**
     * Execute the console command.
     */
    public function handle(SnapshotService $snapshotService)
    {
        $snapshotId = $this->argument('snapshot_id');
        $snapshot = LeadSnapshot::find($snapshotId);

        if (!$snapshot) {
            $this->error("Snapshot #{$snapshotId} not found.");
            return 1;
        }

        $this->info("You are about to restore Lead #{$snapshot->lead_id} to its state from {$snapshot->created_at} (Reason: {$snapshot->reason}).");
        $this->warn("Current data will be overwritten (a safety snapshot will be taken first).");

        if (!$this->confirm('Do you wish to continue?')) {
            $this->info("Operation cancelled.");
            return 0;
        }

        try {
            $snapshotService->restore($snapshotId);
            $this->info("Lead restored successfully.");
        } catch (\Exception $e) {
            $this->error("Restoration failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
