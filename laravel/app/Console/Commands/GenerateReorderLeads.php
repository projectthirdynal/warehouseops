<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Waybill;
use App\Models\Lead;
use App\Models\LeadLog;
use Illuminate\Support\Facades\DB;

class GenerateReorderLeads extends Command
{
    protected $signature = 'leads:generate-reorders';
    protected $description = 'Automatically generate reorder leads from delivered waybills older than 14 days';

    public function handle()
    {
        $this->info('Starting reorder lead generation...');

        // 1. Find Waybills:
        // - status = 'Delivered' (case insensitive)
        // - delivered_at (signing_time) >= 14 days ago
        // - no active lead exists for the same phone number
        
        $cutoffDate = now()->subDays(14);

        $count = 0;

        Waybill::where('status', 'ILIKE', 'delivered')
            ->where('signing_time', '<=', $cutoffDate)
            ->whereNotNull('receiver_phone')
            ->chunk(1000, function ($waybills) use (&$count) {
                foreach ($waybills as $wb) {
                    // Check if an active lead already exists for this phone
                    $activeLeadExists = Lead::where('phone', $wb->receiver_phone)
                        ->whereNotIn('status', [Lead::STATUS_SALE, Lead::STATUS_DELIVERED, Lead::STATUS_CANCELLED])
                        ->exists();

                    if ($activeLeadExists) {
                        continue;
                    }

                    // Create Reorder Lead
                    DB::transaction(function () use ($wb, &$count) {
                        // Find original agent if possible
                        $lastLead = Lead::where('phone', $wb->receiver_phone)->latest()->first();
                        $originalAgentId = $lastLead ? $lastLead->assigned_to : null;

                        $lead = Lead::create([
                            'name' => $wb->receiver_name,
                            'phone' => $wb->receiver_phone,
                            'address' => $wb->receiver_address,
                            'city' => $wb->destination,
                            'status' => Lead::STATUS_NEW,
                            'source' => 'reorder',
                            'assigned_to' => $originalAgentId,
                            'original_agent_id' => $originalAgentId,
                            'notes' => "Automated reorder from Waybill #{$wb->waybill_number}"
                        ]);

                        LeadLog::create([
                            'lead_id' => $lead->id,
                            'user_id' => 1, // System
                            'action' => 'status_change',
                            'new_status' => Lead::STATUS_NEW,
                            'description' => 'System generated reorder lead.'
                        ]);

                        $count++;
                    });
                }
                $this->info("Processed a batch... Current count: {$count}");
            });

        $this->info("Successfully generated {$count} reorder leads.");
    }
}
