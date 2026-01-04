<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerOrderHistory;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use App\Models\Waybill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderHistoryService
{
    protected CustomerIdentificationService $customerService;

    public function __construct(CustomerIdentificationService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Create order history record from a waybill.
     * This is called when waybills are imported/created.
     */
    public function createFromWaybill(Waybill $waybill): ?CustomerOrderHistory
    {
        // Skip if already exists
        if (CustomerOrderHistory::where('waybill_number', $waybill->waybill_number)->exists()) {
            Log::debug("Order history already exists for waybill: {$waybill->waybill_number}");
            return null;
        }

        // Skip if no receiver phone
        if (!$waybill->receiver_phone) {
            Log::warning("Cannot create order history: waybill has no receiver phone", [
                'waybill_id' => $waybill->id
            ]);
            return null;
        }

        try {
            // Find or create customer
            $customer = $this->customerService->findOrCreateCustomer([
                'phone' => $waybill->receiver_phone,
                'name' => $waybill->receiver_name,
                'address' => $waybill->receiver_address,
                'city' => $waybill->city,
                'province' => $waybill->province,
                'barangay' => $waybill->barangay,
                'street' => $waybill->street,
            ]);

            // Determine source type
            $sourceType = CustomerOrderHistory::SOURCE_UPLOAD;
            if ($waybill->lead_id) {
                $sourceType = CustomerOrderHistory::SOURCE_LEAD_CONVERSION;
            }

            // Create order history record
            $orderHistory = CustomerOrderHistory::create([
                'customer_id' => $customer->id,
                'waybill_number' => $waybill->waybill_number,
                'waybill_id' => $waybill->id,
                'source_type' => $sourceType,
                'product_name' => $waybill->item_name ?? $waybill->sender_name,
                'weight' => $waybill->weight,
                'cod_amount' => $waybill->cod_amount,
                'current_status' => strtoupper($waybill->status ?? 'PENDING'),
                'status_history' => [
                    [
                        'status' => strtoupper($waybill->status ?? 'PENDING'),
                        'timestamp' => now()->toIso8601String(),
                        'notes' => 'Initial import',
                    ]
                ],
                'jnt_waybill' => $waybill->waybill_number, // Assume waybill number is J&T waybill
                'lead_id' => $waybill->lead_id,
                'delivery_address' => $waybill->receiver_address,
                'city' => $waybill->city,
                'province' => $waybill->province,
                'barangay' => $waybill->barangay,
                'order_date' => $waybill->signing_time ?? $waybill->created_at,
            ]);

            Log::info("Created order history from waybill", [
                'waybill_id' => $waybill->id,
                'order_history_id' => $orderHistory->id,
                'customer_id' => $customer->id,
            ]);

            return $orderHistory;

        } catch (\Exception $e) {
            Log::error("Failed to create order history from waybill", [
                'waybill_id' => $waybill->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create order history record from a lead conversion (sale).
     * This is called when a lead is marked as SALE.
     */
    public function createFromLeadSale(Lead $lead, Order $order, ?User $agent = null): ?CustomerOrderHistory
    {
        // Generate a temporary waybill number if not yet assigned
        $waybillNumber = 'LEAD-' . $lead->id . '-' . $order->id;

        // Skip if already exists (by checking lead_id + order context)
        $existing = CustomerOrderHistory::where('lead_id', $lead->id)
            ->where('source_type', CustomerOrderHistory::SOURCE_LEAD_CONVERSION)
            ->first();

        if ($existing) {
            Log::debug("Order history already exists for lead sale", [
                'lead_id' => $lead->id
            ]);
            return $existing;
        }

        try {
            // Find or create customer
            $customer = $this->customerService->findOrCreateCustomer([
                'phone' => $lead->phone,
                'name' => $lead->name,
                'address' => $order->address ?? $lead->address,
                'city' => $order->city ?? $lead->city,
                'province' => $order->province ?? $lead->state,
                'barangay' => $order->barangay ?? $lead->barangay,
                'street' => $order->street ?? $lead->street,
            ]);

            // Link lead to customer if not already linked
            if (!$lead->customer_id) {
                $lead->customer_id = $customer->id;
                $lead->save();
            }

            // Create order history record
            $orderHistory = CustomerOrderHistory::create([
                'customer_id' => $customer->id,
                'waybill_number' => $waybillNumber,
                'source_type' => CustomerOrderHistory::SOURCE_LEAD_CONVERSION,
                'product_name' => $order->product_name,
                'cod_amount' => $order->amount,
                'current_status' => CustomerOrderHistory::STATUS_PENDING,
                'status_history' => [
                    [
                        'status' => CustomerOrderHistory::STATUS_PENDING,
                        'timestamp' => now()->toIso8601String(),
                        'notes' => 'Created from lead sale',
                    ]
                ],
                'lead_id' => $lead->id,
                'lead_outcome' => 'SALE',
                'lead_agent' => $agent?->name ?? $order->agent?->name,
                'delivery_address' => $order->address ?? $lead->address,
                'city' => $order->city ?? $lead->city,
                'province' => $order->province ?? $lead->state,
                'barangay' => $order->barangay ?? $lead->barangay,
                'order_date' => now(),
            ]);

            Log::info("Created order history from lead sale", [
                'lead_id' => $lead->id,
                'order_id' => $order->id,
                'order_history_id' => $orderHistory->id,
                'customer_id' => $customer->id,
            ]);

            return $orderHistory;

        } catch (\Exception $e) {
            Log::error("Failed to create order history from lead sale", [
                'lead_id' => $lead->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Update order history when waybill status changes.
     */
    public function syncWaybillStatus(Waybill $waybill): ?CustomerOrderHistory
    {
        $orderHistory = CustomerOrderHistory::where('waybill_id', $waybill->id)
            ->orWhere('waybill_number', $waybill->waybill_number)
            ->first();

        if (!$orderHistory) {
            // Create if doesn't exist
            return $this->createFromWaybill($waybill);
        }

        $newStatus = strtoupper($waybill->status ?? 'PENDING');
        $statusChanged = $orderHistory->updateStatus($newStatus);

        if ($statusChanged) {
            Log::info("Updated order history status from waybill", [
                'waybill_id' => $waybill->id,
                'order_history_id' => $orderHistory->id,
                'new_status' => $newStatus,
            ]);
        }

        return $orderHistory;
    }

    /**
     * Link a real waybill to a lead-generated order history.
     * This is called when a J&T waybill is assigned to a lead order.
     */
    public function linkWaybillToOrder(CustomerOrderHistory $orderHistory, Waybill $waybill): void
    {
        $orderHistory->waybill_id = $waybill->id;
        $orderHistory->waybill_number = $waybill->waybill_number;
        $orderHistory->jnt_waybill = $waybill->waybill_number;
        $orderHistory->save();

        Log::info("Linked waybill to order history", [
            'order_history_id' => $orderHistory->id,
            'waybill_id' => $waybill->id,
            'waybill_number' => $waybill->waybill_number,
        ]);
    }

    /**
     * Import J&T tracking data from CSV/Excel.
     * This handles manual import until API integration is ready.
     *
     * @param array $trackingData Array of tracking records with keys:
     *   - waybill_number (required)
     *   - status (required)
     *   - location (optional)
     *   - timestamp (optional)
     *   - return_reason (optional)
     */
    public function importJNTTracking(array $trackingData): array
    {
        $results = [
            'updated' => 0,
            'created' => 0,
            'not_found' => 0,
            'errors' => 0,
        ];

        foreach ($trackingData as $record) {
            if (empty($record['waybill_number']) || empty($record['status'])) {
                $results['errors']++;
                continue;
            }

            try {
                $orderHistory = CustomerOrderHistory::where('waybill_number', $record['waybill_number'])
                    ->orWhere('jnt_waybill', $record['waybill_number'])
                    ->first();

                if (!$orderHistory) {
                    // Try to find by waybill table and create
                    $waybill = Waybill::where('waybill_number', $record['waybill_number'])->first();

                    if ($waybill) {
                        $orderHistory = $this->createFromWaybill($waybill);
                        if ($orderHistory) {
                            $results['created']++;
                        } else {
                            $results['not_found']++;
                            continue;
                        }
                    } else {
                        $results['not_found']++;
                        continue;
                    }
                }

                // Update status
                $newStatus = $this->normalizeJNTStatus($record['status']);
                $location = $record['location'] ?? null;
                $notes = $record['notes'] ?? null;

                // Build J&T raw data
                $jntData = $orderHistory->jnt_raw_data ?? [];
                $jntData['last_status'] = $record['status'];
                $jntData['last_location'] = $location;
                $jntData['last_update'] = $record['timestamp'] ?? now()->toIso8601String();

                if (!empty($record['return_reason'])) {
                    $jntData['return_reason'] = $record['return_reason'];
                }

                $orderHistory->jnt_raw_data = $jntData;
                $orderHistory->jnt_last_sync = now();

                $statusChanged = $orderHistory->updateStatus($newStatus, $location, $notes);

                if ($statusChanged) {
                    $results['updated']++;
                }

            } catch (\Exception $e) {
                $results['errors']++;
                Log::warning("Failed to import J&T tracking", [
                    'waybill_number' => $record['waybill_number'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("J&T tracking import completed", $results);

        return $results;
    }

    /**
     * Normalize J&T status to our internal status format.
     */
    protected function normalizeJNTStatus(string $jntStatus): string
    {
        $jntStatus = strtoupper(trim($jntStatus));

        // Map common J&T statuses
        $statusMap = [
            'DELIVERED' => CustomerOrderHistory::STATUS_DELIVERED,
            'COMPLETED' => CustomerOrderHistory::STATUS_DELIVERED,
            'SUCCESS' => CustomerOrderHistory::STATUS_DELIVERED,
            'SIGNED' => CustomerOrderHistory::STATUS_DELIVERED,
            'RETURNED' => CustomerOrderHistory::STATUS_RETURNED,
            'RTS' => CustomerOrderHistory::STATUS_RETURNED,
            'RETURN TO SENDER' => CustomerOrderHistory::STATUS_RETURNED,
            'CANCELLED' => CustomerOrderHistory::STATUS_CANCELLED,
            'CANCEL' => CustomerOrderHistory::STATUS_CANCELLED,
            'IN TRANSIT' => CustomerOrderHistory::STATUS_IN_TRANSIT,
            'INTRANSIT' => CustomerOrderHistory::STATUS_IN_TRANSIT,
            'ON THE WAY' => CustomerOrderHistory::STATUS_IN_TRANSIT,
            'DELIVERING' => CustomerOrderHistory::STATUS_DELIVERING,
            'OUT FOR DELIVERY' => CustomerOrderHistory::STATUS_DELIVERING,
            'DISPATCHED' => CustomerOrderHistory::STATUS_DISPATCHED,
            'PICKED UP' => CustomerOrderHistory::STATUS_DISPATCHED,
            'PENDING' => CustomerOrderHistory::STATUS_PENDING,
        ];

        return $statusMap[$jntStatus] ?? CustomerOrderHistory::STATUS_IN_TRANSIT;
    }

    /**
     * Bulk sync waybills to order history.
     * Used for initial data migration.
     */
    public function bulkSyncWaybills(iterable $waybills, ?callable $progressCallback = null): array
    {
        $results = [
            'processed' => 0,
            'created' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        foreach ($waybills as $waybill) {
            $results['processed']++;

            try {
                // Check if already exists
                $exists = CustomerOrderHistory::where('waybill_number', $waybill->waybill_number)->exists();

                if ($exists) {
                    $results['skipped']++;
                } else {
                    $created = $this->createFromWaybill($waybill);
                    if ($created) {
                        $results['created']++;
                    } else {
                        $results['skipped']++;
                    }
                }
            } catch (\Exception $e) {
                $results['errors']++;
                Log::warning("Failed to sync waybill to order history", [
                    'waybill_id' => $waybill->id,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($progressCallback) {
                $progressCallback($results['processed']);
            }
        }

        return $results;
    }
}
