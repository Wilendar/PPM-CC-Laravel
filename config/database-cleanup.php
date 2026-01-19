<?php

/**
 * Database Cleanup Configuration
 *
 * Retention policies dla tabel ktore moga rosnac nieograniczenie.
 * KRYTYCZNE: Bez regularnego czyszczenia tabele moga rosnac do gigabajtow!
 *
 * Incident 2025-01-19: telescope_entries (43GB), price_history (74GB)
 *
 * @see App\Console\Commands\DatabaseHealthCheck
 * @see App\Console\Commands\CleanupLogTables
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Global Settings
    |--------------------------------------------------------------------------
    */
    'enabled' => env('DB_CLEANUP_ENABLED', true),

    // Send email alerts when cleanup runs
    'send_alerts' => env('DB_CLEANUP_ALERTS', true),

    // Admin email for alerts
    'alert_email' => env('DB_CLEANUP_ALERT_EMAIL', 'it@mpptrade.pl'),

    /*
    |--------------------------------------------------------------------------
    | Table Retention Policies
    |--------------------------------------------------------------------------
    |
    | retention_days: How long to keep records
    | date_column: Which column to use for age calculation
    | chunk_size: Delete in batches to avoid memory issues
    | enabled: Can disable specific tables
    |
    */
    'tables' => [
        // Telescope - debugging data, short retention
        'telescope_entries' => [
            'retention_days' => 2,       // Keep 2 days (48 hours)
            'date_column' => 'created_at',
            'chunk_size' => 10000,
            'enabled' => true,
            'command' => 'telescope:prune --hours=48', // Use built-in command
        ],

        // Price history - audit trail, medium retention
        'price_history' => [
            'retention_days' => 90,      // Keep 90 days
            'date_column' => 'created_at',
            'chunk_size' => 5000,
            'enabled' => true,
            'command' => 'price-history:cleanup --days=90',
        ],

        // Sync jobs - operation logs, medium retention
        'sync_jobs' => [
            'retention_days' => 30,      // Keep 30 days
            'date_column' => 'created_at',
            'chunk_size' => 1000,
            'enabled' => true,
            'command' => 'sync:cleanup',
        ],

        // Sync logs - detailed sync logs, short retention
        'sync_logs' => [
            'retention_days' => 14,      // Keep 14 days
            'date_column' => 'created_at',
            'chunk_size' => 5000,
            'enabled' => true,
        ],

        // Integration logs - ERP integration logs, medium retention
        'integration_logs' => [
            'retention_days' => 30,      // Keep 30 days
            'date_column' => 'created_at',
            'chunk_size' => 5000,
            'enabled' => true,
        ],

        // Job progress - queue job tracking, short retention
        'job_progress' => [
            'retention_days' => 7,       // Keep 7 days
            'date_column' => 'created_at',
            'chunk_size' => 1000,
            'enabled' => true,
            'command' => 'jobs:cleanup-stuck --minutes=10080', // 7 days in minutes
        ],

        // Failed jobs - should be reviewed manually, longer retention
        'failed_jobs' => [
            'retention_days' => 30,      // Keep 30 days
            'date_column' => 'failed_at',
            'chunk_size' => 100,
            'enabled' => true,
        ],

        // Notifications - user notifications, medium retention
        'notifications' => [
            'retention_days' => 90,      // Keep 90 days
            'date_column' => 'created_at',
            'chunk_size' => 1000,
            'enabled' => true,
        ],

        // Category previews - temporary data, very short retention
        'category_previews' => [
            'retention_days' => 1,       // Keep 1 day
            'date_column' => 'created_at',
            'chunk_size' => 1000,
            'enabled' => true,
            'command' => 'category-preview:cleanup',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Size Thresholds for Alerts
    |--------------------------------------------------------------------------
    |
    | When table exceeds these sizes (in MB), alerts are triggered.
    |
    */
    'thresholds' => [
        'telescope_entries' => ['warning' => 100, 'critical' => 500],
        'telescope_entries_tags' => ['warning' => 50, 'critical' => 200],
        'price_history' => ['warning' => 500, 'critical' => 2000],
        'sync_jobs' => ['warning' => 50, 'critical' => 200],
        'sync_logs' => ['warning' => 20, 'critical' => 100],
        'integration_logs' => ['warning' => 50, 'critical' => 200],
        'job_progress' => ['warning' => 20, 'critical' => 100],
        'failed_jobs' => ['warning' => 10, 'critical' => 50],
        'notifications' => ['warning' => 50, 'critical' => 200],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Schedule
    |--------------------------------------------------------------------------
    */
    'health_check' => [
        'enabled' => true,
        'schedule' => 'daily',           // daily, hourly, weekly
        'time' => '06:00',               // Time for daily schedule
        'send_alert_on_warning' => true,
        'send_alert_on_critical' => true,
    ],
];
