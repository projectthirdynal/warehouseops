<?php

namespace App\Observers;

use App\Models\Waybill;
use App\Services\CustomerIdentificationService;
use App\Services\OrderHistoryService;
use Illuminate\Support\Facades\Log;

class WaybillObserver
{
    protected OrderHistoryService $orderHistoryService;
    protected CustomerIdentificationService $customerService;

    public function __construct(
        OrderHistoryService $orderHistoryService,
        CustomerIdentificationService $customerService
    ) {
        $this->orderHistoryService = $orderHistoryService;
        $this->customerService = $customerService;
    }

    /**
     * Handle the Waybill "created" event.
     * Creates order history record when a waybill is imported/created.
     */
    public function created(Waybill $waybill): void
    {
        // Only process if waybill has receiver phone
        if (!$waybill->receiver_phone) {
            return;
        }

        try {
            $this->orderHistoryService->createFromWaybill($waybill);

            Log::debug("WaybillObserver: Created order history for new waybill", [
                'waybill_id' => $waybill->id,
                'waybill_number' => $waybill->waybill_number,
            ]);
        } catch (\Exception $e) {
            Log::error("WaybillObserver: Failed to create order history", [
                'waybill_id' => $waybill->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Waybill "updated" event.
     * Syncs status changes to order history.
     */
    public function updated(Waybill $waybill): void
    {
        // Only sync if status changed
        if (!$waybill->wasChanged('status')) {
            return;
        }

        try {
            $this->orderHistoryService->syncWaybillStatus($waybill);

            Log::debug("WaybillObserver: Synced status change to order history", [
                'waybill_id' => $waybill->id,
                'waybill_number' => $waybill->waybill_number,
                'new_status' => $waybill->status,
            ]);
        } catch (\Exception $e) {
            Log::error("WaybillObserver: Failed to sync status change", [
                'waybill_id' => $waybill->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
