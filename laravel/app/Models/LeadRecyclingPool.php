<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadRecyclingPool extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'lead_recycling_pool';

    // Pool status constants
    const STATUS_AVAILABLE = 'AVAILABLE';
    const STATUS_ASSIGNED = 'ASSIGNED';
    const STATUS_CONVERTED = 'CONVERTED';
    const STATUS_EXPIRED = 'EXPIRED';
    const STATUS_EXHAUSTED = 'EXHAUSTED';

    // Recycle reason constants
    const REASON_RETURNED_DELIVERABLE = 'RETURNED_DELIVERABLE'; // Unreachable/wrong address
    const REASON_RETURNED_REFUSED = 'RETURNED_REFUSED'; // Customer refused
    const REASON_RETURNED_OTHER = 'RETURNED_OTHER'; // Other return reason
    const REASON_NO_ANSWER_RETRY = 'NO_ANSWER_RETRY'; // No answer on previous attempts
    const REASON_SCHEDULED_CALLBACK = 'SCHEDULED_CALLBACK'; // Customer requested callback
    const REASON_REORDER_CANDIDATE = 'REORDER_CANDIDATE'; // Previous successful customer

    protected $fillable = [
        'customer_id',
        'source_waybill',
        'source_lead_id',
        'original_outcome',
        'recycle_reason',
        'recycle_count',
        'priority_score',
        'available_from',
        'expires_at',
        'assigned_to',
        'assigned_at',
        'pool_status',
        'processed_at',
        'processed_outcome',
    ];

    protected $casts = [
        'available_from' => 'datetime',
        'expires_at' => 'datetime',
        'assigned_at' => 'datetime',
        'processed_at' => 'datetime',
        'recycle_count' => 'integer',
        'priority_score' => 'integer',
    ];

    /**
     * No auto-incrementing ID (using UUID)
     */
    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Relationships
     */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function sourceLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'source_lead_id');
    }

    /**
     * Scopes
     */

    public function scopeAvailable($query)
    {
        return $query->where('pool_status', self::STATUS_AVAILABLE)
            ->where('available_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeByPriority($query, string $order = 'desc')
    {
        return $query->orderBy('priority_score', $order);
    }

    public function scopeByReason($query, string $reason)
    {
        return $query->where('recycle_reason', $reason);
    }

    public function scopeAssignedTo($query, int $agentId)
    {
        return $query->where('assigned_to', $agentId)
            ->where('pool_status', self::STATUS_ASSIGNED);
    }

    public function scopeExpired($query)
    {
        return $query->where('pool_status', self::STATUS_AVAILABLE)
            ->where('expires_at', '<=', now());
    }

    public function scopeStaleAssignments($query, int $hours = 24)
    {
        return $query->where('pool_status', self::STATUS_ASSIGNED)
            ->where('assigned_at', '<', now()->subHours($hours));
    }

    /**
     * Helper methods
     */

    public function isAvailable(): bool
    {
        return $this->pool_status === self::STATUS_AVAILABLE
            && $this->available_from <= now()
            && ($this->expires_at === null || $this->expires_at > now());
    }

    public function isAssigned(): bool
    {
        return $this->pool_status === self::STATUS_ASSIGNED;
    }

    public function isProcessed(): bool
    {
        return in_array($this->pool_status, [
            self::STATUS_CONVERTED,
            self::STATUS_EXPIRED,
            self::STATUS_EXHAUSTED
        ]);
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at <= now();
    }

    public function assignTo(User $agent): void
    {
        $this->update([
            'assigned_to' => $agent->id,
            'assigned_at' => now(),
            'pool_status' => self::STATUS_ASSIGNED
        ]);
    }

    public function markAsConverted(string $outcome): void
    {
        $this->update([
            'pool_status' => self::STATUS_CONVERTED,
            'processed_at' => now(),
            'processed_outcome' => $outcome
        ]);
    }

    public function markAsExhausted(string $outcome): void
    {
        $this->update([
            'pool_status' => self::STATUS_EXHAUSTED,
            'processed_at' => now(),
            'processed_outcome' => $outcome
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'pool_status' => self::STATUS_EXPIRED,
            'processed_at' => now()
        ]);
    }

    public function releaseAssignment(): void
    {
        $this->update([
            'assigned_to' => null,
            'assigned_at' => null,
            'pool_status' => self::STATUS_AVAILABLE
        ]);
    }

    /**
     * Get priority color for UI
     */
    public function getPriorityColorAttribute(): string
    {
        if ($this->priority_score >= 70) {
            return '#22c55e'; // Green - High priority
        } elseif ($this->priority_score >= 40) {
            return '#eab308'; // Yellow - Medium priority
        } else {
            return '#f97316'; // Orange - Low priority
        }
    }

    /**
     * Get reason label for UI
     */
    public function getReasonLabelAttribute(): string
    {
        return match ($this->recycle_reason) {
            self::REASON_RETURNED_DELIVERABLE => 'Returned - Delivery Issue',
            self::REASON_RETURNED_REFUSED => 'Returned - Customer Refused',
            self::REASON_RETURNED_OTHER => 'Returned - Other',
            self::REASON_NO_ANSWER_RETRY => 'No Answer - Retry',
            self::REASON_SCHEDULED_CALLBACK => 'Scheduled Callback',
            self::REASON_REORDER_CANDIDATE => 'Reorder Candidate',
            default => 'Unknown'
        };
    }
}
