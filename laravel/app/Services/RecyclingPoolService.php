<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerOrderHistory;
use App\Models\Lead;
use App\Models\LeadRecyclingPool;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecyclingPoolService
{
    public function __construct(
        private ?RecycledLeadService $recycledLeadService = null
    ) {
        // Lazy load to avoid circular dependency
        $this->recycledLeadService = $recycledLeadService ?? app(RecycledLeadService::class);
    }
    /**
     * Evaluate if an order should enter the recycling pool
     * Based on the return reason or delivery failure
     */
    public function evaluateForRecycling(CustomerOrderHistory $order): ?LeadRecyclingPool
    {
        $customer = $order->customer;

        if (!$customer) {
            Log::warning("Cannot evaluate recycling: no customer linked to order", [
                'waybill' => $order->waybill_number
            ]);
            return null;
        }

        // Skip if customer is blacklisted
        if ($customer->isBlacklisted()) {
            Log::info("Skipping recycling: customer is blacklisted", [
                'customer_id' => $customer->id
            ]);
            return null;
        }

        // Skip if customer is in cooldown
        if ($customer->isInCooldown()) {
            Log::info("Skipping recycling: customer in cooldown", [
                'customer_id' => $customer->id,
                'cooldown_until' => $customer->recycling_cooldown_until
            ]);
            return null;
        }

        // Determine recycle reason and priority based on order status
        $recycleData = $this->determineRecycleStrategy($order, $customer);

        if (!$recycleData) {
            return null; // Order doesn't qualify for recycling
        }

        // Check if already in recycling pool (update existing entry)
        $existing = LeadRecyclingPool::where('customer_id', $customer->id)
            ->where('pool_status', LeadRecyclingPool::STATUS_AVAILABLE)
            ->first();

        if ($existing) {
            // Update existing entry
            $existing->update([
                'recycle_count' => $existing->recycle_count + 1,
                'priority_score' => min($recycleData['priority_score'], $existing->priority_score), // Lower if repeated
                'source_waybill' => $order->waybill_number,
                'original_outcome' => $order->current_status,
                'updated_at' => now()
            ]);

            Log::info("Updated existing recycling pool entry", [
                'customer_id' => $customer->id,
                'recycle_count' => $existing->recycle_count
            ]);

            return $existing;
        }

        // Create new recycling entry
        $poolEntry = LeadRecyclingPool::create([
            'customer_id' => $customer->id,
            'source_waybill' => $order->waybill_number,
            'source_lead_id' => $order->lead_id,
            'original_outcome' => $order->current_status,
            'recycle_reason' => $recycleData['reason'],
            'priority_score' => $recycleData['priority_score'],
            'available_from' => $recycleData['available_from'],
            'expires_at' => $recycleData['expires_at'],
            'pool_status' => LeadRecyclingPool::STATUS_AVAILABLE
        ]);

        Log::info("Added order to recycling pool", [
            'customer_id' => $customer->id,
            'reason' => $recycleData['reason'],
            'priority' => $recycleData['priority_score']
        ]);

        return $poolEntry;
    }

    /**
     * Determine recycling strategy based on order status and history
     */
    private function determineRecycleStrategy(CustomerOrderHistory $order, Customer $customer): ?array
    {
        $recycleReason = null;
        $priorityScore = 50; // Base priority
        $availableFrom = now();
        $expiresAt = now()->addDays(30);

        // Check order status
        if ($order->current_status === CustomerOrderHistory::STATUS_RETURNED) {
            // Analyze return reason from J&T data
            $returnReason = $order->jnt_raw_data['return_reason'] ?? 'UNKNOWN';

            if (in_array($returnReason, ['REFUSED', 'CANCELLED_BY_CUSTOMER'])) {
                // Customer actively refused - lower priority, longer cooldown
                $recycleReason = LeadRecyclingPool::REASON_RETURNED_REFUSED;
                $priorityScore = 20;
                $availableFrom = now()->addDays(14); // 2 week cooldown

            } elseif (in_array($returnReason, ['UNREACHABLE', 'WRONG_ADDRESS', 'NO_ONE_HOME'])) {
                // Delivery issue - higher priority, can retry sooner
                $recycleReason = LeadRecyclingPool::REASON_RETURNED_DELIVERABLE;
                $priorityScore = 70;
                $availableFrom = now()->addDays(3); // 3 day cooldown

            } else {
                // Other return reason
                $recycleReason = LeadRecyclingPool::REASON_RETURNED_OTHER;
                $priorityScore = 40;
                $availableFrom = now()->addDays(7);
            }

        } elseif ($order->current_status === 'FAILED_DELIVERY') {
            // Failed delivery attempt
            $recycleReason = LeadRecyclingPool::REASON_RETURNED_DELIVERABLE;
            $priorityScore = 65;
            $availableFrom = now()->addDays(2);

        } else {
            // Order doesn't qualify for recycling
            return null;
        }

        // Adjust priority based on customer score
        $scoreMultiplier = $customer->customer_score / 50; // 0.0 to 2.0
        $priorityScore = round($priorityScore * $scoreMultiplier);

        // Clamp priority to 1-100
        $priorityScore = max(1, min(100, $priorityScore));

        return [
            'reason' => $recycleReason,
            'priority_score' => $priorityScore,
            'available_from' => $availableFrom,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * Get available leads from the recycling pool
     */
    public function getAvailableLeads(int $count = 10, array $filters = []): Collection
    {
        $query = LeadRecyclingPool::query()
            ->with(['customer', 'sourceLead'])
            ->available()
            ->byPriority('desc');

        // Apply filters
        if (isset($filters['min_priority'])) {
            $query->where('priority_score', '>=', $filters['min_priority']);
        }

        if (isset($filters['recycle_reason'])) {
            $query->where('recycle_reason', $filters['recycle_reason']);
        }

        if (isset($filters['customer_score_min'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('customer_score', '>=', $filters['customer_score_min']);
            });
        }

        return $query->limit($count)->get();
    }

    /**
     * Assign leads from pool to an agent
     */
    public function assignToAgent(array $poolIds, User $agent): array
    {
        $assigned = 0;
        $errors = [];

        DB::transaction(function () use ($poolIds, $agent, &$assigned, &$errors) {
            foreach ($poolIds as $poolId) {
                $poolEntry = LeadRecyclingPool::find($poolId);

                if (!$poolEntry) {
                    $errors[] = "Pool entry not found: {$poolId}";
                    continue;
                }

                if (!$poolEntry->isAvailable()) {
                    $errors[] = "Pool entry not available: {$poolId}";
                    continue;
                }

                // Assign to agent
                $poolEntry->assignTo($agent);
                $assigned++;

                Log::info("Assigned recycling pool entry to agent", [
                    'pool_id' => $poolId,
                    'customer_id' => $poolEntry->customer_id,
                    'agent_id' => $agent->id
                ]);
            }
        });

        return [
            'assigned' => $assigned,
            'errors' => $errors,
            'total' => count($poolIds)
        ];
    }

    /**
     * Process outcome of a recycled lead
     */
    public function processOutcome(
        string $poolId,
        string $outcome,
        User $agent,
        ?array $saleData = null,
        ?string $notes = null,
        ?string $callbackDate = null
    ): array
    {
        $poolEntry = LeadRecyclingPool::find($poolId);

        if (!$poolEntry) {
            return ['success' => false, 'error' => 'Pool entry not found'];
        }

        $customer = $poolEntry->customer;

        // Update customer contact tracking
        $customer->recordContact();

        // Handle different outcomes
        switch ($outcome) {
            case 'SALE':
            case 'REORDER':
                // Process sale through RecycledLeadService (creates lead + cycle)
                $saleResult = $this->recycledLeadService->processSaleFromPool(
                    $poolEntry,
                    $agent,
                    array_merge($saleData ?? [], ['note' => $notes])
                );

                if (!$saleResult['success']) {
                    return $saleResult;
                }

                // Mark pool entry as converted
                $poolEntry->markAsConverted($outcome);

                // Set cooldown (they just ordered, don't contact again soon)
                $customer->setCooldown(30);

                Log::info("Recycled lead converted to sale", [
                    'pool_id' => $poolId,
                    'customer_id' => $customer->id,
                    'lead_id' => $saleResult['lead_id'],
                    'outcome' => $outcome
                ]);

                return [
                    'success' => true,
                    'action' => 'CONVERTED',
                    'message' => 'Lead successfully converted to sale',
                    'lead_id' => $saleResult['lead_id']
                ];

            case 'NO_ANSWER':
                // Record no answer via RecycledLeadService
                $this->recycledLeadService->handleNoAnswer($poolEntry, $agent);

                // Put back in pool with lower priority if not exhausted
                if ($poolEntry->recycle_count < 3) {
                    // Re-queue for retry
                    $newEntry = LeadRecyclingPool::create([
                        'customer_id' => $customer->id,
                        'source_waybill' => $poolEntry->source_waybill,
                        'source_lead_id' => $poolEntry->source_lead_id,
                        'original_outcome' => 'NO_ANSWER',
                        'recycle_reason' => LeadRecyclingPool::REASON_NO_ANSWER_RETRY,
                        'recycle_count' => $poolEntry->recycle_count + 1,
                        'priority_score' => max(10, $poolEntry->priority_score - 20), // Lower priority
                        'available_from' => now()->addDay(), // Try again tomorrow
                        'expires_at' => now()->addDays(14),
                        'pool_status' => LeadRecyclingPool::STATUS_AVAILABLE
                    ]);

                    $poolEntry->markAsExhausted('NO_ANSWER');

                    return [
                        'success' => true,
                        'action' => 'QUEUED_RETRY',
                        'message' => 'Re-queued for retry tomorrow',
                        'new_pool_id' => $newEntry->id
                    ];
                } else {
                    // Exhausted attempts
                    $poolEntry->markAsExhausted('NO_ANSWER_EXHAUSTED');

                    return [
                        'success' => false,
                        'action' => 'EXHAUSTED',
                        'message' => 'Maximum retry attempts reached'
                    ];
                }

            case 'DECLINED':
            case 'NOT_INTERESTED':
                // Set longer cooldown
                $customer->setCooldown(90);
                $poolEntry->markAsExhausted($outcome);

                return [
                    'success' => false,
                    'action' => 'COOLDOWN_90_DAYS',
                    'message' => 'Customer declined, 90-day cooldown set'
                ];

            case 'DO_NOT_CALL':
                // Blacklist customer
                $customer->blacklist();
                $poolEntry->markAsExhausted($outcome);

                return [
                    'success' => false,
                    'action' => 'BLACKLISTED',
                    'message' => 'Customer blacklisted and removed from pool'
                ];

            case 'CALLBACK':
                // Schedule callback
                if (!$callbackDate) {
                    return [
                        'success' => false,
                        'error' => 'Callback date required'
                    ];
                }

                $callbackDateTime = \Carbon\Carbon::parse($callbackDate);

                $newEntry = LeadRecyclingPool::create([
                    'customer_id' => $customer->id,
                    'source_waybill' => $poolEntry->source_waybill,
                    'source_lead_id' => $poolEntry->source_lead_id,
                    'original_outcome' => 'CALLBACK_REQUESTED',
                    'recycle_reason' => LeadRecyclingPool::REASON_SCHEDULED_CALLBACK,
                    'recycle_count' => $poolEntry->recycle_count,
                    'priority_score' => 90, // High priority for callbacks
                    'available_from' => $callbackDateTime,
                    'expires_at' => $callbackDateTime->copy()->addDays(2),
                    'pool_status' => LeadRecyclingPool::STATUS_AVAILABLE
                ]);

                $poolEntry->markAsExhausted('CALLBACK_SCHEDULED');

                return [
                    'success' => true,
                    'action' => 'CALLBACK_SCHEDULED',
                    'message' => 'Callback scheduled for ' . $callbackDateTime->format('Y-m-d H:i'),
                    'new_pool_id' => $newEntry->id
                ];

            default:
                return [
                    'success' => false,
                    'error' => 'Unknown outcome: ' . $outcome
                ];
        }
    }

    /**
     * Cleanup expired pool entries
     */
    public function cleanupExpired(): int
    {
        $count = LeadRecyclingPool::expired()->update([
            'pool_status' => LeadRecyclingPool::STATUS_EXPIRED,
            'processed_at' => now()
        ]);

        Log::info("Cleaned up expired recycling pool entries", ['count' => $count]);

        return $count;
    }

    /**
     * Release stale assignments (assigned but not processed for >24 hours)
     */
    public function releaseStaleAssignments(int $hours = 24): int
    {
        $staleEntries = LeadRecyclingPool::staleAssignments($hours)->get();

        foreach ($staleEntries as $entry) {
            $entry->releaseAssignment();
        }

        $count = $staleEntries->count();

        Log::warning("Released stale recycling pool assignments", [
            'count' => $count,
            'hours_threshold' => $hours
        ]);

        return $count;
    }

    /**
     * Get pool statistics
     */
    public function getPoolStats(): array
    {
        return [
            'total_available' => LeadRecyclingPool::where('pool_status', LeadRecyclingPool::STATUS_AVAILABLE)->count(),
            'total_assigned' => LeadRecyclingPool::where('pool_status', LeadRecyclingPool::STATUS_ASSIGNED)->count(),
            'total_converted' => LeadRecyclingPool::where('pool_status', LeadRecyclingPool::STATUS_CONVERTED)->count(),
            'total_exhausted' => LeadRecyclingPool::where('pool_status', LeadRecyclingPool::STATUS_EXHAUSTED)->count(),
            'total_expired' => LeadRecyclingPool::where('pool_status', LeadRecyclingPool::STATUS_EXPIRED)->count(),
            'by_reason' => LeadRecyclingPool::where('pool_status', LeadRecyclingPool::STATUS_AVAILABLE)
                ->select('recycle_reason', DB::raw('count(*) as count'))
                ->groupBy('recycle_reason')
                ->pluck('count', 'recycle_reason')
                ->toArray(),
            'avg_priority' => LeadRecyclingPool::where('pool_status', LeadRecyclingPool::STATUS_AVAILABLE)
                ->avg('priority_score'),
        ];
    }
}
