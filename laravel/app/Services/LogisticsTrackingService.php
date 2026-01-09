<?php

namespace App\Services;

use App\Models\Waybill;
use App\Models\CustomerOrderHistory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class LogisticsTrackingService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $customerCode;

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

    public function __construct()
    {
        $this->apiUrl = Config::get('services.logistics.api_url', '');
        $this->apiKey = Config::get('services.logistics.api_key', '');
        $this->customerCode = Config::get('services.logistics.customer_code', '');
    }

    /**
     * Query tracking status for a single waybill.
     *
     * @param string $waybillNumber
     * @return array
     */
    public function queryTracking(string $waybillNumber): array
    {
        return $this->batchQueryTracking([$waybillNumber]);
    }

    /**
     * Query tracking status for multiple waybills.
     *
     * @param array $waybillNumbers
     * @return array
     */
    public function batchQueryTracking(array $waybillNumbers): array
    {
        if (empty($this->apiUrl)) {
            Log::warning('Logistics API URL not configured');
            return [
                'success' => false,
                'error' => 'Logistics API not configured',
                'data' => [],
            ];
        }

        $results = [
            'success' => true,
            'queried' => count($waybillNumbers),
            'updated' => 0,
            'failed' => 0,
            'data' => [],
            'errors' => [],
        ];

        try {
            // Make POST request to logistics provider API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post($this->apiUrl . '/track/query', [
                        'waybill_numbers' => $waybillNumbers,
                        'customer_code' => $this->customerCode,
                        'include_history' => true,
                    ]);

            if (!$response->successful()) {
                Log::error('Logistics API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'success' => false,
                    'error' => 'API request failed: ' . $response->status(),
                    'data' => [],
                ];
            }

            $responseData = $response->json();

            // Process each tracking result
            if (!empty($responseData['data'])) {
                foreach ($responseData['data'] as $trackingData) {
                    $updateResult = $this->processTrackingData($trackingData);
                    $results['data'][] = $updateResult;

                    if ($updateResult['updated']) {
                        $results['updated']++;
                    }
                }
            }

            // Collect errors
            if (!empty($responseData['errors'])) {
                $results['errors'] = $responseData['errors'];
                $results['failed'] = count($responseData['errors']);
            }

            Log::info('Logistics tracking query completed', [
                'queried' => $results['queried'],
                'updated' => $results['updated'],
                'failed' => $results['failed'],
            ]);

        } catch (RequestException $e) {
            Log::error('Logistics API connection error', [
                'message' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
                'data' => [],
            ];
        }

        return $results;
    }

    /**
     * Process individual tracking data and update database.
     *
     * @param array $trackingData
     * @return array
     */
    protected function processTrackingData(array $trackingData): array
    {
        $waybillNumber = $trackingData['waybill_number'] ?? null;

        if (!$waybillNumber) {
            return ['waybill_number' => null, 'updated' => false, 'error' => 'Missing waybill number'];
        }

        $externalStatus = strtoupper($trackingData['current_status'] ?? 'PENDING');
        $internalStatus = $this->statusMapping[$externalStatus] ?? $externalStatus;
        $location = $trackingData['current_location'] ?? null;
        $updated = false;

        // Update Waybill
        $waybill = Waybill::where('waybill_number', $waybillNumber)->first();
        if ($waybill && $waybill->status !== $internalStatus) {
            $waybill->status = $internalStatus;
            $waybill->save();
            $updated = true;
        }

        // Update CustomerOrderHistory
        $orderHistory = CustomerOrderHistory::where('waybill_number', $waybillNumber)
            ->orWhere('jnt_waybill', $waybillNumber)
            ->first();

        if ($orderHistory) {
            $statusChanged = $orderHistory->updateStatus($internalStatus, $location);

            // Store raw API response data
            $orderHistory->jnt_raw_data = $trackingData;
            $orderHistory->jnt_last_sync = now();
            $orderHistory->save();

            $updated = $updated || $statusChanged;
        }

        return [
            'waybill_number' => $waybillNumber,
            'status' => $internalStatus,
            'updated' => $updated,
        ];
    }

    /**
     * Sync all pending orders that need tracking updates.
     *
     * @return array
     */
    public function syncPendingOrders(): array
    {
        // Get orders that need syncing
        $ordersNeedingSync = CustomerOrderHistory::needsSync()
            ->limit(100)
            ->pluck('waybill_number')
            ->filter()
            ->toArray();

        if (empty($ordersNeedingSync)) {
            return [
                'success' => true,
                'message' => 'No orders need syncing',
                'queried' => 0,
                'updated' => 0,
            ];
        }

        return $this->batchQueryTracking($ordersNeedingSync);
    }

    /**
     * Check if the logistics API is configured and reachable.
     *
     * @return array
     */
    public function healthCheck(): array
    {
        if (empty($this->apiUrl)) {
            return [
                'configured' => false,
                'reachable' => false,
                'message' => 'API URL not configured',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(10)->get($this->apiUrl . '/health');

            return [
                'configured' => true,
                'reachable' => $response->successful(),
                'status' => $response->status(),
                'message' => $response->successful() ? 'API is reachable' : 'API returned error',
            ];
        } catch (\Exception $e) {
            return [
                'configured' => true,
                'reachable' => false,
                'message' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }
}
