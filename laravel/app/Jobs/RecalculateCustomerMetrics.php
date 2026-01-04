<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\CustomerMetricsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecalculateCustomerMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The customer ID to update (null = all customers)
     */
    private ?string $customerId;

    /**
     * Create a new job instance
     *
     * @param string|null $customerId UUID of specific customer, or null for all
     */
    public function __construct(?string $customerId = null)
    {
        $this->customerId = $customerId;
    }

    /**
     * Execute the job
     */
    public function handle(CustomerMetricsService $metricsService): void
    {
        if ($this->customerId) {
            // Single customer update
            $this->updateSingleCustomer($metricsService);
        } else {
            // Bulk update all customers
            $this->updateAllCustomers($metricsService);
        }
    }

    /**
     * Update metrics for a single customer
     */
    private function updateSingleCustomer(CustomerMetricsService $metricsService): void
    {
        $customer = Customer::find($this->customerId);

        if (!$customer) {
            Log::warning("Customer not found for metrics update", [
                'customer_id' => $this->customerId
            ]);
            return;
        }

        try {
            $metricsService->updateCustomerMetrics($customer);

            Log::info("Updated metrics for customer {$this->customerId}", [
                'score' => $customer->customer_score,
                'risk' => $customer->risk_level
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update metrics for customer {$this->customerId}", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update metrics for all customers (nightly batch job)
     */
    private function updateAllCustomers(CustomerMetricsService $metricsService): void
    {
        $startTime = microtime(true);

        Log::info("Starting nightly customer metrics recalculation");

        // Get all customer IDs
        $customerIds = Customer::pluck('id');

        if ($customerIds->isEmpty()) {
            Log::info("No customers found for metrics recalculation");
            return;
        }

        // Bulk update with chunking
        $results = $metricsService->bulkUpdateMetrics($customerIds, 100);

        $duration = round(microtime(true) - $startTime, 2);

        Log::info("Nightly metrics recalculation complete", [
            'processed' => $results['processed'],
            'errors' => $results['errors'],
            'total_customers' => $results['total'],
            'duration_seconds' => $duration
        ]);

        // Get updated stats for monitoring
        $stats = $metricsService->getMetricsStats();
        Log::info("Customer metrics summary", $stats);
    }

    /**
     * The job failed to process
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("RecalculateCustomerMetrics job failed", [
            'customer_id' => $this->customerId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
