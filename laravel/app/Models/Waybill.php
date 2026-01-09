<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Waybill extends Model
{
    protected $fillable = [
        'waybill_number', 'upload_id', 'lead_id', 'product_id', 'sender_name', 'sender_address', 'sender_phone',
        'receiver_name', 'receiver_address', 'receiver_phone', 'destination',
        'province', 'city', 'barangay', 'street',
        'weight', 'quantity', 'service_type', 'cod_amount', 'remarks', 'item_name', 'status', 'batch_ready', 'marked_pending_at', 'signing_time',
        // Courier integration fields
        'courier_provider_id', 'courier_waybill_no', 'courier_sorting_code',
        'courier_tracking_status', 'courier_status_reason', 'courier_last_update',
    ];

    protected $casts = [
        'signing_time' => 'datetime',
        'marked_pending_at' => 'datetime',
        'batch_ready' => 'boolean',
        'courier_last_update' => 'datetime',
    ];

    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the lead associated with this waybill.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the courier provider for this waybill.
     */
    public function courierProvider(): BelongsTo
    {
        return $this->belongsTo(CourierProvider::class);
    }

    /**
     * Get all tracking history events for this waybill.
     */
    public function trackingHistory(): HasMany
    {
        return $this->hasMany(WaybillTrackingHistory::class)->orderBy('occurred_at', 'desc');
    }

    /**
     * Get human-readable courier tracking status.
     */
    public function getCourierStatusLabelAttribute(): string
    {
        $labels = [
            'pickup_failed' => 'Pickup Failed',
            'picked_up' => 'Picked Up',
            'in_transit' => 'In Transit',
            'arrived_hub' => 'Arrived at Hub',
            'out_for_delivery' => 'Out for Delivery',
            'delivery_failed' => 'Delivery Failed',
            'delivered' => 'Delivered',
            'returning' => 'Returning',
            'returned' => 'Returned',
        ];

        return $labels[$this->courier_tracking_status] ?? ucfirst(str_replace('_', ' ', $this->courier_tracking_status ?? 'pending'));
    }
}
