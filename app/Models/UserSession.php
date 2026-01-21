<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * ETAP_04 FAZA A: UserSession Model
 *
 * Extended session tracking with device detection, geolocation,
 * and security monitoring capabilities.
 *
 * @property int $id
 * @property int $user_id
 * @property string $session_id
 * @property string $ip_address
 * @property string|null $user_agent
 * @property string $device_type
 * @property string|null $browser
 * @property string|null $browser_version
 * @property string|null $os
 * @property string|null $os_version
 * @property string|null $country
 * @property string|null $country_code
 * @property string|null $city
 * @property string|null $region
 * @property bool $is_active
 * @property bool $is_suspicious
 * @property string|null $suspicious_reason
 * @property \Carbon\Carbon $last_activity
 * @property string|null $last_url
 * @property \Carbon\Carbon|null $ended_at
 * @property string|null $end_reason
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $user
 */
class UserSession extends Model
{
    protected $table = 'user_sessions';

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'browser_version',
        'os',
        'os_version',
        'country',
        'country_code',
        'city',
        'region',
        'is_active',
        'is_suspicious',
        'suspicious_reason',
        'last_activity',
        'last_url',
        'ended_at',
        'end_reason',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_suspicious' => 'boolean',
        'last_activity' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Device type constants
     */
    public const DEVICE_DESKTOP = 'desktop';
    public const DEVICE_MOBILE = 'mobile';
    public const DEVICE_TABLET = 'tablet';
    public const DEVICE_UNKNOWN = 'unknown';

    /**
     * End reason constants
     */
    public const END_LOGOUT = 'logout';
    public const END_TIMEOUT = 'timeout';
    public const END_FORCE_ADMIN = 'force_logout_admin';
    public const END_CONCURRENT = 'concurrent_limit';
    public const END_SECURITY = 'security_block';
    public const END_PASSWORD = 'password_change';
    public const END_BLOCKED = 'user_blocked';
    public const END_BULK = 'bulk_force_logout';
    public const END_EXPIRED = 'session_expired';

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the user that owns the session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get audit logs related to this session.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'session_id', 'session_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope to active sessions only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to inactive sessions only.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to suspicious sessions only.
     */
    public function scopeSuspicious(Builder $query): Builder
    {
        return $query->where('is_suspicious', true);
    }

    /**
     * Scope to a specific user's sessions.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to sessions from a specific IP address.
     */
    public function scopeFromIp(Builder $query, string $ip): Builder
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope to sessions with recent activity.
     */
    public function scopeRecentlyActive(Builder $query, int $minutes = 30): Builder
    {
        return $query->where('last_activity', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope to sessions from a specific device type.
     */
    public function scopeDeviceType(Builder $query, string $type): Builder
    {
        return $query->where('device_type', $type);
    }

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Terminate this session.
     */
    public function terminate(string $reason): void
    {
        $this->update([
            'is_active' => false,
            'ended_at' => now(),
            'end_reason' => $reason,
        ]);
    }

    /**
     * Mark this session as suspicious.
     */
    public function markSuspicious(string $reason): void
    {
        $this->update([
            'is_suspicious' => true,
            'suspicious_reason' => $reason,
        ]);
    }

    /**
     * Clear suspicious flag.
     */
    public function clearSuspicious(): void
    {
        $this->update([
            'is_suspicious' => false,
            'suspicious_reason' => null,
        ]);
    }

    /**
     * Update last activity timestamp.
     */
    public function touchActivity(?string $url = null): void
    {
        $data = ['last_activity' => now()];

        if ($url !== null) {
            $data['last_url'] = $url;
        }

        $this->update($data);
    }

    /**
     * Check if session is current user's session.
     */
    public function isCurrentSession(): bool
    {
        return $this->session_id === session()->getId();
    }

    /**
     * Get session duration in minutes.
     */
    public function getDurationMinutes(): int
    {
        $end = $this->ended_at ?? now();
        return $this->created_at->diffInMinutes($end);
    }

    /**
     * Get formatted device info.
     */
    public function getDeviceInfo(): string
    {
        $parts = [];

        if ($this->browser) {
            $browser = $this->browser;
            if ($this->browser_version) {
                $browser .= ' ' . $this->browser_version;
            }
            $parts[] = $browser;
        }

        if ($this->os) {
            $os = $this->os;
            if ($this->os_version) {
                $os .= ' ' . $this->os_version;
            }
            $parts[] = $os;
        }

        return implode(' / ', $parts) ?: 'Unknown';
    }

    /**
     * Get formatted location info.
     */
    public function getLocationInfo(): string
    {
        $parts = [];

        if ($this->city) {
            $parts[] = $this->city;
        }

        if ($this->region && $this->region !== $this->city) {
            $parts[] = $this->region;
        }

        if ($this->country) {
            $parts[] = $this->country;
        }

        return implode(', ', $parts) ?: 'Unknown location';
    }

    /**
     * Get device type icon path (SVG).
     */
    public function getDeviceIconPath(): string
    {
        return match ($this->device_type) {
            self::DEVICE_MOBILE => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
            self::DEVICE_TABLET => 'M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
            default => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        };
    }

    /**
     * Get status badge info.
     */
    public function getStatusBadge(): array
    {
        if ($this->is_suspicious) {
            return [
                'label' => 'Podejrzana',
                'color' => 'red',
                'icon' => 'exclamation-triangle',
            ];
        }

        if (!$this->is_active) {
            return [
                'label' => 'Zakonczona',
                'color' => 'gray',
                'icon' => 'x-circle',
            ];
        }

        // Check if recently active (last 5 minutes)
        if ($this->last_activity && $this->last_activity->gt(now()->subMinutes(5))) {
            return [
                'label' => 'Aktywna',
                'color' => 'green',
                'icon' => 'check-circle',
            ];
        }

        return [
            'label' => 'Idle',
            'color' => 'yellow',
            'icon' => 'clock',
        ];
    }
}
