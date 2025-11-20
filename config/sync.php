<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sync Jobs Retention Policy
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic cleanup of sync jobs history.
    | Used by:
    | - Manual "Clear Logs" button in /admin/shops
    | - Artisan command: sync:cleanup
    | - Scheduled task (if enabled)
    |
    */

    'retention' => [
        /*
        | Days to keep completed sync jobs before cleanup
        | Default: 30 days
        */
        'completed_days' => env('SYNC_RETENTION_COMPLETED', 30),

        /*
        | Days to keep failed sync jobs before cleanup
        | Failed jobs kept longer for debugging purposes
        | Default: 90 days
        */
        'failed_days' => env('SYNC_RETENTION_FAILED', 90),

        /*
        | Days to keep canceled sync jobs before cleanup
        | Default: 14 days
        */
        'canceled_days' => env('SYNC_RETENTION_CANCELED', 14),

        /*
        | Never auto-delete jobs with these statuses
        | These jobs represent active work or important state
        */
        'never_delete_statuses' => ['pending', 'running'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    */

    'cleanup' => [
        /*
        | Enable automatic scheduled cleanup
        | If true, cleanup runs via Laravel scheduler
        | Default: false (manual only)
        */
        'auto_cleanup_enabled' => env('SYNC_AUTO_CLEANUP', false),

        /*
        | Cleanup batch size
        | Number of records to delete per query (prevents memory issues)
        | Default: 500
        */
        'batch_size' => 500,
    ],
];
