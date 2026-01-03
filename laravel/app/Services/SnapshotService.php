<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadSnapshot;
use App\Models\LeadCycle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SnapshotService
{
    /**
     * Capture an immutable snapshot of the lead's current state.
     */
    public function capture(Lead $lead, string $reason): LeadSnapshot
    {
        // Eager load critical context
        $lead->load(['activeCycle', 'waybills']);

        $data = $lead->toArray();
        
        // Ensure we have the cycle data even if not loaded via relationship (defensive)
        if (!isset($data['active_cycle']) && $lead->activeCycle) {
            $data['active_cycle'] = $lead->activeCycle->toArray();
        }

        return LeadSnapshot::create([
            'lead_id' => $lead->id,
            'reason' => $reason,
            'snapshot_data' => $data,
            'created_at' => now()
        ]);
    }

    /**
     * Restore a lead to a previous snapshot state.
     * WARNING: This effectively overwrites the current lead state.
     */
    public function restore(int $snapshotId): bool
    {
        $snapshot = LeadSnapshot::findOrFail($snapshotId);
        $lead = Lead::findOrFail($snapshot->lead_id);
        $data = $snapshot->snapshot_data;

        Log::warning("Restoring Lead {$lead->id} to Snapshot {$snapshot->id} (Reason: {$snapshot->reason})");

        return DB::transaction(function () use ($lead, $data, $snapshot) {
            // 1. Snapshot CURRENT state before overwriting (Safety net)
            $this->capture($lead, "pre_restore_snapshot_{$snapshot->id}");

            // 2. Restore Lead Attributes
            // Exclude relations from the upgrade
            $attributes = collect($data)->except(['active_cycle', 'waybills', 'lead_cycles', 'logs'])->toArray();
            $lead->forceFill($attributes);
            $lead->save();

            // 3. Restore Active Cycle (Best Effort)
            if (isset($data['active_cycle'])) {
                $cycleData = $data['active_cycle'];
                $cycle = LeadCycle::find($cycleData['id']);
                
                if ($cycle) {
                    // Update existing cycle to match snapshot
                    $cycle->forceFill($cycleData);
                    $cycle->save();
                    Log::info("Restored LeadCycle {$cycle->id} attributes.");
                } else {
                    Log::warning("Could not restore LeadCycle {$cycleData['id']} - Record not found. It may have been hard-deleted.");
                }
            }

            Log::info("Lead {$lead->id} restoration complete.");
            return true;
        });
    }
}
