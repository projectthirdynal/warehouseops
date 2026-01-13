<?php

namespace App\Services\Courier;

use App\Models\Waybill;
use App\Models\WaybillTrackingHistory;

/**
 * Manual courier service for status updates without API integration.
 * This is the default fallback when no courier API is configured.
 */
class ManualCourierService implements CourierInterface
{
    public function getCode(): string
    {
        return 'manual';
    }

    public function createOrder(Waybill $waybill): array
    {
        // Manual entry doesn't create orders via API
        // Just return success with current waybill number
        return [
            'success' => true,
            'waybill_no' => $waybill->waybill_number,
            'sorting_code' => null,
            'error' => null,
        ];
    }

    public function cancelOrder(string $waybillNo): bool
    {
        // Manual cancellation is always "successful"
        return true;
    }

    public function trackOrder(string $waybillNo): array
    {
        // For manual tracking, return the current status from DB
        $waybill = Waybill::where('waybill_number', $waybillNo)
            ->orWhere('courier_waybill_no', $waybillNo)
            ->first();

        if (!$waybill) {
            return [
                'status' => 'unknown',
                'reason' => 'Waybill not found',
                'location' => null,
                'occurred_at' => null,
            ];
        }

        return [
            'status' => $waybill->courier_tracking_status ?? 'pending',
            'reason' => $waybill->courier_status_reason,
            'location' => null,
            'occurred_at' => $waybill->courier_last_update?->toIso8601String(),
        ];
    }

    public function parseWebhookPayload(array $payload): array
    {
        // Manual service doesn't receive webhooks
        return [
            'waybill_no' => $payload['waybill_no'] ?? '',
            'status' => $payload['status'] ?? 'pending',
            'reason' => $payload['reason'] ?? null,
            'location' => $payload['location'] ?? null,
            'occurred_at' => $payload['occurred_at'] ?? now()->toIso8601String(),
        ];
    }

    public function validateWebhook(array $payload, ?string $signature): bool
    {
        // Manual service doesn't use webhooks, always return true for internal use
        return true;
    }

    public function hasCredentials(): bool
    {
        // Manual service doesn't need credentials
        return true;
    }

    public function updateStatus(Waybill $waybill, string $status, ?string $reason = null): bool
    {
        // Update waybill with new status
        $waybill->update([
            'courier_tracking_status' => $status,
            'courier_status_reason' => $reason,
            'courier_last_update' => now(),
        ]);

        // Log to tracking history
        WaybillTrackingHistory::create([
            'waybill_id' => $waybill->id,
            'status' => $status,
            'reason' => $reason,
            'occurred_at' => now(),
            'received_at' => now(),
            'raw_payload' => [
                'source' => 'manual',
                'updated_by' => auth()->id(),
            ],
        ]);

        // Sync with main waybill status if it's a terminal status
        if (in_array($status, ['delivered', 'returned'])) {
            $waybill->update(['status' => strtoupper($status)]);
        }

        return true;
    }
}
