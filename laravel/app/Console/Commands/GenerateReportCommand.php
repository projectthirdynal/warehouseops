<?php

namespace App\Console\Commands;

use App\Services\ReportingService;
use Illuminate\Console\Command;

class GenerateReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:generate
                            {type : Report type (dashboard|ltv|funnel|agents|cohorts|risk)}
                            {--limit=50 : Limit for results}
                            {--months=6 : Number of months for cohorts/risk}
                            {--days=30 : Number of days for stats}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and display analytics reports';

    /**
     * Execute the console command.
     */
    public function handle(ReportingService $reportingService): int
    {
        $type = $this->argument('type');

        return match ($type) {
            'dashboard' => $this->showDashboard($reportingService),
            'ltv' => $this->showCustomerLTV($reportingService),
            'funnel' => $this->showRecyclingFunnel($reportingService),
            'agents' => $this->showAgentPerformance($reportingService),
            'cohorts' => $this->showCustomerCohorts($reportingService),
            'risk' => $this->showRiskTrends($reportingService),
            default => $this->error("Unknown report type: {$type}. Use: dashboard, ltv, funnel, agents, cohorts, or risk.")
        };
    }

    /**
     * Display dashboard statistics
     */
    private function showDashboard(ReportingService $reportingService): int
    {
        $this->info('=== Dashboard Statistics ===');
        $this->newLine();

        $data = $reportingService->getDashboardStats();

        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        // Customers
        $this->info('Customers:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Customers', number_format($data['customers']['total'])],
                ['With Orders', number_format($data['customers']['with_orders'])],
                ['Average Score', $data['customers']['avg_score']],
                ['Blacklisted', number_format($data['customers']['blacklisted'])],
            ]
        );

        $this->newLine();

        // Orders
        $this->info('Orders:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Orders', number_format($data['orders']['total'])],
                ['Delivered', number_format($data['orders']['delivered'])],
                ['Returned', number_format($data['orders']['returned'])],
                ['Pending', number_format($data['orders']['pending'])],
                ['Total Revenue', '₱' . number_format($data['orders']['total_revenue'], 2)],
            ]
        );

        $this->newLine();

        // Recycling Pool
        $this->info('Recycling Pool:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Available', number_format($data['recycling_pool']['available'])],
                ['Assigned', number_format($data['recycling_pool']['assigned'])],
                ['Converted', number_format($data['recycling_pool']['converted'])],
                ['Conversion Rate', $data['recycling_pool']['conversion_rate'] . '%'],
            ]
        );

        $this->newLine();

        // Leads
        $this->info('Leads:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Leads', number_format($data['leads']['total'])],
                ['Recycled Leads', number_format($data['leads']['recycled'])],
                ['Active Cycles', number_format($data['leads']['active_cycles'])],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Display customer lifetime value report
     */
    private function showCustomerLTV(ReportingService $reportingService): int
    {
        $limit = (int) $this->option('limit');
        $data = $reportingService->getCustomerLifetimeValue($limit);

        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info("Top {$limit} Customers by Lifetime Value");
        $this->newLine();

        $this->table(
            ['Name', 'Phone', 'Orders', 'Delivered', 'Value', 'Success %', 'Score', 'Risk'],
            collect($data['top_customers'])->map(fn($c) => [
                $c['name_display'],
                $c['phone_primary'],
                $c['total_orders'],
                $c['total_delivered'],
                '₱' . number_format($c['total_delivered_value'], 2),
                $c['delivery_success_rate'] . '%',
                $c['customer_score'],
                $c['risk_level'],
            ])->toArray()
        );

        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Customers', number_format($data['summary']['total_customers'])],
                ['With Orders', number_format($data['summary']['customers_with_orders'])],
                ['Total LTV', '₱' . number_format($data['summary']['total_lifetime_value'], 2)],
                ['Average LTV', '₱' . number_format($data['summary']['average_lifetime_value'], 2)],
                ['Avg Orders/Customer', round($data['summary']['average_orders_per_customer'], 1)],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Display recycling funnel analysis
     */
    private function showRecyclingFunnel(ReportingService $reportingService): int
    {
        $days = (int) $this->option('days');
        $startDate = now()->subDays($days);

        $data = $reportingService->getRecyclingFunnel($startDate);

        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info("Recycling Funnel (Last {$days} days)");
        $this->newLine();

        $this->info('Funnel Stages:');
        $this->table(
            ['Stage', 'Count'],
            [
                ['Total Entries', $data['funnel']['total_entries']],
                ['Available', $data['funnel']['available']],
                ['Assigned', $data['funnel']['assigned']],
                ['Converted', $data['funnel']['converted']],
                ['Exhausted', $data['funnel']['exhausted']],
                ['Expired', $data['funnel']['expired']],
            ]
        );

        $this->newLine();
        $this->info('Metrics:');
        $this->table(
            ['Metric', 'Rate'],
            [
                ['Conversion Rate', $data['metrics']['conversion_rate'] . '%'],
                ['Exhaustion Rate', $data['metrics']['exhaustion_rate'] . '%'],
                ['Expiration Rate', $data['metrics']['expiration_rate'] . '%'],
            ]
        );

        if (!empty($data['by_reason'])) {
            $this->newLine();
            $this->info('By Reason:');
            $this->table(
                ['Reason', 'Total', 'Converted', 'Exhausted', 'Conversion %'],
                collect($data['by_reason'])->map(fn($r) => [
                    $r['reason'],
                    $r['total'],
                    $r['converted'],
                    $r['exhausted'],
                    $r['conversion_rate'] . '%',
                ])->toArray()
            );
        }

        return self::SUCCESS;
    }

    /**
     * Display agent performance metrics
     */
    private function showAgentPerformance(ReportingService $reportingService): int
    {
        $days = (int) $this->option('days');
        $startDate = now()->subDays($days);

        $data = $reportingService->getAgentRecyclingPerformance($startDate);

        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info("Agent Performance on Recycled Leads (Last {$days} days)");
        $this->newLine();

        if ($data['agents']->isEmpty()) {
            $this->warn('No agent performance data available.');
            return self::SUCCESS;
        }

        $this->table(
            ['Agent', 'Assigned', 'Converted', 'Exhausted', 'Expired', 'Conv %', 'Avg Hours'],
            $data['agents']->map(fn($a) => [
                $a['agent_name'],
                $a['total_assigned'],
                $a['converted'],
                $a['exhausted'],
                $a['expired'],
                $a['conversion_rate'] . '%',
                $a['avg_hours_to_process'],
            ])->toArray()
        );

        $this->newLine();
        $this->info('Summary:');
        $this->line("Total Agents: {$data['summary']['total_agents']}");
        $this->line("Average Conversion Rate: {$data['summary']['average_conversion_rate']}%");

        if ($data['summary']['best_agent']) {
            $best = $data['summary']['best_agent'];
            $this->line("Best Agent: {$best['agent_name']} ({$best['conversion_rate']}%)");
        }

        return self::SUCCESS;
    }

    /**
     * Display customer cohorts analysis
     */
    private function showCustomerCohorts(ReportingService $reportingService): int
    {
        $months = (int) $this->option('months');
        $data = $reportingService->getCustomerCohorts($months);

        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info("Customer Cohorts (Last {$months} months)");
        $this->newLine();

        $this->table(
            ['Month', 'Customers', 'Orders', 'Delivered', 'Revenue', 'Success %', 'Score', 'Rev/Cust'],
            $data['cohorts']->map(fn($c) => [
                $c['month'],
                number_format($c['customers']),
                number_format($c['total_orders']),
                number_format($c['total_delivered']),
                '₱' . number_format($c['revenue'], 2),
                $c['avg_success_rate'] . '%',
                $c['avg_score'],
                '₱' . number_format($c['avg_revenue_per_customer'], 2),
            ])->toArray()
        );

        return self::SUCCESS;
    }

    /**
     * Display risk trends over time
     */
    private function showRiskTrends(ReportingService $reportingService): int
    {
        $months = (int) $this->option('months');
        $data = $reportingService->getRiskTrends($months);

        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info("Customer Risk Trends (Last {$months} months)");
        $this->newLine();

        $this->table(
            ['Month', 'Total', 'Unknown', 'Low', 'Medium', 'High', 'Blacklist'],
            $data['trends']->map(fn($t) => [
                $t['month'],
                number_format($t['total']),
                number_format($t['UNKNOWN']),
                number_format($t['LOW']),
                number_format($t['MEDIUM']),
                number_format($t['HIGH']),
                number_format($t['BLACKLIST']),
            ])->toArray()
        );

        return self::SUCCESS;
    }
}
