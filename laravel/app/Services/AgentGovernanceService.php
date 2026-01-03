<?php

namespace App\Services;

use App\Models\AgentFlag;
use App\Models\AgentProfile;
use App\Models\LeadCycle;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgentGovernanceService
{
    // Thresholds
    const THRESHOLD_RECYCLE_ABUSE_RATE = 20.0; // Flag if > 20% cycles are fast-recycled
    const THRESHOLD_TIME_TO_CALL_VARIANCE = 1.5; // Flag if > 1.5x team average
    const FAST_RECYCLE_MINUTES = 5;

    /**
     * Update metrics for a specific agent.
     */
    public function updateAgentMetrics(User $agent): void
    {
        $profile = $agent->getOrCreateProfile();
        
        // Time window: Last 30 days
        $cycles = LeadCycle::where('agent_id', $agent->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->with(['calls', 'lead'])
            ->get();

        if ($cycles->isEmpty()) {
            return;
        }

        // 1. Calculate Average Time to First Call
        $totalTime = 0;
        $countTime = 0;
        foreach ($cycles as $cycle) {
            $firstCallTime = null;
            if (!empty($cycle->notes) && is_array($cycle->notes)) {
                foreach ($cycle->notes as $note) {
                    if (isset($note['type']) && $note['type'] === 'call') {
                        $firstCallTime = \Carbon\Carbon::parse($note['timestamp']);
                        break;
                    }
                }
            }

            if ($firstCallTime) {
                $timeDiff = $cycle->created_at->diffInSeconds($firstCallTime);
                $totalTime += $timeDiff;
                $countTime++;
            }
        }
        $profile->avg_time_to_first_call = $countTime > 0 ? (int)($totalTime / $countTime) : null;

        // 2. Calculate Recycle Abuse Rate
        // Definition: Cycles closed as REJECT within 5 mins with NO calls
        $abuseCount = 0;
        $recycleCount = 0;
        foreach ($cycles as $cycle) {
            if ($cycle->status === LeadCycle::STATUS_CLOSED_REJECT) {
                $recycleCount++;
                $duration = $cycle->created_at->diffInMinutes($cycle->closed_at);
                if ($duration < self::FAST_RECYCLE_MINUTES && $cycle->call_attempts === 0) {
                    $abuseCount++;
                }
            }
        }
        $profile->recycle_abuse_rate = $recycleCount > 0 ? round(($abuseCount / $recycleCount) * 100, 1) : 0;

        // 3. Fresh Lead Conversion Rate
        $freshCycles = $cycles->filter(function ($cycle) {
            return $cycle->lead && ($cycle->lead->total_cycles === 0 || $cycle->lead->total_cycles === 1);
        });
        $freshConversions = $freshCycles->where('status', LeadCycle::STATUS_CLOSED_SALE)->count();
        $profile->fresh_conversion_rate = $freshCycles->count() > 0 
            ? round(($freshConversions / $freshCycles->count()) * 100, 1) 
            : 0;

        $profile->save();
    }

    /**
     * Analyze behavior for all agents and generate flags.
     */
    public function analyzeAllAgents(): array
    {
        $agents = User::where('role', User::ROLE_AGENT)->where('is_active', true)->get();
        $flagsGenerated = 0;

        // Calculate Team Averages
        $profiles = AgentProfile::whereIn('user_id', $agents->pluck('id'))->get();
        if ($profiles->isEmpty()) return ['processed' => 0, 'flags' => 0];

        $teamAvgTime = $profiles->avg('avg_time_to_first_call'); // seconds
        $teamAbuseRate = $profiles->avg('recycle_abuse_rate'); // percentage

        foreach ($agents as $agent) {
            $this->updateAgentMetrics($agent); // Ensure fresh data
            $agent->refresh();
            $profile = $agent->profile;

            if (!$profile) continue;

            // Check: Recycle Abuse
            if ($profile->recycle_abuse_rate > self::THRESHOLD_RECYCLE_ABUSE_RATE) {
                $this->flag(
                    $agent, 
                    AgentFlag::TYPE_RECYCLE_ABUSE, 
                    AgentFlag::SEVERITY_WARNING,
                    "{$profile->recycle_abuse_rate}%",
                    round($teamAbuseRate, 1) . "%",
                    "High rate of quick recycles without calls."
                );
                $flagsGenerated++;
            }

            // Check: Slow Contact
            // Only flag if strictly slower than team average by threshold factor
            if ($projectTime = $profile->avg_time_to_first_call) {
                if ($teamAvgTime > 0 && $projectTime > ($teamAvgTime * self::THRESHOLD_TIME_TO_CALL_VARIANCE)) {
                    $this->flag(
                        $agent,
                        AgentFlag::TYPE_SLOW_CONTACT,
                        AgentFlag::SEVERITY_INFO,
                        gmdate("H:i:s", $projectTime),
                        gmdate("H:i:s", $teamAvgTime),
                        "Taking significantly longer to contact leads than team average."
                    );
                    $flagsGenerated++;
                }
            }
        }

        return ['processed' => $agents->count(), 'flags' => $flagsGenerated];
    }

    /**
     * Create or update a flag.
     */
    protected function flag(User $agent, string $type, string $severity, string $value, string $teamAvg, string $details): void
    {
        // Prevent duplicate active flags of same type today
        $exists = AgentFlag::where('user_id', $agent->id)
            ->where('type', $type)
            ->where('is_resolved', false)
            ->where('created_at', '>=', now()->startOfDay())
            ->exists();

        if (!$exists) {
            AgentFlag::create([
                'user_id' => $agent->id,
                'type' => $type,
                'severity' => $severity,
                'metric_value' => $value,
                'team_average' => $teamAvg,
                'details' => ['message' => $details]
            ]);
            
            Log::warning("Agent Flag Raised: {$type} for agent {$agent->id}");
        }
    }
}
