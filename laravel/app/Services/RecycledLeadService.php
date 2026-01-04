<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadRecyclingPool;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecycledLeadService
{
    public function __construct(
        private LeadService $leadService,
        private LeadCycleService $leadCycleService
    ) {}

    /**
     * Convert a recycling pool entry to an active lead
     * This is called when an agent successfully converts a recycled lead to a sale
     */
    public function convertPoolEntryToLead(
        LeadRecyclingPool $poolEntry,
        User $agent,
        array $leadData = []
    ): Lead {
        $customer = $poolEntry->customer;

        return DB::transaction(function () use ($poolEntry, $customer, $agent, $leadData) {
            // Create or reactivate lead
            $lead = $this->createLeadFromCustomer($customer, $agent, $leadData);

            // Open a new lead cycle for this recycled lead
            $cycle = $this->leadCycleService->openCycle($lead, $agent);

            Log::info("Converted recycling pool entry to active lead", [
                'pool_id' => $poolEntry->id,
                'lead_id' => $lead->id,
                'cycle_id' => $cycle->id,
                'customer_id' => $customer->id,
                'agent_id' => $agent->id
            ]);

            return $lead;
        });
    }

    /**
     * Create a new lead from customer data
     * Or reactivate an existing lead if one exists
     */
    private function createLeadFromCustomer(Customer $customer, User $agent, array $additionalData = []): Lead
    {
        // Check if there's an existing lead for this customer that can be reactivated
        $existingLead = Lead::where('customer_id', $customer->id)
            ->whereIn('status', [Lead::STATUS_RETURNED, Lead::STATUS_CANCELLED, Lead::STATUS_ARCHIVED])
            ->first();

        if ($existingLead) {
            // Reactivate existing lead
            $existingLead->update([
                'status' => Lead::STATUS_NEW,
                'source' => 'recycled',
                'assigned_to' => $agent->id,
                'assigned_at' => now(),
                'recycling_pool_id' => $additionalData['recycling_pool_id'] ?? null,
                'product_name' => $additionalData['product_name'] ?? $existingLead->product_name,
                'amount' => $additionalData['amount'] ?? $existingLead->amount,
            ]);

            Log::info("Reactivated existing lead from recycling pool", [
                'lead_id' => $existingLead->id,
                'customer_id' => $customer->id
            ]);

            return $existingLead;
        }

        // Create new lead from customer data
        $leadData = [
            'customer_id' => $customer->id,
            'recycling_pool_id' => $additionalData['recycling_pool_id'] ?? null,
            'name' => $customer->name_display,
            'phone' => $customer->phone_primary,
            'address' => $customer->primary_address,
            'city' => $customer->city,
            'state' => $customer->province,
            'barangay' => $customer->barangay,
            'street' => $customer->street,
            'status' => Lead::STATUS_NEW,
            'source' => 'recycled',
            'assigned_to' => $agent->id,
            'assigned_at' => now(),
            'uploaded_by' => null, // System-generated
            'product_name' => $additionalData['product_name'] ?? null,
            'amount' => $additionalData['amount'] ?? null,
        ];

        $lead = Lead::create($leadData);

        Log::info("Created new lead from recycling pool", [
            'lead_id' => $lead->id,
            'customer_id' => $customer->id
        ]);

        return $lead;
    }

    /**
     * Process a successful sale from recycling pool
     * Creates lead, opens cycle, and marks as SALE
     */
    public function processSaleFromPool(
        LeadRecyclingPool $poolEntry,
        User $agent,
        array $saleData
    ): array {
        try {
            return DB::transaction(function () use ($poolEntry, $agent, $saleData) {
                // Create lead from pool entry
                $lead = $this->convertPoolEntryToLead($poolEntry, $agent, [
                    'recycling_pool_id' => $poolEntry->id,
                    'product_name' => $saleData['product_name'] ?? null,
                    'amount' => $saleData['amount'] ?? null,
                ]);

                // Update lead status to SALE
                $this->leadService->updateStatus(
                    $lead,
                    Lead::STATUS_SALE,
                    $saleData['note'] ?? 'Converted from recycling pool',
                    $agent,
                    [
                        'product_name' => $saleData['product_name'] ?? $lead->product_name,
                        'product_brand' => $saleData['product_brand'] ?? null,
                        'amount' => $saleData['amount'] ?? $lead->amount,
                    ]
                );

                Log::info("Processed sale from recycling pool", [
                    'pool_id' => $poolEntry->id,
                    'lead_id' => $lead->id,
                    'amount' => $saleData['amount'] ?? 0
                ]);

                return [
                    'success' => true,
                    'lead_id' => $lead->id,
                    'message' => 'Sale processed successfully'
                ];
            });
        } catch (\Exception $e) {
            Log::error("Failed to process sale from recycling pool", [
                'pool_id' => $poolEntry->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle NO_ANSWER outcome from recycling pool
     * Records the call attempt on the customer
     */
    public function handleNoAnswer(
        LeadRecyclingPool $poolEntry,
        User $agent
    ): void {
        $customer = $poolEntry->customer;

        // Check if there's an active lead for this customer
        $existingLead = Lead::where('customer_id', $customer->id)
            ->where('status', Lead::STATUS_CALLING)
            ->where('assigned_to', $agent->id)
            ->first();

        if ($existingLead) {
            // Record call attempt on existing lead
            $this->leadCycleService->recordCall(
                $existingLead,
                $agent,
                'No answer - from recycling pool'
            );
        }

        // Always record contact on customer
        $customer->recordContact();

        Log::info("Recorded no answer for recycling pool entry", [
            'pool_id' => $poolEntry->id,
            'customer_id' => $customer->id,
            'had_active_lead' => $existingLead !== null
        ]);
    }

    /**
     * Get recycling pool statistics for reporting
     */
    public function getConversionStats(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $query = LeadRecyclingPool::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $total = $query->count();
        $converted = (clone $query)->where('pool_status', LeadRecyclingPool::STATUS_CONVERTED)->count();
        $exhausted = (clone $query)->where('pool_status', LeadRecyclingPool::STATUS_EXHAUSTED)->count();
        $expired = (clone $query)->where('pool_status', LeadRecyclingPool::STATUS_EXPIRED)->count();
        $stillActive = (clone $query)->whereIn('pool_status', [
            LeadRecyclingPool::STATUS_AVAILABLE,
            LeadRecyclingPool::STATUS_ASSIGNED
        ])->count();

        $conversionRate = $total > 0 ? ($converted / $total) * 100 : 0;

        return [
            'total' => $total,
            'converted' => $converted,
            'exhausted' => $exhausted,
            'expired' => $expired,
            'active' => $stillActive,
            'conversion_rate' => round($conversionRate, 2),
            'by_reason' => LeadRecyclingPool::query()
                ->select('recycle_reason', DB::raw('count(*) as count'))
                ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('created_at', '<=', $endDate))
                ->groupBy('recycle_reason')
                ->pluck('count', 'recycle_reason')
                ->toArray(),
        ];
    }

    /**
     * Get top performing agents in recycling pool conversions
     */
    public function getAgentPerformance(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $query = LeadRecyclingPool::query()
            ->where('pool_status', LeadRecyclingPool::STATUS_CONVERTED)
            ->whereNotNull('assigned_to');

        if ($startDate) {
            $query->where('processed_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('processed_at', '<=', $endDate);
        }

        return $query
            ->select('assigned_to', DB::raw('count(*) as conversions'))
            ->groupBy('assigned_to')
            ->orderByDesc('conversions')
            ->with('assignedAgent:id,name')
            ->get()
            ->map(function ($record) {
                return [
                    'agent_id' => $record->assigned_to,
                    'agent_name' => $record->assignedAgent?->name ?? 'Unknown',
                    'conversions' => $record->conversions
                ];
            })
            ->toArray();
    }
}
