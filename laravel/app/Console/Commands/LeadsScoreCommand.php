<?php

namespace App\Console\Commands;

use App\Services\LeadScoringService;
use Illuminate\Console\Command;

class LeadsScoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:score';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate lead quality scores and archive decayed leads';

    /**
     * Execute the console command.
     */
    public function handle(LeadScoringService $scoringService)
    {
        $this->info('Starting lead scoring and decay process...');

        $results = $scoringService->processDecay();

        $this->info("Process complete.");
        $this->info("Leads Processed: {$results['processed']}");
        $this->info("Leads Archived: {$results['archived']}");
    }
}
