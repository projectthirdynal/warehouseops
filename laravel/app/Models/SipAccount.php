<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SipAccount extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'sip_server',
        'ws_server',
        'username',
        'password',
        'display_name',
        'outbound_proxy',
        'realm',
        'is_active',
        'is_default',
        'options',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'options' => 'array',
    ];

    protected $hidden = [
        'password', // Never expose in JSON
    ];

    // -- Relationships --

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -- Accessors & Mutators --

    /**
     * Encrypt password when setting
     */
    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt password when getting
     */
    public function getPasswordAttribute($value): ?string
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    // -- Scopes --

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('user_id');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhereNull('user_id'); // Include global accounts
        });
    }

    // -- Methods --

    /**
     * Check if this is a global (shared) account
     */
    public function isGlobal(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Get SIP.js compatible configuration
     */
    public function toSipConfig(): array
    {
        return [
            'uri' => "sip:{$this->username}@" . parse_url($this->sip_server, PHP_URL_HOST),
            'wsServer' => $this->ws_server,
            'authorizationUsername' => $this->username,
            'authorizationPassword' => $this->password,
            'displayName' => $this->display_name ?? $this->username,
            'realm' => $this->realm,
            'outboundProxy' => $this->outbound_proxy,
            'options' => $this->options ?? [],
        ];
    }

    /**
     * Get the effective SIP account for a user
     * Returns user's personal account if exists, otherwise global default
     */
    public static function getForUser(?int $userId): ?self
    {
        // First try user's personal account
        if ($userId) {
            $personal = self::where('user_id', $userId)
                ->where('is_active', true)
                ->first();
            
            if ($personal) {
                return $personal;
            }
        }

        // Fall back to global default
        return self::whereNull('user_id')
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }
}
