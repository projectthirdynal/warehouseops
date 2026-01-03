<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    const STATUS_NEW = 'NEW';
    const STATUS_CALLING = 'CALLING';
    const STATUS_NO_ANSWER = 'NO_ANSWER';
    const STATUS_REJECT = 'REJECT';
    const STATUS_CALLBACK = 'CALLBACK';
    const STATUS_SALE = 'SALE';
    const STATUS_REORDER = 'REORDER';
    const STATUS_DELIVERED = 'DELIVERED';
    const STATUS_RETURNED = 'RETURNED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_ARCHIVED = 'ARCHIVED';

    protected $fillable = [
        'name',
        'phone',
        'address',
        'city',
        'state',
        'barangay',
        'street',
        'status',
        'source',
        'assigned_to',
        'uploaded_by',
        'original_agent_id',
        'last_called_at',
        'call_attempts',
        'notes',
        'signing_time',
        'submission_time',
        'submitted_at',
        'product_name',
        'product_brand',
        'previous_item',
        'amount',
        'assigned_at',
        'total_cycles',
        'max_cycles',
        'is_exhausted',
        'quality_score',
        'last_scored_at'
    ];

    protected $casts = [
        'last_called_at' => 'datetime',
        'call_attempts' => 'integer',
        'assigned_to' => 'integer',
        'uploaded_by' => 'integer',
        'signing_time' => 'datetime',
        'submission_time' => 'datetime',
        'assigned_at' => 'datetime',
        'total_cycles' => 'integer',
        'max_cycles' => 'integer',
        'is_exhausted' => 'boolean',
        'quality_score' => 'integer',
        'last_scored_at' => 'datetime'
    ];

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LeadLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get all cycles for this lead.
     */
    public function cycles(): HasMany
    {
        return $this->hasMany(LeadCycle::class)->orderBy('cycle_number', 'desc');
    }

    public function leadCycles(): HasMany
    {
        return $this->cycles();
    }

    /**
     * Get the currently active cycle.
     */
    public function activeCycle()
    {
        return $this->hasOne(LeadCycle::class)->where('status', LeadCycle::STATUS_ACTIVE)->latest();
    }

    /**
     * Get all waybills linked to this lead.
     */
    public function waybills(): HasMany
    {
        return $this->hasMany(Waybill::class);
    }

    public function isLocked(): bool
    {
        return in_array($this->status, [self::STATUS_SALE, self::STATUS_DELIVERED]);
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, [self::STATUS_SALE, self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->orderBy('created_at', 'desc');
    }

    /**
     * Check if this lead can be recycled.
     * Returns true if recyclable, or an error message string if not.
     */
    public function canRecycle(): bool|string
    {
        // Check if already exhausted
        if ($this->is_exhausted) {
            return 'Lead has exceeded maximum recycle attempts.';
        }

        if ($this->activeCycle) {
            return 'Lead is currently active in a cycle.';
        }

        // Check for active waybills (not delivered or cancelled)
        $activeWaybill = $this->waybills()
            ->whereNotIn('status', ['DELIVERED', 'CANCELLED', 'RETURNED'])
            ->exists();
        
        if ($activeWaybill) {
            return 'Lead has an active waybill in transit.';
        }

        // Check cooldown period (12 hours since last cycle closed)
        $lastCycle = $this->cycles()->whereNotNull('closed_at')->first();
        if ($lastCycle && $lastCycle->closed_at->diffInHours(now()) < 12) {
            return 'Lead is in cooldown period. Try again after ' . $lastCycle->closed_at->addHours(12)->diffForHumans();
        }

        return true;
    }

    /**
     * Increment cycle count and check exhaustion.
     */
    public function incrementCycleCount(): void
    {
        $this->total_cycles++;
        
        if ($this->total_cycles >= $this->max_cycles) {
            $this->is_exhausted = true;
        }
        
        $this->save();
    }

    /**
     * Get history statistics for the lead.
     * Uses waybills as specific "Orders" record from J&T.
     */
    public function getHistoryAttribute()
    {
        // Use eager loaded waybills if available to avoid N+1
        $waybills = $this->relationLoaded('waybills') ? $this->waybills : $this->waybills()->get();
        
        $total = $waybills->count();
        if ($total === 0) return null;

        // Count statuses (case-insensitive check)
        $completed = $waybills->filter(fn($w) => in_array(strtolower($w->status), ['delivered', 'completed', 'success']))->count();
        $returned = $waybills->filter(fn($w) => strtolower($w->status) === 'returned')->count();
        
        $successRate = round(($completed / $total) * 100);
        
        return [
            'total' => $total,
            'completed' => $completed,
            'returned' => $returned,
            'rate' => $successRate,
            'class' => $successRate >= 80 ? 'success' : ($successRate >= 50 ? 'warning' : 'danger')
        ];
    }
}
