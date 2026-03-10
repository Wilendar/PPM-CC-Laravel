<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Services\Audit\AuditContext;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Trait Auditable
 *
 * Automatic CRUD audit logging via Eloquent model events.
 * Attach to any model to enable audit trail creation.
 *
 * Configurable per model via properties:
 *   - $auditExclude:    array of field names to skip (e.g. ['cached_count'])
 *   - $auditOnly:       if set, log ONLY these fields (whitelist mode)
 *   - $auditOnlySource: if set, log ONLY when source matches (e.g. [AuditLog::SOURCE_WEB])
 *
 * Usage:
 *   class Product extends Model {
 *       use Auditable;
 *       protected array $auditExclude = ['search_index', 'cached_html'];
 *   }
 */
trait Auditable
{
    /**
     * Batch counter per model class to enforce batch_limit.
     *
     * @var array<string, int>
     */
    protected static array $auditBatchCounts = [];

    /**
     * Boot the Auditable trait - register model event listeners.
     */
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->logAuditCreated();
        });

        static::updated(function ($model) {
            $model->logAuditUpdated();
        });

        static::deleted(function ($model) {
            $model->logAuditDeleted();
        });

        // Register restored event only if model uses SoftDeletes
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(function ($model) {
                $model->logAuditRestored();
            });
        }
    }

    /**
     * Reset batch counters (useful in testing or long-running processes).
     */
    public static function resetAuditBatchCount(): void
    {
        static::$auditBatchCounts[static::class] = 0;
    }

    /**
     * Determine whether this model change should be audited.
     */
    protected function shouldAudit(): bool
    {
        // Global config check
        if (!config('audit.enabled', true)) {
            return false;
        }

        // Runtime context check
        if (!AuditContext::isEnabled()) {
            return false;
        }

        // Source filter - only log from specified sources
        $onlySource = $this->auditOnlySource ?? [];
        if (!empty($onlySource)) {
            $currentSource = $this->resolveAuditSource();
            if (!in_array($currentSource, $onlySource)) {
                return false;
            }
        }

        // Batch limit per model class
        $limit = config('audit.batch_limit', 100);
        $className = static::class;
        $currentCount = static::$auditBatchCounts[$className] ?? 0;

        if ($currentCount >= $limit) {
            // Log one summary entry at the limit boundary
            if ($currentCount === $limit) {
                static::$auditBatchCounts[$className] = $currentCount + 1;

                $this->createAuditEntry(
                    AuditLog::EVENT_UPDATED,
                    null,
                    ['batch_truncated' => true, 'limit' => $limit],
                    'Batch limit reached - further changes not individually logged'
                );
            }

            return false;
        }

        static::$auditBatchCounts[$className] = $currentCount + 1;

        return true;
    }

    /**
     * Log audit entry for model creation.
     */
    protected function logAuditCreated(): void
    {
        if (!$this->shouldAudit()) {
            return;
        }

        $newValues = $this->filterAuditAttributes($this->getAttributes());

        $this->createAuditEntry(AuditLog::EVENT_CREATED, null, $newValues);
    }

    /**
     * Log audit entry for model update.
     * Skips logging if no auditable fields were changed.
     */
    protected function logAuditUpdated(): void
    {
        if (!$this->shouldAudit()) {
            return;
        }

        $dirty = $this->getDirtyFiltered();

        // Nothing auditable changed - skip
        if (empty($dirty)) {
            return;
        }

        $oldValues = array_intersect_key($this->getOriginal(), $dirty);

        $this->createAuditEntry(AuditLog::EVENT_UPDATED, $oldValues, $dirty);
    }

    /**
     * Log audit entry for model deletion.
     */
    protected function logAuditDeleted(): void
    {
        if (!$this->shouldAudit()) {
            return;
        }

        $oldValues = $this->filterAuditAttributes($this->getAttributes());

        $this->createAuditEntry(AuditLog::EVENT_DELETED, $oldValues, null);
    }

    /**
     * Log audit entry for model restoration (SoftDeletes).
     */
    protected function logAuditRestored(): void
    {
        if (!$this->shouldAudit()) {
            return;
        }

        $this->createAuditEntry(AuditLog::EVENT_RESTORED, null, ['id' => $this->getKey()]);
    }

    /**
     * Create an audit log entry with proper context resolution.
     *
     * Uses AuditLog::create() directly instead of AuditLog::log() to ensure
     * that AuditContext user override is respected. AuditLog::log() hardcodes
     * auth()->id() which would ignore the context user in CLI/queue scenarios.
     */
    protected function createAuditEntry(
        string $event,
        ?array $oldValues,
        ?array $newValues,
        ?string $comment = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $this->resolveAuditUser(),
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'source' => $this->resolveAuditSource(),
            'comment' => $comment,
            'created_at' => now(),
        ]);
    }

    /**
     * Get dirty attributes filtered by audit rules.
     *
     * Applies exclusion/inclusion rules:
     * 1. Remove globally excluded fields (config)
     * 2. Remove model-specific excluded fields ($auditExclude)
     * 3. If $auditOnly is set, keep ONLY those fields
     *
     * @return array<string, mixed>
     */
    protected function getDirtyFiltered(): array
    {
        $dirty = $this->getDirty();

        return $this->filterAuditAttributes($dirty);
    }

    /**
     * Filter attributes according to audit rules.
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    protected function filterAuditAttributes(array $attributes): array
    {
        // Global exclusions from config
        $globalExclude = config('audit.global_exclude', []);
        $attributes = array_diff_key($attributes, array_flip($globalExclude));

        // Model-specific exclusions
        $modelExclude = $this->auditExclude ?? [];
        if (!empty($modelExclude)) {
            $attributes = array_diff_key($attributes, array_flip($modelExclude));
        }

        // Model-specific whitelist (if set, ONLY these fields are logged)
        $modelOnly = $this->auditOnly ?? [];
        if (!empty($modelOnly)) {
            $attributes = array_intersect_key($attributes, array_flip($modelOnly));
        }

        return $attributes;
    }

    /**
     * Resolve the user ID for the audit log entry.
     *
     * Priority: AuditContext override > authenticated user > null (system)
     */
    protected function resolveAuditUser(): ?int
    {
        return AuditContext::getUser() ?? auth()->id();
    }

    /**
     * Resolve the source for the audit log entry.
     *
     * Priority: AuditContext override > default (web)
     */
    protected function resolveAuditSource(): string
    {
        return AuditContext::getSource() ?? AuditLog::SOURCE_WEB;
    }

    /**
     * Get all audit log entries for this model.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
