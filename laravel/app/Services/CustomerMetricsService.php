<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerOrderHistory;
use Illuminate\Support\Facades\Log;

class CustomerMetricsService
{
    /**
     * Update all metrics for a customer based on their order history
     */
    public function updateCustomerMetrics(Customer $customer): void
    {
        // Query all order history for this customer
        $orders = CustomerOrderHistory::where('customer_id', $customer->id)->get();

        // Calculate counts
        $metrics = [
            'total_orders' => $orders->count(),
            'total_delivered' => $orders->where('current_status', CustomerOrderHistory::STATUS_DELIVERED)->count(),
            'total_returned' => $orders->whereIn('current_status', [
                CustomerOrderHistory::STATUS_RETURNED,
                'RTS' // Return to sender
            ])->count(),
            'total_pending' => $orders->where('current_status', CustomerOrderHistory::STATUS_PENDING)->count(),
            'total_in_transit' => $orders->whereIn('current_status', [
                CustomerOrderHistory::STATUS_IN_TRANSIT,
                CustomerOrderHistory::STATUS_DELIVERING
            ])->count(),

            // Financial totals
            'total_order_value' => $orders->sum('cod_amount'),
            'total_delivered_value' => $orders->where('current_status', CustomerOrderHistory::STATUS_DELIVERED)->sum('cod_amount'),
            'total_returned_value' => $orders->whereIn('current_status', [
                CustomerOrderHistory::STATUS_RETURNED,
                'RTS'
            ])->sum('cod_amount'),

            // Dates
            'last_order_date' => $orders->max('order_date'),
            'last_delivery_date' => $orders->whereNotNull('delivered_date')->max('delivered_date'),
        ];

        // Calculate delivery success rate
        $completedOrders = $metrics['total_delivered'] + $metrics['total_returned'];
        $metrics['delivery_success_rate'] = $completedOrders > 0
            ? round(($metrics['total_delivered'] / $completedOrders) * 100, 2)
            : 0;

        // Calculate customer score (0-100)
        $metrics['customer_score'] = $this->calculateCustomerScore($metrics);

        // Determine risk level
        $metrics['risk_level'] = $this->determineRiskLevel($metrics);

        // Update customer record
        $customer->update(array_merge($metrics, [
            'updated_at' => now()
        ]));

        Log::info("Updated metrics for customer {$customer->id}", [
            'score' => $metrics['customer_score'],
            'risk' => $metrics['risk_level'],
            'orders' => $metrics['total_orders'],
            'success_rate' => $metrics['delivery_success_rate']
        ]);
    }

    /**
     * Calculate customer score (0-100) based on performance metrics
     *
     * Algorithm from specification:
     * - Base score: 50
     * - Delivery success rate impact: ±30 (if ≥3 orders)
     * - Order volume bonus: +10 max
     * - Recent activity bonus: +10 max
     */
    public function calculateCustomerScore(array $metrics): int
    {
        $score = 50; // Base score

        // Delivery success rate impact (max ±30)
        // Only apply if customer has 3+ orders (statistical significance)
        if ($metrics['total_orders'] >= 3) {
            // Formula: (success_rate - 50) * 0.6
            // 0% success = -30 points, 100% success = +30 points
            $score += ($metrics['delivery_success_rate'] - 50) * 0.6;
        }

        // Order volume bonus (max +10)
        // Each order adds 2 points, capped at 10
        $score += min($metrics['total_orders'] * 2, 10);

        // Recent activity bonus (max +10)
        if ($metrics['last_delivery_date']) {
            $daysSinceDelivery = now()->diffInDays($metrics['last_delivery_date']);

            if ($daysSinceDelivery < 30) {
                // Very recent activity
                $score += 10;
            } elseif ($daysSinceDelivery < 90) {
                // Recent activity (last 3 months)
                $score += 5;
            }
            // No bonus if last delivery was >90 days ago
        }

        // Clamp score to 0-100 range
        return max(0, min(100, round($score)));
    }

    /**
     * Determine customer risk level based on performance
     *
     * BLACKLIST: 5+ orders with <30% success rate
     * HIGH: <50% success rate
     * MEDIUM: 50-70% success rate
     * LOW: ≥70% success rate
     */
    public function determineRiskLevel(array $metrics): string
    {
        $successRate = $metrics['delivery_success_rate'];
        $totalOrders = $metrics['total_orders'];

        // Blacklist: Consistent failure pattern (5+ orders, <30% success)
        if ($totalOrders >= 5 && $successRate < 30) {
            return Customer::RISK_BLACKLIST;
        }

        // High risk: More failures than successes
        if ($successRate < 50) {
            return Customer::RISK_HIGH;
        }

        // Medium risk: 50-70% success rate
        if ($successRate < 70) {
            return Customer::RISK_MEDIUM;
        }

        // Low risk: ≥70% success rate
        return Customer::RISK_LOW;
    }

    /**
     * Batch update metrics for multiple customers
     * Used by scheduled jobs and bulk operations
     */
    public function bulkUpdateMetrics(iterable $customerIds, int $chunkSize = 100): array
    {
        $processed = 0;
        $errors = 0;

        // Process in chunks to avoid memory issues
        $chunks = collect($customerIds)->chunk($chunkSize);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $customerId) {
                try {
                    $customer = Customer::find($customerId);
                    if ($customer) {
                        $this->updateCustomerMetrics($customer);
                        $processed++;
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to update metrics for customer {$customerId}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $errors++;
                }
            }
        }

        return [
            'processed' => $processed,
            'errors' => $errors,
            'total' => $processed + $errors
        ];
    }

    /**
     * Get customers that need metrics recalculation
     * Returns customers with order history but stale metrics
     */
    public function getCustomersNeedingUpdate(int $hoursSinceUpdate = 24): \Illuminate\Database\Eloquent\Collection
    {
        return Customer::whereHas('orderHistory')
            ->where(function ($query) use ($hoursSinceUpdate) {
                $query->where('updated_at', '<', now()->subHours($hoursSinceUpdate))
                    ->orWhereNull('updated_at');
            })
            ->get();
    }

    /**
     * Quick stats for monitoring/reporting
     */
    public function getMetricsStats(): array
    {
        return [
            'total_customers' => Customer::count(),
            'customers_with_orders' => Customer::whereHas('orderHistory')->count(),
            'risk_distribution' => [
                'blacklist' => Customer::where('risk_level', Customer::RISK_BLACKLIST)->count(),
                'high' => Customer::where('risk_level', Customer::RISK_HIGH)->count(),
                'medium' => Customer::where('risk_level', Customer::RISK_MEDIUM)->count(),
                'low' => Customer::where('risk_level', Customer::RISK_LOW)->count(),
                'unknown' => Customer::where('risk_level', Customer::RISK_UNKNOWN)->count(),
            ],
            'average_score' => Customer::avg('customer_score'),
            'score_distribution' => [
                'excellent' => Customer::where('customer_score', '>=', 76)->count(), // 76-100
                'good' => Customer::whereBetween('customer_score', [51, 75])->count(), // 51-75
                'fair' => Customer::whereBetween('customer_score', [26, 50])->count(), // 26-50
                'poor' => Customer::where('customer_score', '<=', 25)->count(), // 0-25
            ]
        ];
    }
}
