<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * ETAP_04: BlockedIp Model
 *
 * Stores blocked IP addresses for security enforcement.
 *
 * @property int $id
 * @property string $ip_address
 * @property string|null $reason
 * @property int|null $blocked_by
 * @property \Carbon\Carbon|null $expires_at
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User|null $blocker
 */
class BlockedIp extends Model
{
    protected $table = 'blocked_ips';

    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_by',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the user who blocked this IP.
     */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope to active (non-expired) blocked IPs.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    // ==========================================
    // STATIC METHODS
    // ==========================================

    /**
     * Check if an IP address is currently blocked.
     */
    public static function isBlocked(string $ip): bool
    {
        return static::active()->where('ip_address', $ip)->exists();
    }
}
