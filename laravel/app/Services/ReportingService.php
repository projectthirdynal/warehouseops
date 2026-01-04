<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerOrderHistory;
use App\Models\Lead;
use App\Models\LeadCycle;
use App\Models\LeadRecyclingPool;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    /**
     * Get customer lifetime value analysis
     */
    public function getCustomerLifetimeValue(?int $limit = 50): array
    {
        $topCustomers = Customer::select([
                'id',
                'name_display',
                'phone_primary',
                'total_orders',
                'total_delivered',
                'total_delivered_value',
                'delivery_success_rate',
                'customer_score',
                'risk_level'
            ])
            ->where('total_orders', '>', 0)
            ->orderByDesc('total_delivered_value')
            ->limit($limit)
            ->get();

        return [
            'top_customers' => $topCustomers,
            'summary' => [
                'total_customers' => Customer::count(),
                'customers_with_orders' => Customer::where('total_orders', '>', 0)->count(),
                'total_lifetime_value' => Customer::sum('total_delivered_value'),
                'average_lifetime_value' => Customer::where('total_orders', '>', 0)->avg('total_delivered_value'),
                'average_orders_per_customer' => Customer::where('total_orders', '>', 0)->avg('total_orders'),
            ]
        ];
    }

    /**
     * Get recycling conversion funnel
     */
    public function getRecyclingFunnel(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $query = LeadRecyclingPool::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $total = $query->count();

        // Funnel stages
        $available = (clone $query)->where('pool_status', LeadRecyclingPool::STATUS_AVAILABLE)->count();
        $assigned = (clone $query)->where('pool_status', LeadRecyclingPool::STATUS_ASSIGNED)->count();
        $converted = (clone $query)->where('pool_status', LeadRecyclingPool::STATUS_CONVERTED)->count();
        $exhausted = (clone $query)->where('pool_status', LeadRecyclingPool::STATUS_EXHAUSTED)->count();
        $expired = (clone $query)->where('pool_status', LeadRecyclingPool::STATUS_EXPIRED)->count();

        // Conversion metrics
        $conversionRate = $total > 0 ? ($converted / $total) * 100 : 0;
        $exhaustionRate = $total > 0 ? ($exhausted / $total) * 100 : 0;
        $expirationRate = $total > 0 ? ($expired / $total) * 100 : 0;

        // By reason breakdown
        $byReason = (clone $query)
            ->select('recycle_reason',
                DB::raw('count(*) as total'),
                DB::raw('sum(case when pool_status = "CONVERTED" then 1 else 0 end) as converted'),
                DB::raw('sum(case when pool_status = "EXHAUSTED" then 1 else 0 end) as exhausted')
            )
            ->groupBy('recycle_reason')
            ->get()
            ->map(function ($item) {
                $convRate = $item->total > 0 ? ($item->converted / $item->total) * 100 : 0;
                return [
                    'reason' => $item->recycle_reason,
                    'total' => $item->total,
                    'converted' => $item->converted,
                    'exhausted' => $item->exhausted,
                    'conversion_rate' => round($convRate, 2)
                ];
            });

        return [
            'funnel' => [
                'total_entries' => $total,
                'available' => $available,
                'assigned' => $assigned,
                'converted' => $converted,
                'exhausted' => $exhausted,
                'expired' => $expired,
            ],
            'metrics' => [
                'conversion_rate' => round($conversionRate, 2),
                'exhaustion_rate' => round($exhaustionRate, 2),
                'expiration_rate' => round($expirationRate, 2),
            ],
            'by_reason' => $byReason
        ];
    }

    /**
     * Get agent performance on recycled leads
     */
    public function getAgentRecyclingPerformance(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $query = LeadRecyclingPool::query()
            ->whereNotNull('assigned_to');

        if ($startDate) {
            $query->where('assigned_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('assigned_at', '<=', $endDate);
        }

        $agentStats = $query
            ->select('assigned_to')
            ->selectRaw('count(*) as total_assigned')
            ->selectRaw('sum(case when pool_status = "CONVERTED" then 1 else 0 end) as converted')
            ->selectRaw('sum(case when pool_status = "EXHAUSTED" then 1 else 0 end) as exhausted')
            ->selectRaw('sum(case when pool_status = "EXPIRED" then 1 else 0 end) as expired')
            ->selectRaw('avg(case when processed_at is not null then timestampdiff(HOUR, assigned_at, processed_at) else null end) as avg_hours_to_process')
            ->groupBy('assigned_to')
            ->with('assignedAgent:id,name')
            ->get()
            ->map(function ($stat) {
                $conversionRate = $stat->total_assigned > 0 ? ($stat->converted / $stat->total_assigned) * 100 : 0;
                return [
                    'agent_id' => $stat->assigned_to,
                    'agent_name' => $stat->assignedAgent?->name ?? 'Unknown',
                    'total_assigned' => $stat->total_assigned,
                    'converted' => $stat->converted,
                    'exhausted' => $stat->exhausted,
                    'expired' => $stat->expired,
                    'conversion_rate' => round($conversionRate, 2),
                    'avg_hours_to_process' => round($stat->avg_hours_to_process ?? 0, 1)
                ];
            })
            ->sortByDesc('conversion_rate')
            ->values();

        return [
            'agents' => $agentStats,
            'summary' => [
                'total_agents' => $agentStats->count(),
                'best_agent' => $agentStats->first(),
                'average_conversion_rate' => round($agentStats->avg('conversion_rate'), 2),
            ]
        ];
    }

    /**
     * Get customer cohort analysis by first order month
     */
    public function getCustomerCohorts(int $months = 6): array
    {
        $cohorts = Customer::select(
                DB::raw('DATE_FORMAT(first_seen_at, "%Y-%m") as cohort_month'),
                DB::raw('count(*) as customers'),
                DB::raw('sum(total_orders) as total_orders'),
                DB::raw('sum(total_delivered) as total_delivered'),
                DB::raw('sum(total_delivered_value) as revenue'),
                DB::raw('avg(delivery_success_rate) as avg_success_rate'),
                DB::raw('avg(customer_score) as avg_score')
            )
            ->where('first_seen_at', '>=', now()->subMonths($months))
            ->groupBy('cohort_month')
            ->orderBy('cohort_month', 'desc')
            ->get()
            ->map(function ($cohort) {
                return [
                    'month' => $cohort->cohort_month,
                    'customers' => $cohort->customers,
                    'total_orders' => $cohort->total_orders,
                    'total_delivered' => $cohort->total_delivered,
                    'revenue' => round($cohort->revenue, 2),
                    'avg_success_rate' => round($cohort->avg_success_rate, 2),
                    'avg_score' => round($cohort->avg_score, 2),
                    'avg_revenue_per_customer' => round($cohort->revenue / $cohort->customers, 2),
                ];
            });

        return [
            'cohorts' => $cohorts,
            'period_months' => $months
        ];
    }

    /**
     * Get customer risk distribution over time
     */
    public function getRiskTrends(int $months = 3): array
    {
        $riskTrends = Customer::select(
                DB::raw('DATE_FORMAT(updated_at, "%Y-%m") as month'),
                'risk_level',
                DB::raw('count(*) as count')
            )
            ->where('updated_at', '>=', now()->subMonths($months))
            ->groupBy('month', 'risk_level')
            ->orderBy('month', 'desc')
            ->get()
            ->groupBy('month')
            ->map(function ($monthData, $month) {
                $distribution = [
                    'month' => $month,
                    'LOW' => 0,
                    'MEDIUM' => 0,
                    'HIGH' => 0,
                    'BLACKLIST' => 0,
                    'UNKNOWN' => 0,
                    'total' => 0
                ];

                foreach ($monthData as $item) {
                    $distribution[$item->risk_level] = $item->count;
                    $distribution['total'] += $item->count;
                }

                return $distribution;
            })
            ->values();

        return [
            'trends' => $riskTrends,
            'period_months' => $months
        ];
    }

    /**
     * Get order status distribution
     */
    public function getOrderStatusDistribution(): array
    {
        $distribution = CustomerOrderHistory::select('current_status', DB::raw('count(*) as count'))
            ->groupBy('current_status')
            ->orderByDesc('count')
            ->get()
            ->mapWithKeys(fn($item) => [$item->current_status => $item->count]);

        $total = CustomerOrderHistory::count();

        return [
            'distribution' => $distribution,
            'total_orders' => $total,
            'percentages' => $distribution->map(function ($count) use ($total) {
                return $total > 0 ? round(($count / $total) * 100, 2) : 0;
            })
        ];
    }

    /**
     * Get recycling pool priority distribution
     */
    public function getPriorityDistribution(): array
    {
        $distribution = LeadRecyclingPool::where('pool_status', LeadRecyclingPool::STATUS_AVAILABLE)
            ->select(
                DB::raw('CASE
                    WHEN priority_score >= 70 THEN "High (70-100)"
                    WHEN priority_score >= 40 THEN "Medium (40-69)"
                    ELSE "Low (0-39)"
                END as priority_range'),
                DB::raw('count(*) as count')
            )
            ->groupBy('priority_range')
            ->get();

        return [
            'distribution' => $distribution->pluck('count', 'priority_range'),
            'total_available' => LeadRecyclingPool::where('pool_status', LeadRecyclingPool::STATUS_AVAILABLE)->count()
        ];
    }

    /**
     * Get comprehensive dashboard statistics
     */
    public function getDashboardStats(): array
    {
        return [
            // Customer Overview
            'customers' => [
                'total' => Customer::count(),
                'with_orders' => Customer::where('total_orders', '>', 0)->count(),
                'avg_score' => round(Customer::avg('customer_score'), 2),
                'blacklisted' => Customer::where('risk_level', Customer::RISK_BLACKLIST)->count(),
            ],

            // Order Metrics
            'orders' => [
                'total' => CustomerOrderHistory::count(),
                'delivered' => CustomerOrderHistory::where('current_status', CustomerOrderHistory::STATUS_DELIVERED)->count(),
                'returned' => CustomerOrderHistory::where('current_status', CustomerOrderHistory::STATUS_RETURNED)->count(),
                'pending' => CustomerOrderHistory::where('current_status', CustomerOrderHistory::STATUS_PENDING)->count(),
                'total_revenue' => round(Customer::sum('total_delivered_value'), 2),
            ],

            // Recycling Pool
            'recycling_pool' => [
                'available' => LeadRecyclingPool::where('pool_status', LeadRecyclingPool::STATUS_AVAILABLE)->count(),
                'assigned' => LeadRecyclingPool::where('pool_status', LeadRecyclingPool::STATUS_ASSIGNED)->count(),
                'converted' => LeadRecyclingPool::where('pool_status', LeadRecyclingPool::STATUS_CONVERTED)->count(),
                'conversion_rate' => $this->getRecyclingConversionRate(),
            ],

            // Lead Metrics
            'leads' => [
                'total' => Lead::count(),
                'recycled' => Lead::where('source', 'recycled')->count(),
                'active_cycles' => LeadCycle::where('status', LeadCycle::STATUS_ACTIVE)->count(),
            ]
        ];
    }

    /**
     * Helper: Get overall recycling conversion rate
     */
    private function getRecyclingConversionRate(): float
    {
        $total = LeadRecyclingPool::count();
        if ($total === 0) return 0;

        $converted = LeadRecyclingPool::where('pool_status', LeadRecyclingPool::STATUS_CONVERTED)->count();
        return round(($converted / $total) * 100, 2);
    }
}
