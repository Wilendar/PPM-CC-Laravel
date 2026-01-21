<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * ETAP_04 FAZA A: LoginAttempt Model
 *
 * Tracks all login attempts for security monitoring,
 * brute force detection, and audit compliance.
 *
 * @property int $id
 * @property string $email
 * @property string $ip_address
 * @property string|null $user_agent
 * @property bool $success
 * @property string|null $failure_reason
 * @property int|null $user_id
 * @property string|null $device_type
 * @property string|null $browser
 * @property string|null $country
 * @property string|null $city
 * @property string|null $oauth_provider
 * @property \Carbon\Carbon $attempted_at
 *
 * @property-read User|null $user
 */
class LoginAttempt extends Model
{
    protected $table = 'login_attempts';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'success',
        'failure_reason',
        'user_id',
        'device_type',
        'browser',
        'country',
        'city',
        'oauth_provider',
        'attempted_at',
    ];

    protected $casts = [
        'success' => 'boolean',
        'attempted_at' => 'datetime',
    ];

    /**
     * Common failure reasons
     */
    public const FAILURE_INVALID_CREDENTIALS = 'invalid_credentials';
    public const FAILURE_USER_NOT_FOUND = 'user_not_found';
    public const FAILURE_USER_INACTIVE = 'user_inactive';
    public const FAILURE_USER_LOCKED = 'user_locked';
    public const FAILURE_PASSWORD_EXPIRED = 'password_expired';
    public const FAILURE_OAUTH_ERROR = 'oauth_error';
    public const FAILURE_IP_BLOCKED = 'ip_blocked';
    public const FAILURE_TOO_MANY_ATTEMPTS = 'too_many_attempts';

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the user associated with the attempt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope to successful attempts only.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('success', true);
    }

    /**
     * Scope to failed attempts only.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('success', false);
    }

    /**
     * Scope to attempts for a specific email.
     */
    public function scopeForEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    /**
     * Scope to attempts from a specific IP.
     */
    public function scopeFromIp(Builder $query, string $ip): Builder
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope to attempts within a time period.
     */
    public function scopeWithinPeriod(Builder $query, int $minutes): Builder
    {
        return $query->where('attempted_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope to recent attempts (last 24 hours).
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->where('attempted_at', '>=', now()->subDay());
    }

    /**
     * Scope to today's attempts.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('attempted_at', today());
    }

    // ==========================================
    // STATIC METHODS
    // ==========================================

    /**
     * Record a login attempt.
     */
    public static function record(array $data): static
    {
        return static::create(array_merge($data, [
            'attempted_at' => now(),
        ]));
    }

    /**
     * Record a successful login.
     */
    public static function recordSuccess(string $email, string $ip, ?int $userId = null, array $extra = []): static
    {
        return static::record(array_merge([
            'email' => $email,
            'ip_address' => $ip,
            'success' => true,
            'user_id' => $userId,
        ], $extra));
    }

    /**
     * Record a failed login.
     */
    public static function recordFailure(string $email, string $ip, string $reason, ?int $userId = null, array $extra = []): static
    {
        return static::record(array_merge([
            'email' => $email,
            'ip_address' => $ip,
            'success' => false,
            'failure_reason' => $reason,
            'user_id' => $userId,
        ], $extra));
    }

    /**
     * Count recent failed attempts for email.
     */
    public static function countRecentFailures(string $email, int $minutes = 30): int
    {
        return static::forEmail($email)
            ->failed()
            ->withinPeriod($minutes)
            ->count();
    }

    /**
     * Count recent failed attempts for IP.
     */
    public static function countRecentFailuresFromIp(string $ip, int $minutes = 30): int
    {
        return static::fromIp($ip)
            ->failed()
            ->withinPeriod($minutes)
            ->count();
    }

    /**
     * Get IPs with most failed attempts in period.
     */
    public static function getSuspiciousIps(int $minutes = 60, int $threshold = 10): \Illuminate\Support\Collection
    {
        return static::failed()
            ->withinPeriod($minutes)
            ->selectRaw('ip_address, COUNT(*) as attempt_count')
            ->groupBy('ip_address')
            ->havingRaw('COUNT(*) >= ?', [$threshold])
            ->orderByDesc('attempt_count')
            ->get();
    }

    // ==========================================
    // INSTANCE METHODS
    // ==========================================

    /**
     * Get failure reason label.
     */
    public function getFailureReasonLabel(): ?string
    {
        if ($this->success || !$this->failure_reason) {
            return null;
        }

        return match ($this->failure_reason) {
            self::FAILURE_INVALID_CREDENTIALS => 'Nieprawidlowe dane logowania',
            self::FAILURE_USER_NOT_FOUND => 'Uzytkownik nie znaleziony',
            self::FAILURE_USER_INACTIVE => 'Konto nieaktywne',
            self::FAILURE_USER_LOCKED => 'Konto zablokowane',
            self::FAILURE_PASSWORD_EXPIRED => 'Haslo wygaslo',
            self::FAILURE_OAUTH_ERROR => 'Blad OAuth',
            self::FAILURE_IP_BLOCKED => 'IP zablokowane',
            self::FAILURE_TOO_MANY_ATTEMPTS => 'Zbyt wiele prob',
            default => $this->failure_reason,
        };
    }
}
