<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadService
{
    /**
     * Create a new lead
     */
    public function createLead(array $data, ?User $uploader = null): Lead
    {
        $data['uploaded_by'] = $uploader?->id;
        $data['status'] = 'NEW';
        
        return Lead::create($data);
    }

    /**
     * Update lead status and log the change
     */
    public function updateStatus(Lead $lead, string $newStatus, string $note = null, User $actor): void
    {
        $oldStatus = $lead->status;

        // If status didn't change and no note, do nothing
        if ($oldStatus === $newStatus && empty($note)) {
            return;
        }

        DB::transaction(function () use ($lead, $newStatus, $note, $oldStatus, $actor) {
            $lead->status = $newStatus;
            
            // Update metadata if it's a call-related status
            if (in_array($newStatus, ['NO_ANSWER', 'REJECT', 'CALLBACK', 'NOT_INTERESTED', 'SALE'])) {
                $lead->last_called_at = now();
                $lead->call_attempts++;
            }
            
            if ($note) {
                // Append note to main notes
                $date = now()->format('Y-m-d H:i');
                $lead->notes = $lead->notes . "\n[{$date} {$actor->name}]: {$note}";
            }

            $lead->save();

            // Create Log Entry
            LeadLog::create([
                'lead_id' => $lead->id,
                'user_id' => $actor->id,
                'action' => ($oldStatus !== $newStatus) ? 'status_change' : 'note',
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'description' => $note
            ]);
        });
    }

    /**
     * Bulk assign leads to an agent
     */
    public function assignLeads(array $leadIds, int $agentId, User $assigner): int
    {
        $count = 0;
        
        DB::transaction(function () use ($leadIds, $agentId, $assigner, &$count) {
            $leads = Lead::whereIn('id', $leadIds)->get();
            
            foreach ($leads as $lead) {
                $oldAgentId = $lead->assigned_to;
                if ($oldAgentId === $agentId) continue;

                $lead->assigned_to = $agentId;
                $lead->save();
                
                // Log assignment
                LeadLog::create([
                    'lead_id' => $lead->id,
                    'user_id' => $assigner->id,
                    'action' => 'assignment',
                    'description' => "Assigned to User ID {$agentId}",
                    'old_status' => $lead->status,
                    'new_status' => $lead->status
                ]);
                
                $count++;
            }
        });

        return $count;
    }
}
