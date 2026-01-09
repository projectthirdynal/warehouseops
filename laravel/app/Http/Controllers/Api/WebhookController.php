<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Waybill;
use App\Models\CustomerOrderHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    /**
     * Status mapping from J&T logistics provider to internal status.
     * J&T Statuses: Pickup Failed, Pickup, Departure, Arrival, Delivering, Delivery Failed, Delivered, Return, Returned
     */
    protected array $statusMapping = [
        'PICKUP_FAILED' => 'PENDING',
        'PICKUP' => 'DISPATCHED',
        'DEPARTURE' => 'IN_TRANSIT',
        'ARRIVAL' => 'IN_TRANSIT',
        'DELIVERING' => 'DELIVERING',
        'DELIVERY_FAILED' => 'PENDING',
        'DELIVERED' => 'DELIVERED',
        'RETURN' => 'RETURNED',
        'RETURNED' => 'RETURNED',
    ];

    /**
     * Receive tracking status updates from logistics provider webhook.
     *
     * POST /api/webhooks/tracking
     */
    public function receiveTrackingUpdate(Request $request): JsonResponse
    {
        // Validate webhook secret if configured
        $webhookSecret = config('services.logistics.webhook_secret');
        if ($webhookSecret) {
            $providedSecret = $request->header('X-Webhook-Secret') ?? $request->input('webhook_secret');
            if ($providedSecret !== $webhookSecret) {
                Log::warning('Webhook authentication failed', [
                    'ip' => $request->ip(),
                    'provided_secret' => substr($providedSecret ?? '', 0, 8) . '...',
                ]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        // Validate incoming payload
        $validator = Validator::make($request->all(), [
            'waybill_number' => 'required|string|max:50',
            'status' => 'required|string|max:50',
            'location' => 'nullable|string|max:255',
            'timestamp' => 'nullable|date',
            'details' => 'nullable|string|max:500',
            'rider_name' => 'nullable|string|max:100',
            'rider_phone' => 'nullable|string|max:20',
            'signature_url' => 'nullable|url|max:500',
            'photo_url' => 'nullable|url|max:500',
        ]);

        if ($validator->fails()) {
            Log::warning('Webhook validation failed', [
                'errors' => $validator->errors()->toArray(),
                'payload' => $request->all(),
            ]);
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $waybillNumber = $data['waybill_number'];
        $externalStatus = strtoupper($data['status']);
        $internalStatus = $this->statusMapping[$externalStatus] ?? $externalStatus;

        Log::info('Received tracking webhook', [
            'waybill_number' => $waybillNumber,
            'external_status' => $externalStatus,
            'internal_status' => $internalStatus,
        ]);

        // Update Waybill record
        $waybill = Waybill::where('waybill_number', $waybillNumber)->first();
        $waybillUpdated = false;

        if ($waybill) {
            $waybill->status = $internalStatus;
            if ($internalStatus === 'DELIVERED' && !empty($data['timestamp'])) {
                $waybill->signing_time = $data['timestamp'];
            }
            $waybill->save();
            $waybillUpdated = true;
        }

        // Update CustomerOrderHistory record
        $orderHistory = CustomerOrderHistory::where('waybill_number', $waybillNumber)
            ->orWhere('jnt_waybill', $waybillNumber)
            ->first();
        $orderHistoryUpdated = false;

        if ($orderHistory) {
            $orderHistory->updateStatus(
                $internalStatus,
                $data['location'] ?? null,
                $data['details'] ?? null
            );

            // Store raw webhook data
            $orderHistory->jnt_raw_data = array_merge(
                $orderHistory->jnt_raw_data ?? [],
                ['last_webhook' => $data]
            );
            $orderHistory->jnt_last_sync = now();
            $orderHistory->save();
            $orderHistoryUpdated = true;
        }

        // Log result
        Log::info('Webhook processed', [
            'waybill_number' => $waybillNumber,
            'waybill_updated' => $waybillUpdated,
            'order_history_updated' => $orderHistoryUpdated,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tracking update received',
            'waybill_updated' => $waybillUpdated,
            'order_history_updated' => $orderHistoryUpdated,
        ]);
    }

    /**
     * Verify webhook endpoint is active (health check).
     *
     * GET /api/webhooks/tracking/health
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'tracking-webhook',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
