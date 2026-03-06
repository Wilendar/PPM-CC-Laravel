<?php

namespace App\Services;

use App\Models\SystemSetting;

class RetentionConfigService
{
    /**
     * Get retention days for a table.
     * Priority: system_settings DB > config/database-cleanup.php > default
     */
    public function getRetentionDays(string $table, int $default = 30): int
    {
        // 1. Check system_settings DB
        $dbValue = SystemSetting::get("retention.{$table}.days");
        if ($dbValue !== null) {
            return (int) $dbValue;
        }

        // 2. Check config file
        $configValue = config("database-cleanup.tables.{$table}.retention_days");
        if ($configValue !== null) {
            return (int) $configValue;
        }

        // 3. Default
        return $default;
    }

    /**
     * Check if archive is enabled
     */
    public function isArchiveEnabled(): bool
    {
        $dbValue = SystemSetting::get('retention.archive_enabled');
        if ($dbValue !== null) {
            return (bool) $dbValue;
        }

        return config('database-cleanup.archive.enabled', true);
    }

    /**
     * Get archive retention days
     */
    public function getArchiveRetentionDays(): int
    {
        $dbValue = SystemSetting::get('retention.archive_retention_days');
        if ($dbValue !== null) {
            return (int) $dbValue;
        }

        return config('database-cleanup.archive.archive_retention_days', 180);
    }

    /**
     * Check if sync cleanup is enabled
     */
    public function isSyncCleanupEnabled(): bool
    {
        $dbValue = SystemSetting::get('retention.sync_cleanup_enabled');
        if ($dbValue !== null) {
            return (bool) $dbValue;
        }

        return config('sync.cleanup.auto_cleanup_enabled', false);
    }

    /**
     * Get media soft-delete purge days
     */
    public function getMediaPurgeDays(): int
    {
        $dbValue = SystemSetting::get('retention.media_trashed.days');
        if ($dbValue !== null) {
            return (int) $dbValue;
        }

        return config('database-cleanup.tables.media_trashed.retention_days', 30);
    }

    /**
     * Get media orphan cleanup days
     */
    public function getMediaOrphanDays(): int
    {
        $dbValue = SystemSetting::get('retention.media.days');
        if ($dbValue !== null) {
            return (int) $dbValue;
        }

        return config('database-cleanup.tables.media.retention_days', 90);
    }

    /**
     * Get full retention config for all tables
     */
    public function getAllRetentionConfig(): array
    {
        $tables = config('database-cleanup.tables', []);
        $result = [];

        foreach ($tables as $table => $config) {
            $result[$table] = [
                'retention_days' => $this->getRetentionDays($table, $config['retention_days'] ?? 30),
                'date_column' => $config['date_column'] ?? 'created_at',
                'chunk_size' => $config['chunk_size'] ?? 1000,
                'enabled' => $config['enabled'] ?? true,
                'command' => $config['command'] ?? null,
            ];
        }

        return $result;
    }
}
