<?php

namespace App\Services\Courier;

use App\Models\Waybill;

interface CourierInterface
{
    /**
     * Get the courier provider code (e.g., 'jnt', 'lbc').
     */
    public function getCode(): string;

    /**
     * Create an order with the courier.
     * Returns array with courier_waybill_no and courier_sorting_code.
     *
     * @param Waybill $waybill
     * @return array ['success' => bool, 'waybill_no' => string, 'sorting_code' => string, 'error' => string|null]
     */
    public function createOrder(Waybill $waybill): array;

    /**
     * Cancel an order with the courier.
     *
     * @param string $waybillNo
     * @return bool
     */
    public function cancelOrder(string $waybillNo): bool;

    /**
     * Track an order and get latest status.
     *
     * @param string $waybillNo
     * @return array ['status' => string, 'reason' => string|null, 'location' => string|null, 'occurred_at' => string|null]
     */
    public function trackOrder(string $waybillNo): array;

    /**
     * Parse incoming webhook payload and return normalized status data.
     *
     * @param array $payload
     * @return array ['waybill_no' => string, 'status' => string, 'reason' => string|null, 'location' => string|null, 'occurred_at' => string|null]
     */
    public function parseWebhookPayload(array $payload): array;

    /**
     * Validate webhook signature/authentication.
     *
     * @param array $payload
     * @param string|null $signature
     * @return bool
     */
    public function validateWebhook(array $payload, ?string $signature): bool;

    /**
     * Check if API credentials are configured.
     */
    public function hasCredentials(): bool;

    /**
     * Update waybill status manually (for non-API providers).
     *
     * @param Waybill $waybill
     * @param string $status
     * @param string|null $reason
     * @return bool
     */
    public function updateStatus(Waybill $waybill, string $status, ?string $reason = null): bool;
}
