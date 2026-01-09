<?php

namespace App\Services\Courier;

use App\Models\CourierProvider;
use App\Models\Waybill;
use App\Models\WaybillTrackingHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * J&T Express courier service implementation.
 * Handles API communication with J&T for order creation, tracking, and webhooks.
 */
class JntCourierService implements CourierInterface
{
    protected CourierProvider $provider;

    // J&T status mapping to internal status
    protected array $statusMap = [
        'PICKUP_FAILED' => 'pickup_failed',
        'PICKUP' => 'picked_up',
        'DEPARTURE' => 'in_transit',
        'ARRIVAL' => 'arrived_hub',
        'DELIVERING' => 'out_for_delivery',
        'DELIVERY_FAILED' => 'delivery_failed',
        'DELIVERED' => 'delivered',
        'RETURN' => 'returning',
        'RETURNED' => 'returned',
    ];

    public function __construct(?CourierProvider $provider = null)
    {
        $this->provider = $provider ?? CourierProvider::findByCode('jnt');
    }

    public function getCode(): string
    {
        return 'jnt';
    }

    public function createOrder(Waybill $waybill): array
    {
        if (!$this->hasCredentials()) {
            return [
                'success' => false,
                'waybill_no' => null,
                'sorting_code' => null,
                'error' => 'J&T API credentials not configured',
            ];
        }

        try {
            // Build order payload according to J&T API spec
            $payload = [
                'eccompanyid' => $this->provider->settings['company_id'] ?? '',
                'customerid' => $this->provider->settings['customer_id'] ?? '',
                'txlogisticid' => $waybill->waybill_number,
                'ordertype' => 1, // 1 = COD, 2 = Prepaid
                'servicetype' => $this->mapServiceType($waybill->service_type),
                'sender' => [
                    'name' => $waybill->sender_name,
                    'mobile' => $waybill->sender_phone,
                    'address' => $waybill->sender_address,
                ],
                'receiver' => [
                    'name' => $waybill->receiver_name,
                    'mobile' => $waybill->receiver_phone,
                    'address' => $waybill->receiver_address,
                    'prov' => $waybill->province,
                    'city' => $waybill->city,
                    'area' => $waybill->barangay,
                ],
                'itemsvalue' => $waybill->cod_amount ?? 0,
                'goodsvalue' => $waybill->cod_amount ?? 0,
                'weight' => $waybill->weight ?? 1,
                'quantity' => $waybill->quantity ?? 1,
                'remark' => $waybill->remarks ?? '',
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'apiKey' => $this->provider->api_key,
            ])->post($this->provider->base_url . '/order/create', $payload);

            if ($response->successful() && $response->json('success')) {
                $data = $response->json('data');
                return [
                    'success' => true,
                    'waybill_no' => $data['billcode'] ?? $data['waybillNo'] ?? null,
                    'sorting_code' => $data['sortingCode'] ?? $data['sorting_code'] ?? null,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'waybill_no' => null,
                'sorting_code' => null,
                'error' => $response->json('message') ?? 'Unknown error from J&T API',
            ];
        } catch (\Exception $e) {
            Log::error('J&T API Error: ' . $e->getMessage(), [
                'waybill' => $waybill->waybill_number,
            ]);

            return [
                'success' => false,
                'waybill_no' => null,
                'sorting_code' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function cancelOrder(string $waybillNo): bool
    {
        if (!$this->hasCredentials()) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'apiKey' => $this->provider->api_key,
            ])->post($this->provider->base_url . '/order/cancel', [
                'billcode' => $waybillNo,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('J&T Cancel Order Error: ' . $e->getMessage());
            return false;
        }
    }

    public function trackOrder(string $waybillNo): array
    {
        if (!$this->hasCredentials()) {
            return [
                'status' => 'unknown',
                'reason' => 'API credentials not configured',
                'location' => null,
                'occurred_at' => null,
            ];
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'apiKey' => $this->provider->api_key,
            ])->get($this->provider->base_url . '/track', [
                'billcode' => $waybillNo,
            ]);

            if ($response->successful()) {
                $data = $response->json('data');
                $latestEvent = $data['details'][0] ?? null;

                return [
                    'status' => $this->mapStatus($latestEvent['scantype'] ?? 'PENDING'),
                    'reason' => $latestEvent['desc'] ?? null,
                    'location' => $latestEvent['city'] ?? null,
                    'occurred_at' => $latestEvent['scantime'] ?? null,
                ];
            }

            return [
                'status' => 'unknown',
                'reason' => 'Unable to retrieve tracking info',
                'location' => null,
                'occurred_at' => null,
            ];
        } catch (\Exception $e) {
            Log::error('J&T Track Order Error: ' . $e->getMessage());
            return [
                'status' => 'unknown',
                'reason' => $e->getMessage(),
                'location' => null,
                'occurred_at' => null,
            ];
        }
    }

    public function parseWebhookPayload(array $payload): array
    {
        // Parse J&T webhook format
        return [
            'waybill_no' => $payload['billcode'] ?? $payload['waybillNo'] ?? '',
            'status' => $this->mapStatus($payload['scantype'] ?? $payload['status'] ?? 'PENDING'),
            'reason' => $payload['desc'] ?? $payload['reason'] ?? null,
            'location' => $payload['city'] ?? $payload['location'] ?? null,
            'occurred_at' => $payload['scantime'] ?? $payload['occurred_at'] ?? now()->toIso8601String(),
        ];
    }

    public function validateWebhook(array $payload, ?string $signature): bool
    {
        if (!$signature) {
            return false;
        }

        // J&T uses API key validation in headers
        // Compare with stored webhook secret if available
        $webhookSecret = $this->provider->settings['webhook_secret'] ?? $this->provider->api_key;
        
        // Simple signature validation (J&T may use different method)
        return hash_equals($webhookSecret, $signature);
    }

    public function hasCredentials(): bool
    {
        return !empty($this->provider?->api_key);
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
                'source' => 'jnt_manual',
                'updated_by' => auth()->id(),
            ],
        ]);

        // Sync with main waybill status if it's a terminal status
        if (in_array($status, ['delivered', 'returned'])) {
            $waybill->update(['status' => strtoupper($status)]);
        }

        return true;
    }

    /**
     * Map J&T status codes to internal status.
     */
    protected function mapStatus(string $jntStatus): string
    {
        return $this->statusMap[strtoupper($jntStatus)] ?? 'pending';
    }

    /**
     * Map internal service type to J&T service type.
     */
    protected function mapServiceType(?string $serviceType): int
    {
        $types = [
            'express' => 1,
            'standard' => 2,
            'economy' => 3,
        ];

        return $types[strtolower($serviceType ?? 'express')] ?? 1;
    }
}
