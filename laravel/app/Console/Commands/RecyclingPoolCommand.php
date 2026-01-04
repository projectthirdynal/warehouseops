<?php

namespace App\Console\Commands;

use App\Services\RecycledLeadService;
use App\Services\RecyclingPoolService;
use Illuminate\Console\Command;

class RecyclingPoolCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recycling:pool
                            {action=stats : Action to perform (stats|cleanup|conversion-stats)}
                            {--days=30 : Number of days for statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage and view recycling pool statistics';

    /**
     * Execute the console command.
     */
    public function handle(
        RecyclingPoolService $poolService,
        RecycledLeadService $recycledLeadService
    ): int {
        $action = $this->argument('action');

        return match ($action) {
            'stats' => $this->showStats($poolService),
            'cleanup' => $this->runCleanup($poolService),
            'conversion-stats' => $this->showConversionStats($recycledLeadService),
            default => $this->error("Unknown action: {$action}")
        };
    }

    /**
     * Show recycling pool statistics
     */
    private function showStats(RecyclingPoolService $poolService): int
    {
        $this->info('Recycling Pool Statistics');
        $this->newLine();

        $stats = $poolService->getPoolStats();

        // Status Distribution
        $this->info('Status Distribution:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Available', $stats['total_available']],
                ['Assigned', $stats['total_assigned']],
                ['Converted', $stats['total_converted']],
                ['Exhausted', $stats['total_exhausted']],
                ['Expired', $stats['total_expired']],
            ]
        );

        $this->newLine();

        // By Reason
        if (!empty($stats['by_reason'])) {
            $this->info('Available Leads by Reason:');
            $reasonData = [];
            foreach ($stats['by_reason'] as $reason => $count) {
                $reasonData[] = [$reason, $count];
            }
            $this->table(['Reason', 'Count'], $reasonData);
        }

        $this->newLine();
        $this->info('Average Priority Score: ' . round($stats['avg_priority'] ?? 0, 2));

        return self::SUCCESS;
    }

    /**
     * Run cleanup operations
     */
    private function runCleanup(RecyclingPoolService $poolService): int
    {
        $this->info('Running recycling pool cleanup...');

        $expiredCount = $poolService->cleanupExpired();
        $staleCount = $poolService->releaseStaleAssignments(24);

        $this->newLine();
        $this->info("✓ Cleanup complete");
        $this->table(
            ['Operation', 'Count'],
            [
                ['Expired entries cleaned', $expiredCount],
                ['Stale assignments released', $staleCount],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Show conversion statistics
     */
    private function showConversionStats(RecycledLeadService $recycledLeadService): int
    {
        $days = (int) $this->option('days');
        $startDate = now()->subDays($days);

        $this->info("Recycling Pool Conversion Statistics (Last {$days} days)");
        $this->newLine();

        $stats = $recycledLeadService->getConversionStats($startDate);

        // Overall Stats
        $this->info('Overall Performance:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Pool Entries', $stats['total']],
                ['Converted to Sales', $stats['converted']],
                ['Exhausted', $stats['exhausted']],
                ['Expired', $stats['expired']],
                ['Still Active', $stats['active']],
                ['Conversion Rate', $stats['conversion_rate'] . '%'],
            ]
        );

        $this->newLine();

        // By Reason
        if (!empty($stats['by_reason'])) {
            $this->info('Pool Entries by Reason:');
            $reasonData = [];
            foreach ($stats['by_reason'] as $reason => $count) {
                $reasonData[] = [$reason, $count];
            }
            $this->table(['Reason', 'Count'], $reasonData);
        }

        $this->newLine();

        // Agent Performance
        $agentPerformance = $recycledLeadService->getAgentPerformance($startDate);
        if (!empty($agentPerformance)) {
            $this->info('Top Performing Agents (Recycled Lead Conversions):');
            $this->table(
                ['Agent', 'Conversions'],
                array_map(fn($agent) => [$agent['agent_name'], $agent['conversions']], $agentPerformance)
            );
        }

        return self::SUCCESS;
    }
}
