<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasUuids;

    // Risk Level Constants
    const RISK_UNKNOWN = 'UNKNOWN';
    const RISK_LOW = 'LOW';
    const RISK_MEDIUM = 'MEDIUM';
    const RISK_HIGH = 'HIGH';
    const RISK_BLACKLIST = 'BLACKLIST';

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    protected $fillable = [
        'phone_primary',
        'phone_secondary',
        'name_normalized',
        'name_display',
        'primary_address',
        'city',
        'province',
        'barangay',
        'street',
        'total_orders',
        'total_delivered',
        'total_returned',
        'total_pending',
        'total_in_transit',
        'total_order_value',
        'total_delivered_value',
        'total_returned_value',
        'delivery_success_rate',
        'customer_score',
        'risk_level',
        'times_contacted',
        'last_contact_date',
        'last_order_date',
        'last_delivery_date',
        'recycling_eligible',
        'recycling_cooldown_until',
        'first_seen_at',
    ];

    protected $casts = [
        'total_orders' => 'integer',
        'total_delivered' => 'integer',
        'total_returned' => 'integer',
        'total_pending' => 'integer',
        'total_in_transit' => 'integer',
        'total_order_value' => 'decimal:2',
        'total_delivered_value' => 'decimal:2',
        'total_returned_value' => 'decimal:2',
        'delivery_success_rate' => 'decimal:2',
        'customer_score' => 'integer',
        'times_contacted' => 'integer',
        'last_contact_date' => 'datetime',
        'last_order_date' => 'datetime',
        'last_delivery_date' => 'datetime',
        'recycling_eligible' => 'boolean',
        'recycling_cooldown_until' => 'datetime',
        'first_seen_at' => 'datetime',
    ];

    /**
     * Get all leads associated with this customer.
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Get all order history records for this customer.
     */
    public function orderHistory(): HasMany
    {
        return $this->hasMany(CustomerOrderHistory::class)->orderBy('order_date', 'desc');
    }

    /**
     * Get all recycling pool entries for this customer.
     */
    public function recyclingPool(): HasMany
    {
        return $this->hasMany(LeadRecyclingPool::class);
    }

    /**
     * Check if the customer is blacklisted.
     */
    public function isBlacklisted(): bool
    {
        return $this->risk_level === self::RISK_BLACKLIST;
    }

    /**
     * Check if the customer is currently in a cooldown period.
     */
    public function isInCooldown(): bool
    {
        if (!$this->recycling_cooldown_until) {
            return false;
        }

        return $this->recycling_cooldown_until->isFuture();
    }

    /**
     * Check if the customer can be contacted for recycling.
     * Returns true if contactable, or an error message string if not.
     */
    public function canBeContacted(): bool|string
    {
        if ($this->isBlacklisted()) {
            return 'Customer is blacklisted.';
        }

        if (!$this->recycling_eligible) {
            return 'Customer has opted out of contact.';
        }

        if ($this->isInCooldown()) {
            $cooldownEnd = $this->recycling_cooldown_until->diffForHumans();
            return "Customer is in cooldown period until {$cooldownEnd}.";
        }

        return true;
    }

    /**
     * Set a cooldown period for this customer.
     */
    public function setCooldown(int $days): void
    {
        $this->recycling_cooldown_until = now()->addDays($days);
        $this->save();
    }

    /**
     * Mark the customer as blacklisted.
     */
    public function blacklist(): void
    {
        $this->risk_level = self::RISK_BLACKLIST;
        $this->recycling_eligible = false;
        $this->save();
    }

    /**
     * Record a contact attempt.
     */
    public function recordContact(): void
    {
        $this->times_contacted++;
        $this->last_contact_date = now();
        $this->save();
    }

    /**
     * Get the risk level badge color for UI.
     */
    public function getRiskColorAttribute(): string
    {
        return match ($this->risk_level) {
            self::RISK_LOW => '#22c55e',        // Green
            self::RISK_MEDIUM => '#eab308',    // Yellow
            self::RISK_HIGH => '#f97316',      // Orange
            self::RISK_BLACKLIST => '#ef4444', // Red
            default => '#6b7280',              // Gray
        };
    }

    /**
     * Get the customer score badge color for UI.
     */
    public function getScoreColorAttribute(): string
    {
        return match (true) {
            $this->customer_score >= 76 => '#22c55e',  // Green
            $this->customer_score >= 51 => '#eab308',  // Yellow
            $this->customer_score >= 26 => '#f97316',  // Orange
            default => '#ef4444',                       // Red
        };
    }

    /**
     * Scope to filter by risk level.
     */
    public function scopeByRiskLevel($query, string $level)
    {
        return $query->where('risk_level', $level);
    }

    /**
     * Scope to filter contactable customers.
     */
    public function scopeContactable($query)
    {
        return $query
            ->where('recycling_eligible', true)
            ->where('risk_level', '!=', self::RISK_BLACKLIST)
            ->where(function ($q) {
                $q->whereNull('recycling_cooldown_until')
                  ->orWhere('recycling_cooldown_until', '<=', now());
            });
    }

    /**
     * Scope to filter by minimum customer score.
     */
    public function scopeMinScore($query, int $score)
    {
        return $query->where('customer_score', '>=', $score);
    }
}
