<?php

namespace App\Observers;

use App\Jobs\RecalculateCustomerMetrics;
use App\Models\CustomerOrderHistory;
use App\Services\RecyclingPoolService;
use Illuminate\Support\Facades\Log;

class CustomerOrderHistoryObserver
{
    /**
     * Handle the CustomerOrderHistory "created" event.
     * When a new order is added to customer history, recalculate metrics
     */
    public function created(CustomerOrderHistory $orderHistory): void
    {
        if ($orderHistory->customer_id) {
            Log::info("New order created for customer, triggering metrics update", [
                'customer_id' => $orderHistory->customer_id,
                'waybill' => $orderHistory->waybill_number
            ]);

            // Dispatch job to recalculate metrics
            dispatch(new RecalculateCustomerMetrics($orderHistory->customer_id));
        }
    }

    /**
     * Handle the CustomerOrderHistory "updated" event.
     * When order status changes, recalculate customer metrics and evaluate for recycling
     */
    public function updated(CustomerOrderHistory $orderHistory): void
    {
        // Only recalculate if status changed (avoid unnecessary calculations)
        if ($orderHistory->isDirty('current_status') && $orderHistory->customer_id) {
            $oldStatus = $orderHistory->getOriginal('current_status');
            $newStatus = $orderHistory->current_status;

            Log::info("Order status changed, triggering metrics update", [
                'customer_id' => $orderHistory->customer_id,
                'waybill' => $orderHistory->waybill_number,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);

            // Dispatch job to recalculate metrics
            dispatch(new RecalculateCustomerMetrics($orderHistory->customer_id));

            // Evaluate for recycling if order is returned or failed
            if (in_array($newStatus, [CustomerOrderHistory::STATUS_RETURNED, 'FAILED_DELIVERY'])) {
                $this->evaluateForRecycling($orderHistory);
            }
        }
    }

    /**
     * Evaluate order for recycling pool entry
     */
    private function evaluateForRecycling(CustomerOrderHistory $orderHistory): void
    {
        try {
            $recyclingService = app(RecyclingPoolService::class);
            $poolEntry = $recyclingService->evaluateForRecycling($orderHistory);

            if ($poolEntry) {
                Log::info("Order added to recycling pool", [
                    'order_id' => $orderHistory->id,
                    'pool_id' => $poolEntry->id,
                    'priority' => $poolEntry->priority_score
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to evaluate order for recycling", [
                'order_id' => $orderHistory->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the CustomerOrderHistory "deleted" event.
     * Recalculate metrics when an order is removed
     */
    public function deleted(CustomerOrderHistory $orderHistory): void
    {
        if ($orderHistory->customer_id) {
            Log::info("Order deleted, triggering metrics update", [
                'customer_id' => $orderHistory->customer_id,
                'waybill' => $orderHistory->waybill_number
            ]);

            // Dispatch job to recalculate metrics
            dispatch(new RecalculateCustomerMetrics($orderHistory->customer_id));
        }
    }
}
