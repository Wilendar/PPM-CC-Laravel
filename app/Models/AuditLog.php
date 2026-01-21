<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Audit Log Model
 *
 * FAZA C: Kompletny audit trail dla systemu PPM
 *
 * Śledzenie wszystkich zmian w aplikacji:
 * - Kto wykonał operację (user_id)
 * - Na jakim obiekcie (auditable_type, auditable_id)
 * - Jakie zmiany (old_values, new_values)
 * - Kiedy i skąd (timestamp, IP, user_agent)
 * - Źródło zmiany (web, api, import, sync)
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $auditable_type
 * @property int $auditable_id
 * @property string $event
 * @property array|null $old_values
 * @property array|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $source
 * @property string|null $comment
 * @property Carbon $created_at
 * @property User|null $user
 * @property Model|null $auditable
 */
class AuditLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'audit_logs';

    /**
     * Indicates if the model should be timestamped.
     * Note: Table only has created_at, no updated_at
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'source',
        'comment',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'user_id' => 'integer',
        'auditable_id' => 'integer',
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Source types for audit logs.
     */
    public const SOURCE_WEB = 'web';
    public const SOURCE_API = 'api';
    public const SOURCE_IMPORT = 'import';
    public const SOURCE_SYNC = 'sync';

    /**
     * Event types for audit logs.
     */
    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_DELETED = 'deleted';
    public const EVENT_RESTORED = 'restored';
    public const EVENT_LOGIN = 'login';
    public const EVENT_LOGIN_FAILED = 'login_failed';
    public const EVENT_LOGOUT = 'logout';
    public const EVENT_BULK_DELETE = 'bulk_delete';
    public const EVENT_BULK_UPDATE = 'bulk_update';
    public const EVENT_BULK_EXPORT = 'bulk_export';

    // ==========================================
    // ACCESSORS FOR COMPONENT COMPATIBILITY
    // ==========================================

    /**
     * Get action attribute (alias for event).
     * Compatibility with AuditLogs.php component.
     */
    public function getActionAttribute(): string
    {
        return $this->event;
    }

    /**
     * Set action attribute (alias for event).
     */
    public function setActionAttribute(string $value): void
    {
        $this->attributes['event'] = $value;
    }

    /**
     * Get model_type attribute (alias for auditable_type).
     * Compatibility with AuditLogs.php component.
     */
    public function getModelTypeAttribute(): string
    {
        return $this->auditable_type;
    }

    /**
     * Set model_type attribute (alias for auditable_type).
     */
    public function setModelTypeAttribute(string $value): void
    {
        $this->attributes['auditable_type'] = $value;
    }

    /**
     * Get model_id attribute (alias for auditable_id).
     * Compatibility with AuditLogs.php component.
     */
    public function getModelIdAttribute(): int
    {
        return $this->auditable_id;
    }

    /**
     * Set model_id attribute (alias for auditable_id).
     */
    public function setModelIdAttribute(int $value): void
    {
        $this->attributes['auditable_id'] = $value;
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model (polymorphic).
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope query to specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope query to system operations (no user).
     */
    public function scopeSystemOnly(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope query to specific event type.
     */
    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Scope query to specific model type.
     */
    public function scopeForModelType(Builder $query, string $modelType): Builder
    {
        return $query->where('auditable_type', 'like', '%' . $modelType);
    }

    /**
     * Scope query to specific source.
     */
    public function scopeFromSource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    /**
     * Scope query to date range.
     */
    public function scopeDateRange(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Scope query to recent logs.
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope query to suspicious activity patterns.
     */
    public function scopeSuspicious(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('event', 'like', '%failed%')
              ->orWhere('event', 'like', '%bulk_%')
              ->orWhereRaw('HOUR(created_at) < 6 OR HOUR(created_at) > 22');
        });
    }

    // ==========================================
    // STATIC FACTORY METHODS
    // ==========================================

    /**
     * Create an audit log entry.
     */
    public static function log(
        string $event,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $comment = null,
        string $source = self::SOURCE_WEB
    ): self {
        return self::create([
            'user_id' => auth()->id(),
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'source' => $source,
            'comment' => $comment,
            'created_at' => now(),
        ]);
    }

    /**
     * Log a model creation.
     */
    public static function logCreated(Model $model, ?string $comment = null): self
    {
        return self::log(
            self::EVENT_CREATED,
            $model,
            null,
            $model->toArray(),
            $comment
        );
    }

    /**
     * Log a model update.
     */
    public static function logUpdated(Model $model, array $oldValues, ?string $comment = null): self
    {
        return self::log(
            self::EVENT_UPDATED,
            $model,
            $oldValues,
            $model->toArray(),
            $comment
        );
    }

    /**
     * Log a model deletion.
     */
    public static function logDeleted(Model $model, ?string $comment = null): self
    {
        return self::log(
            self::EVENT_DELETED,
            $model,
            $model->toArray(),
            null,
            $comment
        );
    }

    /**
     * Log a user login.
     */
    public static function logLogin(?int $userId = null): self
    {
        $user = $userId ? User::find($userId) : auth()->user();

        return self::create([
            'user_id' => $user?->id,
            'auditable_type' => User::class,
            'auditable_id' => $user?->id ?? 0,
            'event' => self::EVENT_LOGIN,
            'old_values' => null,
            'new_values' => ['ip' => request()->ip()],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'source' => self::SOURCE_WEB,
            'created_at' => now(),
        ]);
    }

    /**
     * Log a failed login attempt.
     */
    public static function logLoginFailed(string $email): self
    {
        return self::create([
            'user_id' => null,
            'auditable_type' => User::class,
            'auditable_id' => 0,
            'event' => self::EVENT_LOGIN_FAILED,
            'old_values' => null,
            'new_values' => ['email' => $email],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'source' => self::SOURCE_WEB,
            'created_at' => now(),
        ]);
    }

    /**
     * Log a user logout.
     */
    public static function logLogout(): self
    {
        $user = auth()->user();

        return self::create([
            'user_id' => $user?->id,
            'auditable_type' => User::class,
            'auditable_id' => $user?->id ?? 0,
            'event' => self::EVENT_LOGOUT,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'source' => self::SOURCE_WEB,
            'created_at' => now(),
        ]);
    }

    // ==========================================
    // UTILITY METHODS
    // ==========================================

    /**
     * Get the changes between old and new values.
     */
    public function getChanges(): array
    {
        $changes = [];
        $oldValues = $this->old_values ?? [];
        $newValues = $this->new_values ?? [];

        $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

        foreach ($allKeys as $key) {
            $old = $oldValues[$key] ?? null;
            $new = $newValues[$key] ?? null;

            if ($old !== $new) {
                $changes[$key] = [
                    'old' => $old,
                    'new' => $new,
                ];
            }
        }

        return $changes;
    }

    /**
     * Get human-readable event name.
     */
    public function getEventDisplayAttribute(): string
    {
        return match($this->event) {
            'created' => 'Utworzono',
            'updated' => 'Zaktualizowano',
            'deleted' => 'Usunięto',
            'restored' => 'Przywrócono',
            'login' => 'Logowanie',
            'login_failed' => 'Nieudane logowanie',
            'logout' => 'Wylogowanie',
            'bulk_delete' => 'Masowe usunięcie',
            'bulk_update' => 'Masowa aktualizacja',
            'bulk_export' => 'Masowy eksport',
            default => ucfirst(str_replace('_', ' ', $this->event))
        };
    }

    /**
     * Get human-readable source name.
     */
    public function getSourceDisplayAttribute(): string
    {
        return match($this->source) {
            'web' => 'Panel Web',
            'api' => 'API',
            'import' => 'Import',
            'sync' => 'Synchronizacja',
            default => ucfirst($this->source)
        };
    }

    /**
     * Get short model type name (without namespace).
     */
    public function getShortModelTypeAttribute(): string
    {
        return class_basename($this->auditable_type);
    }

    /**
     * Check if this is a suspicious activity.
     */
    public function isSuspicious(): bool
    {
        // Failed logins
        if ($this->event === self::EVENT_LOGIN_FAILED) {
            return true;
        }

        // Bulk operations
        if (str_starts_with($this->event, 'bulk_')) {
            return true;
        }

        // Outside business hours
        $hour = $this->created_at->hour;
        if ($hour < 6 || $hour > 22) {
            return true;
        }

        return false;
    }
}
