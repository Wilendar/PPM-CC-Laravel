<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'type',
        'priority',
        'channel',
        'is_read',
        'read_at',
        'sent_at',
        'is_acknowledged',
        'acknowledged_at',
        'acknowledged_by',
        'related_type',
        'related_id',
        'metadata',
        'recipients',
        'created_by',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        'is_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
        'metadata' => 'array',
        'recipients' => 'array',
    ];

    /**
     * Type constants
     */
    public const TYPE_SYSTEM = 'system';
    public const TYPE_SECURITY = 'security';
    public const TYPE_INTEGRATION = 'integration';
    public const TYPE_USER = 'user';

    /**
     * Priority constants
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    /**
     * Channel constants
     */
    public const CHANNEL_WEB = 'web';
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_BOTH = 'both';

    /**
     * Get the user who created this notification
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who acknowledged this notification
     */
    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Get the related model (polymorphic)
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for critical notifications
     */
    public function scopeCritical($query)
    {
        return $query->where('priority', self::PRIORITY_CRITICAL);
    }

    /**
     * Scope for high priority notifications
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_CRITICAL]);
    }

    /**
     * Scope by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as acknowledged
     */
    public function acknowledge(User $user): void
    {
        $this->update([
            'is_acknowledged' => true,
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ]);
    }

    /**
     * Get priority color class
     */
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_CRITICAL => 'text-red-600',
            self::PRIORITY_HIGH => 'text-orange-600',
            self::PRIORITY_NORMAL => 'text-blue-600',
            self::PRIORITY_LOW => 'text-gray-600',
        };
    }

    /**
     * Get priority icon
     */
    public function getPriorityIconAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_CRITICAL => 'fas fa-exclamation-triangle',
            self::PRIORITY_HIGH => 'fas fa-exclamation-circle',
            self::PRIORITY_NORMAL => 'fas fa-info-circle',
            self::PRIORITY_LOW => 'fas fa-bell',
        };
    }

    /**
     * Get type icon
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_SYSTEM => 'fas fa-server',
            self::TYPE_SECURITY => 'fas fa-shield-alt',
            self::TYPE_INTEGRATION => 'fas fa-plug',
            self::TYPE_USER => 'fas fa-user',
        };
    }

    /**
     * Check if notification should be sent via email
     */
    public function shouldSendEmail(): bool
    {
        return in_array($this->channel, [self::CHANNEL_EMAIL, self::CHANNEL_BOTH]);
    }

    /**
     * Check if notification should be shown on web
     */
    public function shouldShowOnWeb(): bool
    {
        return in_array($this->channel, [self::CHANNEL_WEB, self::CHANNEL_BOTH]);
    }
}