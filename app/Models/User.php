<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
// use Illuminate\Database\Eloquent\SoftDeletes; // TODO: Add soft deletes migration later

/**
 * PPM User Model - rozszerzony model użytkownika dla systemu PPM
 * 
 * FAZA D: Integration & System Tables
 * 
 * Rozszerza standardowy Laravel User model o:
 * - Spatie Roles & Permissions integration
 * - PPM-specific user fields (company, position, phone, etc.)
 * - UI preferences & notification settings (JSON)
 * - User activity tracking (last_login_at)
 * - Soft deletes dla bezpieczeństwa
 * 
 * @property string $first_name
 * @property string $last_name  
 * @property string $phone
 * @property string $company
 * @property string $position
 * @property bool $is_active
 * @property \Carbon\Carbon $last_login_at
 * @property string $avatar
 * @property string $preferred_language
 * @property string $timezone
 * @property string $date_format
 * @property array $ui_preferences
 * @property array $notification_settings
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles; // SoftDeletes removed for now

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'first_name',
        'last_name',
        'dashboard_refresh_interval',
        'dashboard_widget_preferences',
        'import_column_preferences',
        'phone',
        'company',
        'position',
        'is_active',
        'last_login_at',
        'avatar',
        'preferred_language',
        'timezone',
        'date_format',
        'ui_preferences',
        'notification_settings',
        'email_verified_at',
        'oauth_provider',
        'oauth_id',
        'oauth_email',
        'oauth_provider_data',
        'oauth_avatar_url',
        'oauth_verified',
        'oauth_linked_at',
        'oauth_domain',
        'oauth_last_used_at',
        'oauth_linked_providers',
        'primary_auth_method'
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'oauth_access_token',
        'oauth_refresh_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'ui_preferences' => 'array',
        'notification_settings' => 'array',
        'dashboard_widget_preferences' => 'array',
        'import_column_preferences' => 'array',
        'oauth_provider_data' => 'array',
        'oauth_verified' => 'boolean',
        'oauth_linked_at' => 'datetime',
        'oauth_last_used_at' => 'datetime',
        'oauth_token_expires_at' => 'datetime',
        'oauth_locked_until' => 'datetime',
        'oauth_linked_providers' => 'array',
    ];

    // Removed dates array - no soft deletes for now

    // ==========================================
    // ACCESSORS & MUTATORS
    // ==========================================

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}") ?: $this->name;
    }

    /**
     * Get the user's display name (full name or email).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name ?: $this->email;
    }

    /**
     * Get the user's initials for avatar.
     */
    public function getInitialsAttribute(): string
    {
        $fullName = $this->full_name;
        if (empty($fullName)) {
            return strtoupper(substr($this->email, 0, 2));
        }

        $names = explode(' ', $fullName);
        $initials = '';
        foreach ($names as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }
        
        return substr($initials, 0, 2);
    }

    /**
     * Get the user's avatar URL or default.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        
        // Fallback to Gravatar or default avatar
        $email = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$email}?d=mp&s=150";
    }

    /**
     * Check if user is active.
     */
    public function getIsActiveUserAttribute(): bool
    {
        return $this->is_active; // Removed trashed() check for now
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include users from specific company.
     */
    public function scopeFromCompany($query, string $company)
    {
        return $query->where('company', $company);
    }

    /**
     * Scope a query to only include users with specific role.
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->role($role);
    }

    // ==========================================
    // BUSINESS LOGIC METHODS
    // ==========================================

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Get default UI preferences for new users.
     */
    public static function getDefaultUIPreferences(): array
    {
        return [
            'theme' => 'light',
            'sidebar_collapsed' => false,
            'products_per_page' => 25,
            'default_price_group' => 'retail',
            'default_warehouse' => 'mpptrade',
            'date_format' => 'Y-m-d',
            'timezone' => 'Europe/Warsaw'
        ];
    }

    /**
     * Get default notification settings for new users.
     */
    public static function getDefaultNotificationSettings(): array
    {
        return [
            'email_notifications' => false,
            'sync_notifications' => false,
            'stock_alerts' => false,
            'import_notifications' => false,
            'browser_notifications' => true
        ];
    }

    /**
     * Merge UI preferences with defaults.
     */
    public function getUIPreference(string $key, $default = null)
    {
        $preferences = array_merge(
            self::getDefaultUIPreferences(),
            $this->ui_preferences ?? []
        );

        return $preferences[$key] ?? $default;
    }

    /**
     * Update specific UI preference.
     */
    public function updateUIPreference(string $key, $value): void
    {
        $preferences = $this->ui_preferences ?? [];
        $preferences[$key] = $value;
        
        $this->update(['ui_preferences' => $preferences]);
    }

    /**
     * Check if user has notification enabled.
     */
    public function hasNotificationEnabled(string $type): bool
    {
        $settings = $this->notification_settings ?? [];
        return $settings[$type] ?? false;
    }

    /**
     * Get user timezone or default.
     */
    public function getUserTimezone(): string
    {
        return $this->timezone ?? 'Europe/Warsaw';
    }

    /**
     * Check if user can perform action on resource.
     */
    public function canManage($resource): bool
    {
        // Admin może wszystko
        if ($this->hasRole('Admin')) {
            return true;
        }

        // Manager może zarządzać większością zasobów  
        if ($this->hasRole('Manager')) {
            return in_array($resource, ['products', 'categories', 'prices', 'stock', 'integrations']);
        }

        return false;
    }

    // ==========================================
    // SPATIE PERMISSION HELPER METHODS
    // ==========================================

    /**
     * Check if user has any of the specified roles.
     * Accepts array or pipe-separated string (e.g., 'Admin|Manager').
     */
    public function hasAnyRole(array|string $roles): bool
    {
        if (is_string($roles)) {
            $roles = explode('|', $roles);
        }

        foreach ($roles as $role) {
            if ($this->hasRole(trim($role))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all permissions as collection for easy access.
     */
    public function getAllPermissionsAttribute()
    {
        return $this->getAllPermissions();
    }

    /**
     * Get role names as array for easy access.
     */
    public function getRoleNamesAttribute(): array
    {
        return $this->roles->pluck('name')->toArray();
    }

    /**
     * Check if user has specific permission with role fallback.
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // Admin always has all permissions
        if ($this->hasRole('Admin')) {
            return true;
        }

        // Use parent Spatie method for regular check
        return parent::hasPermissionTo($permission, $guardName);
    }

    /**
     * Scope query by permission.
     */
    public function scopeByPermission($query, string $permission)
    {
        return $query->permission($permission);
    }

    /**
     * Check if user can access admin panel.
     */
    public function canAccessAdmin(): bool
    {
        return $this->hasAnyRole(['Admin']);
    }

    /**
     * Check if user can access manager panel.
     */
    public function canAccessManager(): bool
    {
        return $this->hasAnyRole(['Admin', 'Manager']);
    }

    /**
     * Check if user can access editor features.
     */
    public function canAccessEditor(): bool
    {
        return $this->hasAnyRole(['Admin', 'Manager', 'Editor']);
    }

    /**
     * Check if user can access API.
     */
    public function canAccessAPI(): bool
    {
        return $this->hasPermissionTo('api.access') || $this->hasRole('Admin');
    }

    /**
     * Get user's highest role level (for hierarchy).
     */
    public function getHighestRoleLevel(): int
    {
        $roleHierarchy = [
            'Admin' => 7,
            'Manager' => 6, 
            'Editor' => 5,
            'Warehouseman' => 4,
            'Salesperson' => 3,
            'Claims' => 2,
            'User' => 1
        ];

        $userRoles = $this->getRoleNames();
        $maxLevel = 0;

        foreach ($userRoles as $role) {
            if (isset($roleHierarchy[$role]) && $roleHierarchy[$role] > $maxLevel) {
                $maxLevel = $roleHierarchy[$role];
            }
        }

        return $maxLevel;
    }

    /**
     * Get user's primary role (highest in hierarchy).
     */
    public function getPrimaryRole(): string
    {
        $roleHierarchy = ['Admin', 'Manager', 'Editor', 'Warehouseman', 'Salesperson', 'Claims', 'User'];
        $userRoles = $this->getRoleNames(); // Returns Collection

        foreach ($roleHierarchy as $role) {
            if ($userRoles->contains($role)) {
                return $role;
            }
        }

        return 'User';
    }

    // ==========================================
    // OAUTH2 METHODS
    // ==========================================

    /**
     * Check if user is OAuth authenticated.
     */
    public function isOAuthUser(): bool
    {
        return !empty($this->oauth_provider) && !empty($this->oauth_id);
    }

    /**
     * Check if user is verified OAuth user.
     */
    public function isVerifiedOAuthUser(): bool
    {
        return $this->isOAuthUser() && $this->oauth_verified;
    }

    /**
     * Get OAuth provider display name.
     */
    public function getOAuthProviderDisplayName(): string
    {
        return match($this->oauth_provider) {
            'google' => 'Google Workspace',
            'microsoft' => 'Microsoft Entra ID',
            'github' => 'GitHub',
            'linkedin' => 'LinkedIn',
            default => ucfirst($this->oauth_provider ?? 'Unknown')
        };
    }

    /**
     * Get OAuth avatar URL or fallback.
     */
    public function getOAuthAvatarAttribute(): string
    {
        if ($this->oauth_avatar_url) {
            return $this->oauth_avatar_url;
        }
        
        return $this->getAvatarUrlAttribute();
    }

    /**
     * Check if OAuth token is expired.
     */
    public function isOAuthTokenExpired(): bool
    {
        if (!$this->oauth_token_expires_at) {
            return false; // No expiration date = never expires
        }
        
        return $this->oauth_token_expires_at->isPast();
    }

    /**
     * Check if user can link OAuth provider.
     */
    public function canLinkOAuthProvider(string $provider): bool
    {
        // Check if provider is already linked
        $linkedProviders = $this->oauth_linked_providers ?? [];
        
        return !in_array($provider, $linkedProviders);
    }

    /**
     * Link OAuth provider to user account.
     */
    public function linkOAuthProvider(string $provider, array $providerData): void
    {
        $linkedProviders = $this->oauth_linked_providers ?? [];
        
        if (!in_array($provider, $linkedProviders)) {
            $linkedProviders[] = $provider;
        }
        
        // If this is the first OAuth provider, set as primary
        if (empty($this->oauth_provider)) {
            $this->oauth_provider = $provider;
            $this->primary_auth_method = $provider;
        }
        
        $this->update([
            'oauth_linked_providers' => $linkedProviders,
            'oauth_linked_at' => now(),
            'oauth_provider_data' => array_merge(
                $this->oauth_provider_data ?? [],
                [$provider => $providerData]
            )
        ]);
    }

    /**
     * Unlink OAuth provider from user account.
     */
    public function unlinkOAuthProvider(string $provider): void
    {
        $linkedProviders = $this->oauth_linked_providers ?? [];
        $linkedProviders = array_filter($linkedProviders, fn($p) => $p !== $provider);
        
        $providerData = $this->oauth_provider_data ?? [];
        unset($providerData[$provider]);
        
        // If removing primary provider, set to local or first available
        $updates = [
            'oauth_linked_providers' => array_values($linkedProviders),
            'oauth_provider_data' => $providerData
        ];
        
        if ($this->oauth_provider === $provider) {
            $updates['oauth_provider'] = !empty($linkedProviders) ? $linkedProviders[0] : null;
            $updates['primary_auth_method'] = !empty($linkedProviders) ? $linkedProviders[0] : 'local';
            
            // Clear OAuth fields if no providers left
            if (empty($linkedProviders)) {
                $updates = array_merge($updates, [
                    'oauth_id' => null,
                    'oauth_email' => null,
                    'oauth_avatar_url' => null,
                    'oauth_verified' => false,
                    'oauth_domain' => null
                ]);
            }
        }
        
        $this->update($updates);
    }

    /**
     * Update OAuth login activity.
     */
    public function updateOAuthActivity(): void
    {
        $this->update([
            'oauth_last_used_at' => now(),
            'oauth_login_attempts' => 0 // Reset failed attempts on successful login
        ]);
    }

    /**
     * Increment OAuth login attempts.
     */
    public function incrementOAuthAttempts(): void
    {
        $attempts = $this->oauth_login_attempts + 1;
        $updates = ['oauth_login_attempts' => $attempts];
        
        // Lock account after 5 failed attempts for 30 minutes
        if ($attempts >= 5) {
            $updates['oauth_locked_until'] = now()->addMinutes(30);
        }
        
        $this->update($updates);
    }

    /**
     * Check if OAuth account is locked.
     */
    public function isOAuthLocked(): bool
    {
        if (!$this->oauth_locked_until) {
            return false;
        }
        
        if ($this->oauth_locked_until->isPast()) {
            // Unlock account if lock period has passed
            $this->update([
                'oauth_locked_until' => null,
                'oauth_login_attempts' => 0
            ]);
            return false;
        }
        
        return true;
    }

    /**
     * Get OAuth domain for workspace verification.
     */
    public function getOAuthDomainAttribute(): ?string
    {
        if ($this->oauth_email) {
            return substr(strrchr($this->oauth_email, "@"), 1);
        }
        
        return $this->oauth_domain;
    }

    /**
     * Check if OAuth domain is allowed.
     */
    public function isOAuthDomainAllowed(): bool
    {
        $allowedDomains = config('services.oauth.allowed_domains', []);
        
        if (empty($allowedDomains)) {
            return true; // No domain restrictions
        }
        
        $userDomain = $this->getOAuthDomainAttribute();
        
        return in_array($userDomain, $allowedDomains);
    }

    /**
     * Scope query to OAuth users only.
     */
    public function scopeOAuthUsers($query)
    {
        return $query->whereNotNull('oauth_provider')
                    ->whereNotNull('oauth_id');
    }

    /**
     * Scope query to verified OAuth users only.
     */
    public function scopeVerifiedOAuthUsers($query)
    {
        return $query->oAuthUsers()
                    ->where('oauth_verified', true);
    }

    /**
     * Scope query by OAuth provider.
     */
    public function scopeByOAuthProvider($query, string $provider)
    {
        return $query->where('oauth_provider', $provider);
    }

    /**
     * Scope query by primary auth method.
     */
    public function scopeByAuthMethod($query, string $method)
    {
        return $query->where('primary_auth_method', $method);
    }

    // ==========================================
    // RELATIONSHIPS (future extension)
    // ==========================================

    /**
     * Get user's audit logs.
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get user's sessions.
     */
    public function sessions()
    {
        return $this->hasMany(UserSession::class);
    }
}
