<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * ETAP_04 FAZA A: SecurityAlert Model
 *
 * Stores security alerts for admin review and action.
 *
 * @property int $id
 * @property string $alert_type
 * @property string $severity
 * @property string $title
 * @property string $message
 * @property array|null $details
 * @property int|null $related_user_id
 * @property string|null $related_ip
 * @property int|null $related_session_id
 * @property bool $acknowledged
 * @property int|null $acknowledged_by
 * @property \Carbon\Carbon|null $acknowledged_at
 * @property string|null $acknowledgment_notes
 * @property bool $resolved
 * @property int|null $resolved_by
 * @property \Carbon\Carbon|null $resolved_at
 * @property string|null $resolution_notes
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User|null $relatedUser
 * @property-read User|null $acknowledgedByUser
 * @property-read User|null $resolvedByUser
 * @property-read UserSession|null $relatedSession
 */
class SecurityAlert extends Model
{
    protected $table = 'security_alerts';

    protected $fillable = [
        'alert_type',
        'severity',
        'title',
        'message',
        'details',
        'related_user_id',
        'related_ip',
        'related_session_id',
        'acknowledged',
        'acknowledged_by',
        'acknowledged_at',
        'acknowledgment_notes',
        'resolved',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
        'expires_at',
    ];

    protected $casts = [
        'details' => 'array',
        'acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Alert type constants
     */
    public const TYPE_BRUTE_FORCE = 'brute_force';
    public const TYPE_SUSPICIOUS_IP = 'suspicious_ip';
    public const TYPE_MULTIPLE_SESSIONS = 'multiple_sessions';
    public const TYPE_UNUSUAL_LOCATION = 'unusual_location';
    public const TYPE_FAILED_LOGINS = 'failed_logins';
    public const TYPE_PASSWORD_EXPIRED = 'password_expired';
    public const TYPE_ACCOUNT_LOCKED = 'account_locked';
    public const TYPE_CREDENTIAL_STUFFING = 'credential_stuffing';
    public const TYPE_SESSION_HIJACKING = 'session_hijacking';

    /**
     * Severity constants
     */
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the user related to this alert.
     */
    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    /**
     * Get the user who acknowledged this alert.
     */
    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Get the user who resolved this alert.
     */
    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get the related session.
     */
    public function relatedSession(): BelongsTo
    {
        return $this->belongsTo(UserSession::class, 'related_session_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope to unacknowledged alerts only.
     */
    public function scopeUnacknowledged(Builder $query): Builder
    {
        return $query->where('acknowledged', false);
    }

    /**
     * Scope to acknowledged alerts only.
     */
    public function scopeAcknowledged(Builder $query): Builder
    {
        return $query->where('acknowledged', true);
    }

    /**
     * Scope to unresolved alerts only.
     */
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('resolved', false);
    }

    /**
     * Scope to resolved alerts only.
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('resolved', true);
    }

    /**
     * Scope to alerts of specific severity.
     */
    public function scopeSeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to alerts of specific type.
     */
    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Scope to non-expired alerts.
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to critical and high severity alerts.
     */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->whereIn('severity', [self::SEVERITY_CRITICAL, self::SEVERITY_HIGH]);
    }

    // ==========================================
    // STATIC METHODS
    // ==========================================

    /**
     * Create a security alert.
     */
    public static function createAlert(
        string $type,
        string $severity,
        string $title,
        string $message,
        array $details = [],
        ?int $userId = null,
        ?string $ip = null,
        ?int $sessionId = null
    ): static {
        return static::create([
            'alert_type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'details' => $details,
            'related_user_id' => $userId,
            'related_ip' => $ip,
            'related_session_id' => $sessionId,
        ]);
    }

    /**
     * Create a brute force alert.
     */
    public static function bruteForceAlert(string $ip, int $attemptCount): static
    {
        return static::createAlert(
            self::TYPE_BRUTE_FORCE,
            self::SEVERITY_HIGH,
            'Wykryto atak brute force',
            "IP {$ip} wykonal {$attemptCount} nieudanych prob logowania w krotkim czasie.",
            ['ip' => $ip, 'attempt_count' => $attemptCount],
            null,
            $ip
        );
    }

    /**
     * Create a suspicious IP alert.
     */
    public static function suspiciousIpAlert(string $ip, int $userCount): static
    {
        return static::createAlert(
            self::TYPE_SUSPICIOUS_IP,
            self::SEVERITY_HIGH,
            'Podejrzana aktywnosc IP',
            "IP {$ip} uzywane przez {$userCount} roznych uzytkownikow.",
            ['ip' => $ip, 'user_count' => $userCount],
            null,
            $ip
        );
    }

    /**
     * Create a multiple sessions alert.
     */
    public static function multipleSessionsAlert(User $user, int $sessionCount): static
    {
        return static::createAlert(
            self::TYPE_MULTIPLE_SESSIONS,
            $sessionCount > 5 ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM,
            'Wiele aktywnych sesji',
            "Uzytkownik {$user->full_name} ma {$sessionCount} aktywnych sesji.",
            ['user_id' => $user->id, 'session_count' => $sessionCount],
            $user->id
        );
    }

    /**
     * Get count of unacknowledged alerts by severity.
     */
    public static function getUnacknowledgedCount(): array
    {
        return static::unacknowledged()
            ->notExpired()
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();
    }

    // ==========================================
    // INSTANCE METHODS
    // ==========================================

    /**
     * Acknowledge this alert.
     */
    public function acknowledge(?int $userId = null, ?string $notes = null): void
    {
        $this->update([
            'acknowledged' => true,
            'acknowledged_by' => $userId ?? auth()->id(),
            'acknowledged_at' => now(),
            'acknowledgment_notes' => $notes,
        ]);
    }

    /**
     * Resolve this alert.
     */
    public function resolve(?int $userId = null, ?string $notes = null): void
    {
        // Auto-acknowledge if not already
        if (!$this->acknowledged) {
            $this->acknowledge($userId);
        }

        $this->update([
            'resolved' => true,
            'resolved_by' => $userId ?? auth()->id(),
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Get severity badge info.
     */
    public function getSeverityBadge(): array
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => ['label' => 'Krytyczny', 'color' => 'red', 'icon' => 'exclamation-circle'],
            self::SEVERITY_HIGH => ['label' => 'Wysoki', 'color' => 'orange', 'icon' => 'exclamation-triangle'],
            self::SEVERITY_MEDIUM => ['label' => 'Sredni', 'color' => 'yellow', 'icon' => 'exclamation'],
            self::SEVERITY_LOW => ['label' => 'Niski', 'color' => 'blue', 'icon' => 'information-circle'],
            default => ['label' => 'Nieznany', 'color' => 'gray', 'icon' => 'question-mark-circle'],
        };
    }

    /**
     * Get alert type label.
     */
    public function getTypeLabel(): string
    {
        return match ($this->alert_type) {
            self::TYPE_BRUTE_FORCE => 'Atak brute force',
            self::TYPE_SUSPICIOUS_IP => 'Podejrzane IP',
            self::TYPE_MULTIPLE_SESSIONS => 'Wiele sesji',
            self::TYPE_UNUSUAL_LOCATION => 'Nietypowa lokalizacja',
            self::TYPE_FAILED_LOGINS => 'Nieudane logowania',
            self::TYPE_PASSWORD_EXPIRED => 'Haslo wygaslo',
            self::TYPE_ACCOUNT_LOCKED => 'Konto zablokowane',
            self::TYPE_CREDENTIAL_STUFFING => 'Credential stuffing',
            self::TYPE_SESSION_HIJACKING => 'Przechwycenie sesji',
            default => $this->alert_type,
        };
    }
}
