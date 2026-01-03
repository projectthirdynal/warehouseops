<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadCycle;
use App\Models\User;
use App\Models\Waybill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadCycleService
{
    /**
     * Open a new cycle for a lead.
     * Validates recycling rules and creates a new LeadCycle record.
     *
     * @throws \Exception if lead cannot be recycled
     */
    public function openCycle(Lead $lead, User $agent): LeadCycle
    {
        // Validate recycling rules
        $canRecycle = $lead->canRecycle();
        if ($canRecycle !== true) {
            throw new \Exception($canRecycle);
        }

        return DB::transaction(function () use ($lead, $agent) {
            // Close any existing active cycle first (defensive)
            $this->closeActiveCycles($lead, LeadCycle::STATUS_CLOSED_RETURNED);

            // Increment cycle count on lead
            $lead->incrementCycleCount();

            // Create new cycle
            $cycleNumber = $lead->total_cycles;
            
            $cycle = LeadCycle::create([
                'lead_id' => $lead->id,
                'agent_id' => $agent->id,
                'cycle_number' => $cycleNumber,
                'status' => LeadCycle::STATUS_ACTIVE,
                'opened_at' => now(),
                'call_attempts' => 0,
                'notes' => []
            ]);

            // Update lead assignment
            $lead->assigned_to = $agent->id;
            $lead->assigned_at = now();
            $lead->save();

            // Log the cycle opening
            $cycle->addNote("Cycle #{$cycleNumber} opened. Assigned to {$agent->name}.", $agent, 'assignment');

            Log::info("Lead Cycle Opened", [
                'lead_id' => $lead->id,
                'cycle_id' => $cycle->id,
                'cycle_number' => $cycleNumber,
                'agent_id' => $agent->id
            ]);

            return $cycle;
        });
    }

    /**
     * Close a cycle with a specific outcome.
     */
    public function closeCycle(LeadCycle $cycle, string $outcome, ?User $actor = null, ?string $reason = null): void
    {
        if (!$cycle->isActive()) {
            throw new \Exception("Cycle is already closed.");
        }

        DB::transaction(function () use ($cycle, $outcome, $actor, $reason) {
            $cycle->close($outcome);

            if ($actor && $reason) {
                $cycle->addNote("Cycle closed: {$reason}", $actor, 'status_change');
            }

            // Update lead status based on outcome
            $lead = $cycle->lead;
            
            switch ($outcome) {
                case LeadCycle::STATUS_CLOSED_SALE:
                    $lead->status = Lead::STATUS_SALE;
                    break;
                case LeadCycle::STATUS_CLOSED_REJECT:
                    $lead->status = Lead::STATUS_REJECT;
                    break;
                case LeadCycle::STATUS_CLOSED_RETURNED:
                    $lead->status = Lead::STATUS_NEW; // Ready for next agent
                    $lead->assigned_to = null;
                    break;
                case LeadCycle::STATUS_CLOSED_EXHAUSTED:
                    $lead->is_exhausted = true;
                    $lead->status = Lead::STATUS_CANCELLED;
                    break;
            }
            
            $lead->save();

            Log::info("Lead Cycle Closed", [
                'lead_id' => $lead->id,
                'cycle_id' => $cycle->id,
                'outcome' => $outcome
            ]);
        });
    }

    /**
     * Close all active cycles for a lead.
     */
    public function closeActiveCycles(Lead $lead, string $outcome): int
    {
        $count = 0;
        
        $activeCycles = $lead->cycles()->where('status', LeadCycle::STATUS_ACTIVE)->get();
        
        foreach ($activeCycles as $cycle) {
            $cycle->close($outcome);
            $count++;
        }

        return $count;
    }

    /**
     * Record a call attempt on the active cycle.
     */
    public function recordCall(Lead $lead, User $agent, ?string $note = null): void
    {
        $cycle = $lead->activeCycle;
        
        if (!$cycle) {
            // Auto-open a cycle if none exists
            $cycle = $this->openCycle($lead, $agent);
        }

        $cycle->recordCall();

        if ($note) {
            $cycle->addNote($note, $agent, 'call');
        }

        // Also update lead-level call tracking for backwards compatibility
        $lead->call_attempts++;
        $lead->last_called_at = now();
        $lead->save();
    }

    /**
     * Add a note to the active cycle.
     */
    public function addNote(Lead $lead, string $content, User $author, string $type = 'note'): void
    {
        $cycle = $lead->activeCycle;
        
        if ($cycle) {
            $cycle->addNote($content, $author, $type);
        }

        // Also update legacy notes field for backwards compatibility
        $date = now()->format('Y-m-d H:i');
        $newNoteEntry = "[{$date} {$author->name}]: {$content}";
        $lead->notes = $lead->notes ? $lead->notes . "\n" . $newNoteEntry : $newNoteEntry;
        $lead->save();
    }

    /**
     * Bind a waybill to the current cycle.
     */
    public function bindWaybill(Lead $lead, Waybill $waybill): void
    {
        $cycle = $lead->activeCycle;
        
        if ($cycle) {
            $cycle->waybill_id = $waybill->id;
            $cycle->save();
        }

        // Also update waybill with lead reference
        $waybill->lead_id = $lead->id;
        $waybill->save();
    }

    /**
     * Get recycling validation status for a lead.
     */
    public function validateRecycling(Lead $lead): array
    {
        $result = $lead->canRecycle();
        
        return [
            'can_recycle' => $result === true,
            'reason' => $result === true ? null : $result,
            'total_cycles' => $lead->total_cycles,
            'max_cycles' => $lead->max_cycles,
            'remaining_cycles' => max(0, $lead->max_cycles - $lead->total_cycles),
            'is_exhausted' => $lead->is_exhausted,
            'has_active_waybill' => $lead->waybills()->whereNotIn('status', ['DELIVERED', 'CANCELLED', 'RETURNED'])->exists()
        ];
    }

    /**
     * Bulk open cycles for distribution.
     */
    public function distributeLeads(array $leadIds, User $agent, User $assigner): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($leadIds as $leadId) {
            try {
                $lead = Lead::findOrFail($leadId);
                $this->openCycle($lead, $agent);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'lead_id' => $leadId,
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info("Lead Distribution Completed", [
            'agent_id' => $agent->id,
            'assigner_id' => $assigner->id,
            'success' => $results['success'],
            'failed' => $results['failed']
        ]);

        return $results;
    }
}
