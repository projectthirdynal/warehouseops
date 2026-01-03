<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadCycle;
use App\Models\LeadLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadService
{
    protected LeadCycleService $cycleService;

    public function __construct(LeadCycleService $cycleService)
    {
        $this->cycleService = $cycleService;
    }

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
                // Record call on the active cycle
                $this->cycleService->recordCall($lead, $actor, $note);
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

                // Close the active cycle as SALE
                $activeCycle = $lead->activeCycle;
                if ($activeCycle) {
                    $this->cycleService->closeCycle($activeCycle, LeadCycle::STATUS_CLOSED_SALE, $actor, 'Lead converted to sale');
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

            // Handle REJECT status - close cycle
            if ($newStatus === Lead::STATUS_REJECT) {
                $activeCycle = $lead->activeCycle;
                if ($activeCycle) {
                    $this->cycleService->closeCycle($activeCycle, LeadCycle::STATUS_CLOSED_REJECT, $actor, $note ?? 'Lead rejected');
                }
            }
            
            if ($note) {
                // Add structured note to cycle
                $this->cycleService->addNote($lead, $note, $actor, 'status_change');
            }

            $lead->save();

            // Create Log Entry (legacy system)
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
     * Bulk assign leads to an agent using the cycle system
     */
    public function assignLeads(array $leadIds, int $agentId, User $assigner): int
    {
        $agent = User::findOrFail($agentId);
        $results = $this->cycleService->distributeLeads($leadIds, $agent, $assigner);
        
        // Log failures for admin visibility
        if ($results['failed'] > 0) {
            Log::warning("Lead Assignment Failures", [
                'agent_id' => $agentId,
                'assigner_id' => $assigner->id,
                'errors' => $results['errors']
            ]);
        }

        return $results['success'];
    }

    /**
     * Get the cycle service for direct access if needed.
     */
    public function getCycleService(): LeadCycleService
    {
        return $this->cycleService;
    }
}
