<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadCycle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadScoringService
{
    const DECAY_PER_RECYCLE = 15;
    const DECAY_PER_DAY_IDLE = 2;
    const ARCHIVE_THRESHOLD = 20;

    protected LeadStateMachine $stateMachine;

    public function __construct(LeadStateMachine $stateMachine)
    {
        $this->stateMachine = $stateMachine;
    }

    /**
     * Calculate quality score for a lead.
     * Score = 100 - (Recycle Count * 15) - (Days Idle * 2)
     */
    public function calculateScore(Lead $lead): int
    {
        $score = 100;

        // Penalty for recycling
        $score -= ($lead->total_cycles * self::DECAY_PER_RECYCLE);

        // Penalty for idleness
        // Use last activity date (assigned_at, last_called_at, or updated_at)
        $lastActivity = $lead->last_called_at 
            ?? $lead->assigned_at 
            ?? $lead->updated_at 
            ?? $lead->created_at;

        if ($lastActivity) {
            $daysIdle = $lastActivity->diffInDays(now());
            $score -= ($daysIdle * self::DECAY_PER_DAY_IDLE);
        }

        // Ensure score stays within 0-100
        return max(0, min(100, $score));
    }

    /**
     * Update score for a specific lead and archive if necessary.
     */
    public function updateScore(Lead $lead): void
    {
        $oldScore = $lead->quality_score;
        $newScore = $this->calculateScore($lead);

        $lead->quality_score = $newScore;
        $lead->last_scored_at = now();

        // Check for archiving threshold
        if ($newScore < self::ARCHIVE_THRESHOLD) {
            $this->archiveLead($lead);
        }

        $lead->save();
    }

    /**
     * Archive a lead due to low quality.
     */
    public function archiveLead(Lead $lead): void
    {
        // Only archive if not already archived, locked, or active
        if ($lead->status === Lead::STATUS_ARCHIVED) return;
        if ($lead->isLocked()) return; // Don't archive active sales
        if ($lead->activeCycle) return; // Don't archive leads currently being worked

        // Transitions checks handled by StateMachine, but archiving is a system action
        // We'll bypass strict transition validation here since it's a decay process
        // provided the lead isn't in a protected state (SALE, DELIVERED)
        
        $lead->status = Lead::STATUS_ARCHIVED;
        
        Log::info("Lead Archived (Low Score)", [
            'lead_id' => $lead->id,
            'score' => $lead->quality_score
        ]);
    }

    /**
     * Batch process decay for all applicable leads.
     */
    public function processDecay(): array
    {
        $processed = 0;
        $archived = 0;

        // Get leads that haven't been scored in 24 hours
        // Exclude finalized leads
        $leads = Lead::where(function($q) {
                $q->whereNull('last_scored_at')
                  ->orWhere('last_scored_at', '<', now()->subDay());
            })
            ->whereNotIn('status', [
                Lead::STATUS_SALE, 
                Lead::STATUS_DELIVERED, 
                Lead::STATUS_CANCELLED, 
                Lead::STATUS_ARCHIVED
            ])
            ->chunkById(100, function ($chunk) use (&$processed, &$archived) {
                foreach ($chunk as $lead) {
                    $this->updateScore($lead);
                    $processed++;
                    if ($lead->status === Lead::STATUS_ARCHIVED) {
                        $archived++;
                    }
                }
            });

        Log::info("Lead Decay Processed", [
            'processed' => $processed,
            'archived' => $archived
        ]);

        return [
            'processed' => $processed,
            'archived' => $archived
        ];
    }
}
