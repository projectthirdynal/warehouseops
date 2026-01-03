<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        self::ROLE_AGENT => ['dashboard', 'accounts', 'leads_view'],
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

