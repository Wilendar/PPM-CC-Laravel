<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * OAuth Audit Log Model
 * 
 * FAZA D: OAuth2 + Advanced Features
 * Model dla specialized OAuth2 audit logging with security tracking
 * 
 * Features:
 * - OAuth-specific event tracking
 * - Security incident detection
 * - Compliance reporting (GDPR, etc.)
 * - Performance monitoring
 * - Retention policy management
 * - Suspicious activity flagging
 * 
 * @property int $id
 * @property int|null $user_id
 * @property string $oauth_provider
 * @property string $oauth_action
 * @property string $oauth_event_type
 * @property string|null $oauth_session_id
 * @property string|null $oauth_state
 * @property string|null $oauth_client_id
 * @property string|null $oauth_redirect_uri
 * @property string|null $oauth_email
 * @property string|null $oauth_domain
 * @property string|null $oauth_external_id
 * @property string $ip_address
 * @property string|null $user_agent
 * @property array|null $oauth_request_data
 * @property array|null $oauth_response_data
 * @property array|null $oauth_token_info
 * @property array|null $oauth_profile_data
 * @property array|null $oauth_permissions
 * @property string $security_level
 * @property array|null $security_indicators
 * @property string|null $compliance_category
 * @property bool $requires_review
 * @property string $status
 * @property string|null $error_message
 * @property string|null $error_code
 * @property int $attempt_number
 * @property Carbon|null $oauth_initiated_at
 * @property Carbon|null $oauth_completed_at
 * @property int|null $processing_time_ms
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $archived_at
 * @property string $retention_policy
 * @property bool $is_sensitive
 * @property User|null $user
 */
class OAuthAuditLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'oauth_audit_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'oauth_provider',
        'oauth_action',
        'oauth_event_type',
        'oauth_session_id',
        'oauth_state',
        'oauth_client_id',
        'oauth_redirect_uri',
        'oauth_email',
        'oauth_domain',
        'oauth_external_id',
        'ip_address',
        'user_agent',
        'oauth_request_data',
        'oauth_response_data',
        'oauth_token_info',
        'oauth_profile_data',
        'oauth_permissions',
        'security_level',
        'security_indicators',
        'compliance_category',
        'requires_review',
        'status',
        'error_message',
        'error_code',
        'attempt_number',
        'oauth_initiated_at',
        'oauth_completed_at',
        'processing_time_ms',
        'archived_at',
        'retention_policy',
        'is_sensitive',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'user_id' => 'integer',
        'oauth_request_data' => 'array',
        'oauth_response_data' => 'array',
        'oauth_token_info' => 'array',
        'oauth_profile_data' => 'array',
        'oauth_permissions' => 'array',
        'security_indicators' => 'array',
        'requires_review' => 'boolean',
        'attempt_number' => 'integer',
        'processing_time_ms' => 'integer',
        'oauth_initiated_at' => 'datetime',
        'oauth_completed_at' => 'datetime',
        'archived_at' => 'datetime',
        'is_sensitive' => 'boolean',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the user associated with this audit log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope query to specific OAuth provider.
     */
    public function scopeForProvider(Builder $query, string $provider): Builder
    {
        return $query->where('oauth_provider', $provider);
    }

    /**
     * Scope query to specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope query to specific action.
     */
    public function scopeForAction(Builder $query, string $action): Builder
    {
        return $query->where('oauth_action', $action);
    }

    /**
     * Scope query to specific event type.
     */
    public function scopeForEventType(Builder $query, string $eventType): Builder
    {
        return $query->where('oauth_event_type', $eventType);
    }

    /**
     * Scope query to specific status.
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope query to failed attempts.
     */
    public function scopeFailedAttempts(Builder $query): Builder
    {
        return $query->where('status', 'failure');
    }

    /**
     * Scope query to successful attempts.
     */
    public function scopeSuccessfulAttempts(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope query to suspicious activity.
     */
    public function scopeSuspiciousActivity(Builder $query): Builder
    {
        return $query->whereIn('security_level', ['suspicious', 'critical'])
                    ->orWhere('requires_review', true);
    }

    /**
     * Scope query to security incidents.
     */
    public function scopeSecurityIncidents(Builder $query): Builder
    {
        return $query->where('security_level', 'critical')
                    ->where('requires_review', true);
    }

    /**
     * Scope query to specific domain.
     */
    public function scopeForDomain(Builder $query, string $domain): Builder
    {
        return $query->where('oauth_domain', $domain);
    }

    /**
     * Scope query to date range.
     */
    public function scopeDateRange(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Scope query to recent logs (last 24 hours).
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope query to logs requiring review.
     */
    public function scopeRequiringReview(Builder $query): Builder
    {
        return $query->where('requires_review', true)
                    ->whereNull('archived_at');
    }

    /**
     * Scope query to compliance-related logs.
     */
    public function scopeForCompliance(Builder $query, ?string $category = null): Builder
    {
        $baseQuery = $query->whereNotNull('compliance_category');
        
        if ($category) {
            $baseQuery->where('compliance_category', $category);
        }
        
        return $baseQuery;
    }

    /**
     * Scope query to sensitive data logs.
     */
    public function scopeSensitiveData(Builder $query): Builder
    {
        return $query->where('is_sensitive', true);
    }

    /**
     * Scope query to non-archived logs.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope query to archived logs.
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    // ==========================================
    // STATIC CREATION METHODS
    // ==========================================

    /**
     * Create OAuth login attempt log.
     */
    public static function logLoginAttempt(
        string $provider,
        ?int $userId,
        string $status,
        array $data = []
    ): self {
        return self::create([
            'user_id' => $userId,
            'oauth_provider' => $provider,
            'oauth_action' => 'login.attempt',
            'oauth_event_type' => 'authentication',
            'status' => $status,
            'oauth_email' => $data['email'] ?? null,
            'oauth_domain' => isset($data['email']) ? self::extractDomain($data['email']) : null,
            'oauth_external_id' => $data['external_id'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'oauth_request_data' => $data,
            'oauth_initiated_at' => now(),
            'oauth_completed_at' => now(),
            'security_level' => $status === 'failure' ? 'suspicious' : 'normal',
        ]);
    }

    /**
     * Create account linking log.
     */
    public static function logAccountLinking(
        string $provider,
        int $userId,
        string $action, // link, unlink
        array $data = []
    ): self {
        return self::create([
            'user_id' => $userId,
            'oauth_provider' => $provider,
            'oauth_action' => "account.{$action}",
            'oauth_event_type' => 'authorization',
            'status' => 'success',
            'oauth_email' => $data['email'] ?? null,
            'oauth_domain' => isset($data['email']) ? self::extractDomain($data['email']) : null,
            'oauth_external_id' => $data['external_id'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'oauth_profile_data' => $data,
            'oauth_initiated_at' => now(),
            'oauth_completed_at' => now(),
        ]);
    }

    /**
     * Create profile sync log.
     */
    public static function logProfileSync(
        string $provider,
        int $userId,
        array $syncedData,
        string $status = 'success'
    ): self {
        return self::create([
            'user_id' => $userId,
            'oauth_provider' => $provider,
            'oauth_action' => 'profile.sync',
            'oauth_event_type' => 'sync',
            'status' => $status,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'oauth_profile_data' => $syncedData,
            'oauth_initiated_at' => now(),
            'oauth_completed_at' => now(),
        ]);
    }

    /**
     * Create security incident log.
     */
    public static function logSecurityIncident(
        string $provider,
        ?int $userId,
        string $incident,
        array $indicators = [],
        array $context = []
    ): self {
        return self::create([
            'user_id' => $userId,
            'oauth_provider' => $provider,
            'oauth_action' => 'security.incident',
            'oauth_event_type' => 'security',
            'status' => 'blocked',
            'security_level' => 'critical',
            'requires_review' => true,
            'security_indicators' => array_merge(['incident' => $incident], $indicators),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'oauth_request_data' => $context,
            'oauth_initiated_at' => now(),
            'oauth_completed_at' => now(),
        ]);
    }

    // ==========================================
    // UTILITY METHODS
    // ==========================================

    /**
     * Extract domain from email.
     */
    protected static function extractDomain(string $email): string
    {
        return substr(strrchr($email, "@"), 1);
    }

    /**
     * Mark log as reviewed.
     */
    public function markAsReviewed(): void
    {
        $this->update(['requires_review' => false]);
    }

    /**
     * Archive this log.
     */
    public function archive(): void
    {
        $this->update(['archived_at' => now()]);
    }

    /**
     * Check if log is within retention period.
     */
    public function isWithinRetention(): bool
    {
        $retentionDays = match($this->retention_policy) {
            'standard' => 90,
            'extended' => 365,
            'permanent' => null,
            default => 90
        };
        
        if (!$retentionDays) {
            return true; // Permanent retention
        }
        
        return $this->created_at->addDays($retentionDays)->isFuture();
    }

    /**
     * Get processing time in human readable format.
     */
    public function getProcessingTimeAttribute(): ?string
    {
        if (!$this->processing_time_ms) {
            return null;
        }
        
        if ($this->processing_time_ms < 1000) {
            return $this->processing_time_ms . 'ms';
        }
        
        return round($this->processing_time_ms / 1000, 2) . 's';
    }

    /**
     * Get human readable security level.
     */
    public function getSecurityLevelDisplayAttribute(): string
    {
        return match($this->security_level) {
            'normal' => 'Normalny',
            'suspicious' => 'Podejrzany',
            'critical' => 'Krytyczny',
            default => ucfirst($this->security_level)
        };
    }

    /**
     * Check if this is a failed login attempt.
     */
    public function isFailedLogin(): bool
    {
        return $this->oauth_action === 'login.attempt' && $this->status === 'failure';
    }

    /**
     * Check if this is a security incident.
     */
    public function isSecurityIncident(): bool
    {
        return $this->oauth_event_type === 'security' && $this->security_level === 'critical';
    }

    /**
     * Get related security incidents for same IP or user.
     */
    public function getRelatedIncidents()
    {
        $query = self::where('oauth_event_type', 'security')
                    ->where('id', '!=', $this->id);
        
        if ($this->user_id) {
            $query->where('user_id', $this->user_id);
        } else {
            $query->where('ip_address', $this->ip_address);
        }
        
        return $query->recent(168) // Last 7 days
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
    }
}