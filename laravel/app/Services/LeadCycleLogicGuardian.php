<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadCycle;
use App\Models\AgentFlag;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class LeadCycleLogicGuardian
{
    const MAX_GLOBAL_CYCLES = 15;
    const MIN_HOURS_BETWEEN_RECYCLES = 4;
    const MAX_DAYS_IN_CALLING = 7;
    const MAX_OPEN_CYCLES_PER_AGENT = 50; // Safety cap

    /**
     * Determine if a lead is allowed to be recycled.
     * This is the "Constitution" check.
     */
    public function canRecycle(Lead $lead): bool|string
    {
        // 1. Hard Cap Check
        if ($lead->total_cycles >= self::MAX_GLOBAL_CYCLES) {
            return "Lead has reached maximum global recycle limit (" . self::MAX_GLOBAL_CYCLES . ").";
        }

        // 2. Cooldown Check
        // If it was recycled very recently (e.g. accidentally), prevent spamming
        // Check the *previous* cycle's close time if exists
        $lastCycle = $lead->leadCycles()
            ->where('status', '!=', LeadCycle::STATUS_ACTIVE)
            ->latest('closed_at')
            ->first();

        if ($lastCycle && $lastCycle->closed_at && $lastCycle->closed_at->diffInHours(now()) < self::MIN_HOURS_BETWEEN_RECYCLES) {
            // Check if it was just assigned today to prevent "Assign -> Reject -> Assign -> Reject" loops
            // Exception: If no calls were made, maybe it's just a mistake, but we handle that via "Abuse" flags.
            // Here we want to stop spam.
            return "Lead was recycled too recently. Minimum cooldown is " . self::MIN_HOURS_BETWEEN_RECYCLES . " hours.";
        }

        // 3. Quality Score Check (Redundant with Phase 10 but good as immediate gate)
        if ($lead->quality_score < 20) {
            return "Lead score is too low for recycling. It should be archived.";
        }

        return true;
    }

    /**
     * Pre-transition Audit.
     * Called before a status change is committed.
     */
    public function auditTransition(Lead $lead, string $newStatus, User $actor): void
    {
        // Only care about transitions that imply recycling/rejection
        if ($newStatus === Lead::STATUS_REJECT) {
            $result = $this->canRecycle($lead);
            if ($result !== true) {
                // If it's a hard "NO", we block the transition.
                // However, "REJECT" is often the agent saying "I give up".
                // If we block it, they can't work.
                // Solution: We allow the REJECT status, but the DistributionEngine
                // will respect the "Can Recycle" logic and might Archive it instead of distributing.
                
                // WAIT: If `canRecycle` returns false due to MAX_CYCLES, 
                // we should probably auto-archive it right here or let it go to REJECT (which is a dead end anyway until distributed).
                // The LogicGuardian protects the *Logic Path*.
                // If the path is "Reject -> Archive", that is valid.
                // If the path is "Reject -> New Agent", that is invalid.
                
                // So, we don't exception here. We allow the status change.
                // But we log distinct warnings if rules are being bent.
                Log::notice("Guardian: Lead {$lead->id} rejected but failed recycle check: {$result}");
            }
        }
    }

    /**
     * Nightly System Audit (Drift Detection).
     */
    public function auditSystem(): array
    {
        $driftDetected = 0;

        // 1. Stuck Leads in CALLING
        $stuckLeads = Lead::where('status', Lead::STATUS_CALLING)
            ->where('updated_at', '<', now()->subDays(self::MAX_DAYS_IN_CALLING))
            ->get();

        foreach ($stuckLeads as $lead) {
            // Flag system alert
            Log::warning("Guardian Audit: Lead {$lead->id} stuck in CALLING > 7 days.");
            $driftDetected++;
        }

        // 2. Open Cycles with No Activity
        // Find active cycles created > 24 hours ago with 0 calls
        $zombieCycles = LeadCycle::where('status', LeadCycle::STATUS_ACTIVE)
            ->where('created_at', '<', now()->subHours(24))
            ->where('call_attempts', 0)
            ->get();

        foreach ($zombieCycles as $cycle) {
            Log::warning("Guardian Audit: Cycle {$cycle->id} (Agent {$cycle->agent_id}) zombie > 24h.");
            $driftDetected++;
            
            // Generate System flag? Or Agent Flag?
            // This is system drift / agent negligence.
            // We'll leave it to logs for now, Phase 11 covers agent flags.
        }

        return ['drift_detected' => $driftDetected];
    }
}
