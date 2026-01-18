<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourierProvider;
use App\Models\Waybill;
use App\Models\WaybillTrackingHistory;
use App\Services\Courier\CourierFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourierWebhookController extends Controller
{
    /**
     * Handle J&T Express webhook for status updates.
     */
    public function handleJnt(Request $request)
    {
        Log::info('J&T Webhook received', ['payload' => $request->all()]);

        $provider = CourierProvider::findByCode('jnt');
        
        if (!$provider || !$provider->is_active) {
            return response()->json(['success' => false, 'message' => 'Provider not active'], 400);
        }

        // Get the courier service
        $courierService = CourierFactory::make('jnt');

        // Validate webhook signature if provided
        $signature = $request->header('X-JNT-Signature') ?? $request->header('Authorization');
        if (!$courierService->validateWebhook($request->all(), $signature)) {
            Log::warning('J&T Webhook signature validation failed', [
                'ip' => $request->ip(),
                'payload' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature'
            ], 401);
        }

        // Parse the webhook payload
        $parsed = $courierService->parseWebhookPayload($request->all());

        if (empty($parsed['waybill_no'])) {
            return response()->json(['success' => false, 'message' => 'Missing waybill number'], 400);
        }

        // Find the waybill
        $waybill = Waybill::where('courier_waybill_no', $parsed['waybill_no'])
            ->orWhere('waybill_number', $parsed['waybill_no'])
            ->first();

        if (!$waybill) {
            Log::warning('J&T Webhook: Waybill not found', ['waybill_no' => $parsed['waybill_no']]);
            return response()->json(['success' => false, 'message' => 'Waybill not found'], 404);
        }

        // Update waybill status
        $waybill->update([
            'courier_tracking_status' => $parsed['status'],
            'courier_status_reason' => $parsed['reason'],
            'courier_last_update' => now(),
        ]);

        // Log to tracking history
        WaybillTrackingHistory::create([
            'waybill_id' => $waybill->id,
            'status' => $parsed['status'],
            'reason' => $parsed['reason'],
            'location' => $parsed['location'],
            'occurred_at' => $parsed['occurred_at'] ? \Carbon\Carbon::parse($parsed['occurred_at']) : now(),
            'received_at' => now(),
            'raw_payload' => $request->all(),
        ]);

        // Sync terminal statuses with main waybill status
        if (in_array($parsed['status'], ['delivered', 'returned'])) {
            $waybill->update(['status' => strtoupper($parsed['status'])]);
        }

        Log::info('J&T Webhook processed successfully', [
            'waybill_id' => $waybill->id,
            'status' => $parsed['status'],
        ]);

        return response()->json(['success' => true, 'message' => 'Status updated']);
    }

    /**
     * Generic webhook handler for any courier (routes by code).
     */
    public function handleGeneric(Request $request, string $courierCode)
    {
        Log::info("Courier webhook received: {$courierCode}", ['payload' => $request->all()]);

        $provider = CourierProvider::findByCode($courierCode);
        
        if (!$provider || !$provider->is_active) {
            return response()->json(['success' => false, 'message' => 'Provider not active'], 400);
        }

        if (!CourierFactory::supports($courierCode)) {
            return response()->json(['success' => false, 'message' => 'Courier not supported'], 400);
        }

        $courierService = CourierFactory::make($courierCode);

        // Validate webhook signature
        $signature = $request->header('X-Webhook-Signature') ?? $request->header('Authorization');
        if (!$courierService->validateWebhook($request->all(), $signature)) {
            Log::warning("Courier {$courierCode} webhook signature validation failed", [
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature'
            ], 401);
        }

        $parsed = $courierService->parseWebhookPayload($request->all());

        if (empty($parsed['waybill_no'])) {
            return response()->json(['success' => false, 'message' => 'Missing waybill number'], 400);
        }

        $waybill = Waybill::where('courier_waybill_no', $parsed['waybill_no'])
            ->orWhere('waybill_number', $parsed['waybill_no'])
            ->first();

        if (!$waybill) {
            return response()->json(['success' => false, 'message' => 'Waybill not found'], 404);
        }

        $courierService->updateStatus($waybill, $parsed['status'], $parsed['reason']);

        return response()->json(['success' => true, 'message' => 'Status updated']);
    }
}
