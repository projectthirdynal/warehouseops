<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\OrderHistoryService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    protected OrderHistoryService $orderHistoryService;

    public function __construct(OrderHistoryService $orderHistoryService)
    {
        $this->orderHistoryService = $orderHistoryService;
    }

    /**
     * Handle the Order "created" event.
     * Creates order history record when a lead converts to sale.
     */
    public function created(Order $order): void
    {
        // Get the lead for this order
        $lead = $order->lead;

        if (!$lead) {
            Log::warning("OrderObserver: Order created without lead reference", [
                'order_id' => $order->id,
            ]);
            return;
        }

        // Only process if lead has phone (required for customer identification)
        if (!$lead->phone) {
            Log::warning("OrderObserver: Lead has no phone number", [
                'order_id' => $order->id,
                'lead_id' => $lead->id,
            ]);
            return;
        }

        try {
            $agent = $order->agent;
            $this->orderHistoryService->createFromLeadSale($lead, $order, $agent);

            Log::debug("OrderObserver: Created order history from lead sale", [
                'order_id' => $order->id,
                'lead_id' => $lead->id,
            ]);
        } catch (\Exception $e) {
            Log::error("OrderObserver: Failed to create order history", [
                'order_id' => $order->id,
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Order "updated" event.
     * Syncs status changes to order history if applicable.
     */
    public function updated(Order $order): void
    {
        // Status changes for orders are typically synced via waybill updates
        // This observer handles direct order status changes if needed

        if (!$order->wasChanged('status')) {
            return;
        }

        Log::debug("OrderObserver: Order status updated", [
            'order_id' => $order->id,
            'new_status' => $order->status,
        ]);

        // Future: Could sync order status to customer_order_history if needed
    }
}
