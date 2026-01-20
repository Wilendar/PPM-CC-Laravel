<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BugReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'steps_to_reproduce',
        'type',
        'severity',
        'status',
        'context_url',
        'browser_info',
        'os_info',
        'console_errors',
        'user_actions',
        'screenshot_path',
        'reporter_id',
        'assigned_to',
        'resolution',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'console_errors' => 'array',
        'user_actions' => 'array',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Type constants
     */
    public const TYPE_BUG = 'bug';
    public const TYPE_FEATURE_REQUEST = 'feature_request';
    public const TYPE_IMPROVEMENT = 'improvement';
    public const TYPE_QUESTION = 'question';
    public const TYPE_SUPPORT = 'support';

    /**
     * Severity constants
     */
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Status constants
     */
    public const STATUS_NEW = 'new';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Get available types with labels
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_BUG => 'Blad',
            self::TYPE_FEATURE_REQUEST => 'Nowa funkcja',
            self::TYPE_IMPROVEMENT => 'Ulepszenie',
            self::TYPE_QUESTION => 'Pytanie',
            self::TYPE_SUPPORT => 'Wsparcie',
        ];
    }

    /**
     * Get available severities with labels
     */
    public static function getSeverities(): array
    {
        return [
            self::SEVERITY_LOW => 'Niski',
            self::SEVERITY_MEDIUM => 'Sredni',
            self::SEVERITY_HIGH => 'Wysoki',
            self::SEVERITY_CRITICAL => 'Krytyczny',
        ];
    }

    /**
     * Get available statuses with labels
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_NEW => 'Nowe',
            self::STATUS_IN_PROGRESS => 'W trakcie',
            self::STATUS_WAITING => 'Oczekuje',
            self::STATUS_RESOLVED => 'Rozwiazane',
            self::STATUS_CLOSED => 'Zamkniete',
            self::STATUS_REJECTED => 'Odrzucone',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the user who reported this bug
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Get the user assigned to this report
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get comments for this report
     */
    public function comments(): HasMany
    {
        return $this->hasMany(BugReportComment::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get public comments (visible to reporter)
     */
    public function publicComments(): HasMany
    {
        return $this->hasMany(BugReportComment::class)
            ->where('is_internal', false)
            ->orderBy('created_at', 'asc');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope by severity
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope for unresolved reports
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
            self::STATUS_REJECTED,
        ]);
    }

    /**
     * Scope for reports assigned to user
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope for reports created by user
     */
    public function scopeReportedBy($query, int $userId)
    {
        return $query->where('reporter_id', $userId);
    }

    /**
     * Scope for new reports
     */
    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get status badge HTML class (PPM Dark Theme)
     */
    public function getStatusBadgeAttribute(): string
    {
        return 'badge-bugreport badge-status-' . $this->status;
    }

    /**
     * Get type badge HTML class (PPM Dark Theme)
     */
    public function getTypeBadgeAttribute(): string
    {
        return 'badge-bugreport badge-type-' . $this->type;
    }

    /**
     * Get severity badge HTML class (PPM Dark Theme)
     */
    public function getSeverityBadgeAttribute(): string
    {
        return 'badge-bugreport badge-severity-' . $this->severity;
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    /**
     * Get severity label
     */
    public function getSeverityLabelAttribute(): string
    {
        return self::getSeverities()[$this->severity] ?? $this->severity;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    /**
     * Get type icon
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_BUG => 'fas fa-bug',
            self::TYPE_FEATURE_REQUEST => 'fas fa-lightbulb',
            self::TYPE_IMPROVEMENT => 'fas fa-chart-line',
            self::TYPE_QUESTION => 'fas fa-question-circle',
            self::TYPE_SUPPORT => 'fas fa-headset',
            default => 'fas fa-file-alt',
        };
    }

    /**
     * Get severity icon
     */
    public function getSeverityIconAttribute(): string
    {
        return match ($this->severity) {
            self::SEVERITY_LOW => 'fas fa-arrow-down',
            self::SEVERITY_MEDIUM => 'fas fa-minus',
            self::SEVERITY_HIGH => 'fas fa-arrow-up',
            self::SEVERITY_CRITICAL => 'fas fa-exclamation-triangle',
            default => 'fas fa-minus',
        };
    }

    // ========================================
    // METHODS
    // ========================================

    /**
     * Check if report is open (can be edited)
     */
    public function isOpen(): bool
    {
        return !in_array($this->status, [
            self::STATUS_CLOSED,
            self::STATUS_REJECTED,
        ]);
    }

    /**
     * Check if report is resolved
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Check if report is closed (closed or rejected)
     */
    public function isClosed(): bool
    {
        return in_array($this->status, [
            self::STATUS_CLOSED,
            self::STATUS_REJECTED,
        ]);
    }

    /**
     * Mark as in progress
     */
    public function markInProgress(?int $assigneeId = null): void
    {
        $data = ['status' => self::STATUS_IN_PROGRESS];

        if ($assigneeId) {
            $data['assigned_to'] = $assigneeId;
        }

        $this->update($data);
    }

    /**
     * Mark as resolved
     */
    public function markResolved(string $resolution): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolution' => $resolution,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Mark as closed
     */
    public function markClosed(): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'closed_at' => now(),
        ]);
    }

    /**
     * Mark as rejected
     */
    public function markRejected(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'resolution' => $reason,
            'closed_at' => now(),
        ]);
    }
}
