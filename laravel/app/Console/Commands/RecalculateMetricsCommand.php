<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\CustomerMetricsService;
use Illuminate\Console\Command;

class RecalculateMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:recalculate
                            {--customer= : UUID of specific customer to update}
                            {--chunk=100 : Number of customers to process per chunk}
                            {--stale-only : Only update customers with stale metrics (>24 hours old)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate customer performance metrics and scores';

    /**
     * Execute the console command.
     */
    public function handle(CustomerMetricsService $metricsService): int
    {
        $this->info('Customer Metrics Recalculation');
        $this->newLine();

        // Single customer update
        if ($customerId = $this->option('customer')) {
            return $this->updateSingleCustomer($customerId, $metricsService);
        }

        // Bulk update
        return $this->updateMultipleCustomers($metricsService);
    }

    /**
     * Update metrics for a single customer
     */
    private function updateSingleCustomer(string $customerId, CustomerMetricsService $metricsService): int
    {
        $customer = Customer::find($customerId);

        if (!$customer) {
            $this->error("Customer not found: {$customerId}");
            return self::FAILURE;
        }

        $this->info("Updating metrics for customer: {$customer->name_display} ({$customer->phone_primary})");

        try {
            $metricsService->updateCustomerMetrics($customer);

            $this->newLine();
            $this->info('✓ Metrics updated successfully');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Orders', $customer->total_orders],
                    ['Delivered', $customer->total_delivered],
                    ['Returned', $customer->total_returned],
                    ['Success Rate', round($customer->delivery_success_rate, 2) . '%'],
                    ['Customer Score', $customer->customer_score . '/100'],
                    ['Risk Level', $customer->risk_level],
                ]
            );

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to update metrics: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Update metrics for multiple customers
     */
    private function updateMultipleCustomers(CustomerMetricsService $metricsService): int
    {
        $chunkSize = (int) $this->option('chunk');

        // Get customers to update
        if ($this->option('stale-only')) {
            $this->info('Updating customers with stale metrics (>24 hours old)...');
            $customers = $metricsService->getCustomersNeedingUpdate(24);
        } else {
            $this->info('Updating ALL customers...');
            $customers = Customer::all();
        }

        if ($customers->isEmpty()) {
            $this->info('No customers found to update.');
            return self::SUCCESS;
        }

        $totalCustomers = $customers->count();
        $this->info("Found {$totalCustomers} customers to process.");
        $this->newLine();

        // Create progress bar
        $progressBar = $this->output->createProgressBar($totalCustomers);
        $progressBar->start();

        $processed = 0;
        $errors = 0;

        // Process in chunks
        foreach ($customers->chunk($chunkSize) as $chunk) {
            foreach ($chunk as $customer) {
                try {
                    $metricsService->updateCustomerMetrics($customer);
                    $processed++;
                } catch (\Exception $e) {
                    $this->error("\nFailed for customer {$customer->id}: {$e->getMessage()}");
                    $errors++;
                }

                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->info('✓ Metrics recalculation complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $processed],
                ['Errors', $errors],
                ['Total', $totalCustomers],
            ]
        );

        // Display summary statistics
        $this->newLine();
        $this->displaySummaryStats($metricsService);

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Display summary statistics after bulk update
     */
    private function displaySummaryStats(CustomerMetricsService $metricsService): void
    {
        $stats = $metricsService->getMetricsStats();

        $this->info('System-wide Customer Metrics Summary:');
        $this->table(
            ['Category', 'Count'],
            [
                ['Total Customers', $stats['total_customers']],
                ['Customers with Orders', $stats['customers_with_orders']],
            ]
        );

        $this->newLine();
        $this->info('Risk Level Distribution:');
        $this->table(
            ['Risk Level', 'Count'],
            [
                ['Blacklist', $stats['risk_distribution']['blacklist']],
                ['High Risk', $stats['risk_distribution']['high']],
                ['Medium Risk', $stats['risk_distribution']['medium']],
                ['Low Risk', $stats['risk_distribution']['low']],
                ['Unknown', $stats['risk_distribution']['unknown']],
            ]
        );

        $this->newLine();
        $this->info('Customer Score Distribution:');
        $this->table(
            ['Score Range', 'Count'],
            [
                ['Excellent (76-100)', $stats['score_distribution']['excellent']],
                ['Good (51-75)', $stats['score_distribution']['good']],
                ['Fair (26-50)', $stats['score_distribution']['fair']],
                ['Poor (0-25)', $stats['score_distribution']['poor']],
            ]
        );

        $this->info('Average Customer Score: ' . round($stats['average_score'], 2) . '/100');
    }
}
