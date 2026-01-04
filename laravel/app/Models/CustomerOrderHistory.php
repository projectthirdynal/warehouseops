<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerOrderHistory extends Model
{
    use HasUuids;

    // Source Type Constants
    const SOURCE_UPLOAD = 'UPLOAD';
    const SOURCE_LEAD_CONVERSION = 'LEAD_CONVERSION';
    const SOURCE_REORDER = 'REORDER';
    const SOURCE_JNT_IMPORT = 'JNT_IMPORT';

    // Status Constants (aligned with waybill statuses)
    const STATUS_PENDING = 'PENDING';
    const STATUS_DISPATCHED = 'DISPATCHED';
    const STATUS_IN_TRANSIT = 'IN_TRANSIT';
    const STATUS_DELIVERING = 'DELIVERING';
    const STATUS_DELIVERED = 'DELIVERED';
    const STATUS_RETURNED = 'RETURNED';
    const STATUS_CANCELLED = 'CANCELLED';

    /**
     * The table associated with the model.
     */
    protected $table = 'customer_order_history';

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    protected $fillable = [
        'customer_id',
        'waybill_number',
        'source_type',
        'product_name',
        'product_id',
        'weight',
        'declared_value',
        'cod_amount',
        'current_status',
        'status_history',
        'jnt_waybill',
        'jnt_last_sync',
        'jnt_raw_data',
        'lead_id',
        'lead_outcome',
        'lead_agent',
        'waybill_id',
        'delivery_address',
        'city',
        'province',
        'barangay',
        'order_date',
        'shipped_date',
        'delivered_date',
        'returned_date',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'declared_value' => 'decimal:2',
        'cod_amount' => 'decimal:2',
        'status_history' => 'array',
        'jnt_raw_data' => 'array',
        'jnt_last_sync' => 'datetime',
        'order_date' => 'datetime',
        'shipped_date' => 'datetime',
        'delivered_date' => 'datetime',
        'returned_date' => 'datetime',
    ];

    /**
     * Get the customer this order belongs to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the lead this order originated from.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the waybill record.
     */
    public function waybill(): BelongsTo
    {
        return $this->belongsTo(Waybill::class);
    }

    /**
     * Check if order is in a terminal state.
     */
    public function isComplete(): bool
    {
        return in_array($this->current_status, [
            self::STATUS_DELIVERED,
            self::STATUS_RETURNED,
            self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Check if order was successfully delivered.
     */
    public function isDelivered(): bool
    {
        return $this->current_status === self::STATUS_DELIVERED;
    }

    /**
     * Check if order was returned.
     */
    public function isReturned(): bool
    {
        return $this->current_status === self::STATUS_RETURNED;
    }

    /**
     * Check if order is still in transit.
     */
    public function isInTransit(): bool
    {
        return in_array($this->current_status, [
            self::STATUS_DISPATCHED,
            self::STATUS_IN_TRANSIT,
            self::STATUS_DELIVERING,
        ]);
    }

    /**
     * Append a status change to the history.
     */
    public function appendStatusHistory(string $status, ?string $location = null, ?string $notes = null): void
    {
        $history = $this->status_history ?? [];

        $history[] = [
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'location' => $location,
            'notes' => $notes,
        ];

        $this->status_history = $history;
    }

    /**
     * Update status with automatic history tracking.
     */
    public function updateStatus(string $newStatus, ?string $location = null, ?string $notes = null): bool
    {
        $oldStatus = $this->current_status;

        if ($oldStatus === $newStatus) {
            return false;
        }

        $this->current_status = $newStatus;
        $this->appendStatusHistory($newStatus, $location, $notes);

        // Set completion dates based on status
        if ($newStatus === self::STATUS_DELIVERED && !$this->delivered_date) {
            $this->delivered_date = now();
        }

        if ($newStatus === self::STATUS_RETURNED && !$this->returned_date) {
            $this->returned_date = now();
        }

        if (in_array($newStatus, [self::STATUS_DISPATCHED, self::STATUS_IN_TRANSIT]) && !$this->shipped_date) {
            $this->shipped_date = now();
        }

        $this->save();

        return true;
    }

    /**
     * Get the return reason from J&T data if available.
     */
    public function getReturnReasonAttribute(): ?string
    {
        if (!$this->jnt_raw_data) {
            return null;
        }

        return $this->jnt_raw_data['return_reason'] ?? null;
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->current_status) {
            self::STATUS_DELIVERED => '#22c55e',    // Green
            self::STATUS_RETURNED => '#ef4444',     // Red
            self::STATUS_CANCELLED => '#6b7280',    // Gray
            self::STATUS_IN_TRANSIT, self::STATUS_DELIVERING => '#3b82f6', // Blue
            self::STATUS_DISPATCHED => '#8b5cf6',   // Purple
            default => '#eab308',                    // Yellow (pending)
        };
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('current_status', $status);
    }

    /**
     * Scope to filter completed orders (delivered + returned).
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('current_status', [
            self::STATUS_DELIVERED,
            self::STATUS_RETURNED,
        ]);
    }

    /**
     * Scope to filter pending orders (not yet delivered/returned/cancelled).
     */
    public function scopePending($query)
    {
        return $query->whereNotIn('current_status', [
            self::STATUS_DELIVERED,
            self::STATUS_RETURNED,
            self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Scope to filter orders needing J&T sync.
     */
    public function scopeNeedsSync($query)
    {
        return $query->pending()
            ->whereNotNull('jnt_waybill')
            ->where(function ($q) {
                $q->whereNull('jnt_last_sync')
                  ->orWhere('jnt_last_sync', '<', now()->subMinutes(30));
            });
    }
}
