<?php

namespace App\Services\Audit;

/**
 * Static context resolver for audit logging.
 *
 * Allows setting user, source and enabled state for audit operations
 * outside of the standard web request context (e.g. CLI, queues, imports).
 *
 * Usage:
 *   AuditContext::setSource(AuditLog::SOURCE_IMPORT);
 *   AuditContext::setUser($importUserId);
 *   // ... perform model operations, audit logs will use this context
 *   AuditContext::reset();
 *
 *   // Temporarily disable audit logging:
 *   AuditContext::withoutAudit(function () {
 *       $model->update(['field' => 'value']); // no audit log created
 *   });
 */
class AuditContext
{
    /**
     * Override user ID for audit logs (used in CLI/queue context).
     */
    protected static ?int $userId = null;

    /**
     * Override source for audit logs (web, api, import, sync).
     */
    protected static ?string $source = null;

    /**
     * Whether audit logging is currently enabled.
     */
    protected static bool $enabled = true;

    /**
     * Set the user ID for subsequent audit log entries.
     */
    public static function setUser(int $userId): void
    {
        static::$userId = $userId;
    }

    /**
     * Get the overridden user ID, or null if not set.
     */
    public static function getUser(): ?int
    {
        return static::$userId;
    }

    /**
     * Set the source for subsequent audit log entries.
     *
     * @param string $source One of AuditLog::SOURCE_* constants
     */
    public static function setSource(string $source): void
    {
        static::$source = $source;
    }

    /**
     * Get the overridden source, or null if not set.
     */
    public static function getSource(): ?string
    {
        return static::$source;
    }

    /**
     * Disable audit logging globally.
     */
    public static function disable(): void
    {
        static::$enabled = false;
    }

    /**
     * Enable audit logging globally.
     */
    public static function enable(): void
    {
        static::$enabled = true;
    }

    /**
     * Check if audit logging is currently enabled.
     */
    public static function isEnabled(): bool
    {
        return static::$enabled;
    }

    /**
     * Reset all context overrides to defaults.
     */
    public static function reset(): void
    {
        static::$userId = null;
        static::$source = null;
        static::$enabled = true;
    }

    /**
     * Execute a callback with audit logging disabled.
     *
     * Restores previous enabled state after callback completes,
     * even if an exception is thrown.
     *
     * @param \Closure $callback The code to execute without audit logging
     * @return mixed The return value of the callback
     */
    public static function withoutAudit(\Closure $callback): mixed
    {
        $wasEnabled = static::$enabled;
        static::$enabled = false;

        try {
            return $callback();
        } finally {
            static::$enabled = $wasEnabled;
        }
    }

    /**
     * Execute a callback with a specific source context.
     *
     * Restores previous source after callback completes.
     *
     * @param string $source One of AuditLog::SOURCE_* constants
     * @param \Closure $callback The code to execute with the given source
     * @return mixed The return value of the callback
     */
    public static function withSource(string $source, \Closure $callback): mixed
    {
        $previousSource = static::$source;
        static::$source = $source;

        try {
            return $callback();
        } finally {
            static::$source = $previousSource;
        }
    }
}
