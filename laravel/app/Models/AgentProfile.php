<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'max_active_cycles',
        'product_skills',
        'regions',
        'priority_weight',
        'is_available',
        'conversion_rate',
        'avg_calls_per_cycle'
    ];

    protected $casts = [
        'product_skills' => 'array',
        'regions' => 'array',
        'max_active_cycles' => 'integer',
        'priority_weight' => 'float',
        'is_available' => 'boolean',
        'conversion_rate' => 'float',
        'avg_calls_per_cycle' => 'integer'
    ];

    /**
     * Get the user this profile belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if agent has skill for a specific product.
     */
    public function hasSkillFor(?string $product): bool
    {
        if (empty($product) || empty($this->product_skills)) {
            return false;
        }

        $product = strtolower(trim($product));
        
        foreach ($this->product_skills as $skill) {
            if (str_contains($product, strtolower($skill)) || str_contains(strtolower($skill), $product)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if agent covers a specific region.
     */
    public function coversRegion(?string $state, ?string $city = null): bool
    {
        if (empty($this->regions)) {
            return false; // No regions = covers all (handled by caller)
        }

        $regions = array_map('strtolower', $this->regions);

        if ($state && in_array(strtolower($state), $regions)) {
            return true;
        }

        if ($city && in_array(strtolower($city), $regions)) {
            return true;
        }

        return false;
    }

    /**
     * Get current active cycle count for this agent.
     */
    public function getActiveCycleCount(): int
    {
        return LeadCycle::where('agent_id', $this->user_id)
            ->where('status', LeadCycle::STATUS_ACTIVE)
            ->count();
    }

    /**
     * Check if agent has capacity for more cycles.
     */
    public function hasCapacity(): bool
    {
        return $this->getActiveCycleCount() < $this->max_active_cycles;
    }

    /**
     * Get remaining capacity.
     */
    public function getRemainingCapacity(): int
    {
        return max(0, $this->max_active_cycles - $this->getActiveCycleCount());
    }

    /**
     * Calculate load percentage (0-100+).
     */
    public function getLoadPercentage(): float
    {
        if ($this->max_active_cycles === 0) {
            return 100;
        }
        
        return ($this->getActiveCycleCount() / $this->max_active_cycles) * 100;
    }
}
