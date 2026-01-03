<?php

namespace App\Console\Commands;

use App\Services\LeadCycleLogicGuardian;
use Illuminate\Console\Command;

class GuardianAuditCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:guardian-audit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run AI Supervisor audit to detect system drift and rule violations';

    /**
     * Execute the console command.
     */
    public function handle(LeadCycleLogicGuardian $guardian)
    {
        $this->info('Starting Guardian System Audit...');

        $results = $guardian->auditSystem();

        $this->info("Audit Complete.");
        $this->info("Drift Events Detected: {$results['drift_detected']}");
        
        if ($results['drift_detected'] > 0) {
            $this->warn("Warnings have been logged to the system logs.");
        }
    }
}
