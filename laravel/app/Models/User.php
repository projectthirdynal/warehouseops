<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Role constants
    const ROLE_SUPERADMIN = 'superadmin';
    const ROLE_ADMIN = 'admin';
    const ROLE_OPERATOR = 'operator';
    const ROLE_AGENT = 'agent';

    const PERMISSIONS = [
        self::ROLE_SUPERADMIN => ['dashboard', 'scanner', 'pending', 'upload', 'accounts', 'settings', 'users', 'leads_view', 'leads_manage', 'leads_create'],
        self::ROLE_ADMIN => ['dashboard', 'scanner', 'pending', 'upload', 'accounts', 'settings', 'leads_view', 'leads_manage', 'users', 'leads_create'],
        self::ROLE_OPERATOR => ['dashboard', 'scanner', 'pending', 'upload', 'accounts'],
        self::ROLE_AGENT => ['accounts', 'leads_view'],
    ];

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the agent profile for this user.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(AgentProfile::class);
    }

    /**
     * Get all lead cycles assigned to this agent.
     */
    public function leadCycles(): HasMany
    {
        return $this->hasMany(LeadCycle::class, 'agent_id');
    }

    /**
     * Get the SIP account for this user.
     */
    public function sipAccount(): HasOne
    {
        return $this->hasOne(SipAccount::class);
    }

    /**
     * Get or create agent profile.
     */
    public function getOrCreateProfile(): AgentProfile
    {
        if (!$this->profile) {
            return AgentProfile::create([
                'user_id' => $this->id,
                'max_active_cycles' => 10,
                'product_skills' => [],
                'regions' => [],
                'priority_weight' => 1.0,
                'is_available' => true
            ]);
        }
        
        return $this->profile;
    }

    /**
     * Get active cycle count.
     */
    public function getActiveCycleCount(): int
    {
        return $this->leadCycles()
            ->where('status', LeadCycle::STATUS_ACTIVE)
            ->count();
    }

    /**
     * Check if agent has capacity for more cycles.
     */
    public function hasCapacity(): bool
    {
        $profile = $this->profile;
        if (!$profile) {
            return true; // No profile = unlimited capacity
        }
        
        return $this->getActiveCycleCount() < $profile->max_active_cycles;
    }

    /**
     * Check if agent is available for distribution.
     */
    public function isAvailableForDistribution(): bool
    {
        if (!$this->is_active || $this->role !== self::ROLE_AGENT) {
            return false;
        }

        $profile = $this->profile;
        if ($profile && !$profile->is_available) {
            return false;
        }

        return $this->hasCapacity();
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user is superadmin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isAgent(): bool
    {
        return $this->role === self::ROLE_AGENT;
    }

    public function isChecker(): bool
    {
        return $this->role === 'checker';
    }

    /**
     * Check if user can access a specific feature
     */
    public function canAccess(string $feature): bool
    {
        $permissions = self::PERMISSIONS[$this->role] ?? [];
        return in_array($feature, $permissions);
    }

    /**
     * Get all available roles
     */
    public static function getRoles(): array
    {
        return [
            self::ROLE_SUPERADMIN => 'Super Admin',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_OPERATOR => 'Operator',
            self::ROLE_AGENT => 'Agent',
        ];
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayName(): string
    {
        return self::getRoles()[$this->role] ?? 'Unknown';
    }
}
