<?php

namespace App\Services;

use App\Models\AgentProfile;
use App\Models\Lead;
use App\Models\LeadCycle;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DistributionEngine
{
    // Scoring weights
    const BASE_SCORE = 100;
    const SKILL_MATCH_BONUS = 20;
    const REGION_MATCH_BONUS = 15;
    const CAPACITY_PENALTY_PER_PERCENT = 0.5; // -0.5 per % load
    const RECYCLE_DECAY_PER_CYCLE = 5; // -5 per previous cycle
    const RECYCLE_DECAY_PER_DAY = 2; // -2 per day since last cycle closed

    /**
     * Find the best agent for a specific lead.
     */
    public function findBestAgent(Lead $lead): ?User
    {
        $availableAgents = $this->getAvailableAgents();

        if ($availableAgents->isEmpty()) {
            Log::warning("DistributionEngine: No available agents found");
            return null;
        }

        $scores = [];

        foreach ($availableAgents as $agent) {
            $scores[$agent->id] = [
                'agent' => $agent,
                'score' => $this->calculateAgentScore($agent, $lead)
            ];
        }

        // Sort by score descending
        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

        // Return the highest scoring agent
        return $scores[0]['agent'] ?? null;
    }

    /**
     * Calculate a score for how well an agent matches a lead.
     */
    public function calculateAgentScore(User $agent, Lead $lead): float
    {
        $score = self::BASE_SCORE;
        $profile = $agent->profile;

        // 1. Capacity modifier (penalize agents with high load)
        if ($profile) {
            $loadPercent = $profile->getLoadPercentage();
            $score -= ($loadPercent * self::CAPACITY_PENALTY_PER_PERCENT);
        }

        // 2. Skill match bonus
        if ($profile && $profile->hasSkillFor($lead->product_name)) {
            $score += self::SKILL_MATCH_BONUS;
        }
        if ($profile && $profile->hasSkillFor($lead->product_brand)) {
            $score += self::SKILL_MATCH_BONUS / 2; // Half bonus for brand match
        }

        // 3. Region match bonus
        if ($profile && $profile->coversRegion($lead->state, $lead->city)) {
            $score += self::REGION_MATCH_BONUS;
        }

        // 4. Priority weight multiplier
        if ($profile) {
            $score *= $profile->priority_weight;
        }

        // 5. Recycle priority decay (for recycled leads)
        if ($lead->total_cycles > 0) {
            // Penalty for each previous cycle
            $score -= ($lead->total_cycles * self::RECYCLE_DECAY_PER_CYCLE);
            
            // Additional penalty based on age of last cycle
            $lastCycle = $lead->cycles()->whereNotNull('closed_at')->first();
            if ($lastCycle && $lastCycle->closed_at) {
                $daysSinceClose = $lastCycle->closed_at->diffInDays(now());
                $score -= ($daysSinceClose * self::RECYCLE_DECAY_PER_DAY);
            }
        }

        // Ensure minimum score of 0
        return max(0, $score);
    }

    /**
     * Get all agents available for distribution.
     */
    public function getAvailableAgents(): Collection
    {
        return User::where('role', User::ROLE_AGENT)
            ->where('is_active', true)
            ->with('profile')
            ->get()
            ->filter(fn(User $agent) => $agent->isAvailableForDistribution());
    }

    /**
     * Smart distribute a batch of leads to agents.
     * Returns array of results.
     */
    public function distributeLeads(Collection $leads, ?User $assigner = null): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'assignments' => [],
            'errors' => []
        ];

        $cycleService = app(LeadCycleService::class);

        foreach ($leads as $lead) {
            try {
                // Find best agent
                $bestAgent = $this->findBestAgent($lead);

                if (!$bestAgent) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'lead_id' => $lead->id,
                        'error' => 'No available agent found'
                    ];
                    continue;
                }

                // Open cycle for this lead
                $cycle = $cycleService->openCycle($lead, $bestAgent);

                $results['success']++;
                $results['assignments'][] = [
                    'lead_id' => $lead->id,
                    'agent_id' => $bestAgent->id,
                    'agent_name' => $bestAgent->name,
                    'cycle_id' => $cycle->id,
                    'score' => $this->calculateAgentScore($bestAgent, $lead)
                ];

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'lead_id' => $lead->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info("DistributionEngine: Distributed {$results['success']} leads, {$results['failed']} failed", [
            'assigner_id' => $assigner?->id
        ]);

        return $results;
    }

    /**
     * Distribute a specific count of leads matching criteria.
     */
    public function smartDistribute(array $criteria, ?User $assigner = null): array
    {
        $query = Lead::query();

        // Apply filters
        if (isset($criteria['status'])) {
            if ($criteria['status'] === 'REORDER') {
                $query->where('source', 'reorder')->where('status', Lead::STATUS_NEW);
            } elseif ($criteria['status'] === 'NEW') {
                $query->where('status', Lead::STATUS_NEW)
                    ->where(fn($q) => $q->whereNull('source')->orWhere('source', 'fresh'));
            } else {
                $query->where('status', $criteria['status']);
            }
        } else {
            $query->where('status', Lead::STATUS_NEW);
        }

        // Only unassigned or recyclable leads
        if (!empty($criteria['recycle'])) {
            $query->where(function($q) {
                $q->whereNull('assigned_to')
                  ->orWhere('is_exhausted', false);
            });
            // Exclude leads with active cycles
            $query->whereDoesntHave('cycles', fn($q) => $q->where('status', LeadCycle::STATUS_ACTIVE));
        } else {
            $query->whereNull('assigned_to');
        }

        // Exclude exhausted leads
        $query->where('is_exhausted', false);

        // Product filter
        if (!empty($criteria['previous_item'])) {
            $query->where('previous_item', $criteria['previous_item']);
        }

        // Order by priority (fresh first, then by total_cycles ascending, then by created_at)
        $query->orderBy('total_cycles', 'asc')
              ->orderBy('created_at', 'asc');

        // Limit
        $count = $criteria['count'] ?? 10;
        $leads = $query->limit($count)->get();

        if ($leads->isEmpty()) {
            return [
                'success' => 0,
                'failed' => 0,
                'message' => 'No leads found matching criteria',
                'errors' => []
            ];
        }

        return $this->distributeLeads($leads, $assigner);
    }

    /**
     * Get distribution statistics.
     */
    public function getDistributionStats(): array
    {
        $agents = $this->getAvailableAgents();
        
        $stats = [
            'available_agents' => $agents->count(),
            'total_capacity' => 0,
            'used_capacity' => 0,
            'agents' => []
        ];

        foreach ($agents as $agent) {
            $profile = $agent->profile ?? $agent->getOrCreateProfile();
            $activeCycles = $agent->getActiveCycleCount();
            
            $stats['total_capacity'] += $profile->max_active_cycles;
            $stats['used_capacity'] += $activeCycles;
            
            $stats['agents'][] = [
                'id' => $agent->id,
                'name' => $agent->name,
                'max_cycles' => $profile->max_active_cycles,
                'active_cycles' => $activeCycles,
                'remaining_capacity' => $profile->getRemainingCapacity(),
                'load_percent' => round($profile->getLoadPercentage(), 1),
                'skills' => $profile->product_skills ?? [],
                'regions' => $profile->regions ?? [],
                'priority_weight' => $profile->priority_weight
            ];
        }

        return $stats;
    }
}
