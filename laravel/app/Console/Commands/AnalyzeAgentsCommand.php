<?php

namespace App\Console\Commands;

use App\Services\AgentGovernanceService;
use Illuminate\Console\Command;

class AnalyzeAgentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:analyze-agents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze agent behavior and generate governance flags';

    /**
     * Execute the console command.
     */
    public function handle(AgentGovernanceService $governanceService)
    {
        $this->info('Starting agent behavior analysis...');

        $results = $governanceService->analyzeAllAgents();

        $this->info("Analysis complete.");
        $this->info("Agents Analyzed: {$results['processed']}");
        $this->info("Flags Generated: {$results['flags']}");
    }
}
