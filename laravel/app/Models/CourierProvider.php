<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourierProvider extends Model
{
    protected $fillable = [
        'code',
        'name',
        'api_key',
        'api_secret',
        'base_url',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get all waybills using this courier provider.
     */
    public function waybills(): HasMany
    {
        return $this->hasMany(Waybill::class);
    }

    /**
     * Check if API credentials are configured.
     */
    public function hasApiCredentials(): bool
    {
        return !empty($this->api_key);
    }

    /**
     * Get the webhook path for this courier.
     */
    public function getWebhookPathAttribute(): ?string
    {
        return $this->settings['webhook_path'] ?? null;
    }

    /**
     * Scope to only active providers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Find provider by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
