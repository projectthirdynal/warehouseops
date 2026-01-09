<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaybillTrackingHistory extends Model
{
    protected $table = 'waybill_tracking_history';

    protected $fillable = [
        'waybill_id',
        'status',
        'reason',
        'location',
        'occurred_at',
        'received_at',
        'raw_payload',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'received_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    /**
     * Get the waybill this tracking event belongs to.
     */
    public function waybill(): BelongsTo
    {
        return $this->belongsTo(Waybill::class);
    }

    /**
     * Get human-readable status label.
     */
    public function getStatusLabelAttribute(): string
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

        return $labels[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * Get badge color class for status.
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'delivered' => 'success',
            'picked_up' => 'info',
            'in_transit' => 'primary',
            'out_for_delivery' => 'warning',
            'delivery_failed' => 'danger',
            'pickup_failed' => 'danger',
            'returning' => 'warning',
            'returned' => 'secondary',
        ];

        return $badges[$this->status] ?? 'secondary';
    }
}
