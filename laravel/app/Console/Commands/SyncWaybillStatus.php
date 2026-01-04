<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Lead;

class SyncWaybillStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:sync-waybill-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates Lead status based on latest Waybill status (Unlocks Delivered leads)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing Lead statuses from Waybills...');

        // 1. Mark DELIVERED
        // Find Leads that are currently 'SALE' but have a 'delivered' Waybill
        $affected = DB::update("
            UPDATE leads l
            JOIN waybills w ON l.id = w.lead_id
            SET l.status = 'DELIVERED', l.updated_at = NOW()
            WHERE l.status = 'SALE'
            AND w.status IN ('delivered', 'completed', 'success')
        ");
        $this->info("Updated {$affected} leads to DELIVERED.");

        // 2. Mark RETURNED
        // Find Leads that are 'SALE' or 'DELIVERED' but have a 'returned' Waybill
        // (Note: If multiple waybills exist, this logic might need refinement to pick the LATEST. 
        // But assuming 1 active waybill per sale cycle for now).
        $affectedReturned = DB::update("
            UPDATE leads l
            JOIN waybills w ON l.id = w.lead_id
            SET l.status = 'RETURNED', l.updated_at = NOW()
            WHERE l.status IN ('SALE', 'DELIVERED')
            AND w.status = 'returned'
        ");
        $this->info("Updated {$affectedReturned} leads to RETURNED.");

        $this->info('Sync complete.');
    }
}
