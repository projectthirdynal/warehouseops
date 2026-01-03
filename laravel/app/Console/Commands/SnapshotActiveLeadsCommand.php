<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Services\SnapshotService;
use Illuminate\Console\Command;

class SnapshotActiveLeadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:snapshot-active';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Take a snapshot of all currently active or recently updated leads for disaster recovery.';

    /**
     * Execute the console command.
     */
    public function handle(SnapshotService $snapshotService)
    {
        $this->info('Starting snapshot of active leads...');

        // Query: Leads that are actively being worked on
        // 1. Leads with status CALLING or CALLBACK
        // 2. Leads updated in the last 24 hours (covers new REJECTs, SALEs, etc.)
        $leads = Lead::whereIn('status', [Lead::STATUS_CALLING, Lead::STATUS_CALLBACK])
            ->orWhere('updated_at', '>=', now()->subHours(24))
            ->with('activeCycle')
            ->cursor(); // Use cursor for memory efficiency

        $count = 0;

        foreach ($leads as $lead) {
            try {
                $snapshotService->capture($lead, 'daily_backup');
                $count++;
                if ($count % 100 === 0) {
                    $this->info("Snapshotted {$count} leads...");
                }
            } catch (\Exception $e) {
                $this->error("Failed to snapshot Lead {$lead->id}: " . $e->getMessage());
            }
        }

        $this->info("Snapshot complete. Total leads captured: {$count}");
    }
}
