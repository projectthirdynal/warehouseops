<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadLog;
use App\Models\Order;
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
    public function updateStatus(Lead $lead, string $newStatus, string $note = null, User $actor, array $attributes = []): void
    {
        $oldStatus = $lead->status;

        // Enforce locking
        if ($lead->isLocked() && $actor->role === 'agent') {
            throw new \Exception("Agents cannot update a locked lead (SALE/DELIVERED).");
        }

        DB::transaction(function () use ($lead, $newStatus, $note, $oldStatus, $actor, $attributes) {
            $lead->status = $newStatus;
            
            // Update metadata if it's a call-related status
            if (in_array($newStatus, [Lead::STATUS_NO_ANSWER, Lead::STATUS_REJECT, Lead::STATUS_CALLBACK, Lead::STATUS_SALE])) {
                $lead->last_called_at = now();
                $lead->call_attempts++;
            }

            // Update additional attributes if provided
            if (isset($attributes['product_name'])) $lead->product_name = $attributes['product_name'];
            if (isset($attributes['product_brand'])) $lead->product_brand = $attributes['product_brand'];
            if (isset($attributes['amount'])) $lead->amount = $attributes['amount'];
            if (isset($attributes['address'])) $lead->address = $attributes['address'];
            if (isset($attributes['province'])) $lead->state = $attributes['province'];
            if (isset($attributes['city'])) $lead->city = $attributes['city'];
            if (isset($attributes['barangay'])) $lead->barangay = $attributes['barangay'];
            if (isset($attributes['street'])) $lead->street = $attributes['street'];

            if ($newStatus === Lead::STATUS_SALE) {
                if ($oldStatus !== Lead::STATUS_SALE) {
                    $lead->submitted_at = now();
                }

                // Create a historical order record
                Order::create([
                    'lead_id' => $lead->id,
                    'agent_id' => $actor->id,
                    'product_name' => $attributes['product_name'] ?? $lead->product_name,
                    'product_brand' => $attributes['product_brand'] ?? $lead->product_brand,
                    'amount' => $attributes['amount'] ?? $lead->amount,
                    'status' => 'PENDING', // Default to PENDING upon sale
                    'address' => $attributes['address'] ?? $lead->address,
                    'province' => $attributes['province'] ?? $lead->state,
                    'city' => $attributes['city'] ?? $lead->city,
                    'barangay' => $attributes['barangay'] ?? $lead->barangay,
                    'street' => $attributes['street'] ?? $lead->street,
                    'notes' => $note
                ]);
            }
            
            if ($note) {
                // Append note to main notes
                $date = now()->format('Y-m-d H:i');
                $newNoteEntry = "[{$date} {$actor->name}]: {$note}";
                $lead->notes = $lead->notes ? $lead->notes . "\n" . $newNoteEntry : $newNoteEntry;
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
                $lead->assigned_at = now();
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
