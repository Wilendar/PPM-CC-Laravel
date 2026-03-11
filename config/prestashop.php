<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PrestaShop API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for PrestaShop API integration (v8/v9)
    |
    */

    /**
     * Default API timeout (seconds)
     */
    'api_timeout' => env('PRESTASHOP_API_TIMEOUT', 30),

    /**
     * API retry attempts on failure
     */
    'api_retry_attempts' => env('PRESTASHOP_API_RETRY_ATTEMPTS', 3),

    /**
     * API retry delay (milliseconds)
     */
    'api_retry_delay_ms' => env('PRESTASHOP_API_RETRY_DELAY_MS', 1000),

    /*
    |--------------------------------------------------------------------------
    | Category Import Preview System
    |--------------------------------------------------------------------------
    |
    | ETAP_07 FAZA 3D: Category Import Preview
    |
    | Enable/disable category preview during product import.
    | When enabled, system analyzes missing categories and shows preview
    | modal before creating them in PPM.
    |
    */

    /**
     * Enable category preview during product import
     *
     * TRUE: Analyze missing categories → show preview → user approval → create
     * FALSE: Skip analysis → create categories automatically
     */
    'category_preview_enabled' => env('PRESTASHOP_CATEGORY_PREVIEW_ENABLED', true),

    /**
     * Category preview expiration time (hours)
     *
     * After this time, preview expires and requires re-analysis
     */
    'category_preview_expiration_hours' => env('PRESTASHOP_CATEGORY_PREVIEW_EXPIRATION', 1),

    /*
    |--------------------------------------------------------------------------
    | Synchronization Settings
    |--------------------------------------------------------------------------
    */

    /**
     * Default sync frequency (cron schedule)
     */
    'sync_frequency' => env('PRESTASHOP_SYNC_FREQUENCY', '0 */6 * * *'), // Every 6 hours

    /**
     * Sync batch size (products per batch)
     */
    'sync_batch_size' => env('PRESTASHOP_SYNC_BATCH_SIZE', 50),

    /**
     * Enable automatic conflict resolution
     */
    'auto_conflict_resolution' => env('PRESTASHOP_AUTO_CONFLICT_RESOLUTION', false),

    /**
     * Default conflict resolution strategy
     *
     * Options: 'use_ppm', 'use_prestashop', 'manual', 'skip'
     */
    'default_conflict_resolution' => env('PRESTASHOP_DEFAULT_CONFLICT_RESOLUTION', 'manual'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    /**
     * Max API requests per hour
     */
    'rate_limit_per_hour' => env('PRESTASHOP_RATE_LIMIT_PER_HOUR', 3600),

    /**
     * Enable rate limiting
     */
    'rate_limiting_enabled' => env('PRESTASHOP_RATE_LIMITING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */

    /**
     * Enable webhook processing
     */
    'webhooks_enabled' => env('PRESTASHOP_WEBHOOKS_ENABLED', false),

    /**
     * Webhook signature verification
     */
    'webhook_signature_verification' => env('PRESTASHOP_WEBHOOK_SIGNATURE_VERIFICATION', true),

    /*
    |--------------------------------------------------------------------------
    | Image Synchronization
    |--------------------------------------------------------------------------
    */

    /**
     * Enable image sync
     */
    'sync_images' => env('PRESTASHOP_SYNC_IMAGES', true),

    /**
     * Image download timeout (seconds)
     */
    'image_download_timeout' => env('PRESTASHOP_IMAGE_DOWNLOAD_TIMEOUT', 60),

    /**
     * Max image size (MB)
     */
    'max_image_size_mb' => env('PRESTASHOP_MAX_IMAGE_SIZE_MB', 10),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */

    /**
     * Enable detailed API logging
     */
    'detailed_logging' => env('PRESTASHOP_DETAILED_LOGGING', false),

    /**
     * Log API requests/responses
     */
    'log_api_requests' => env('PRESTASHOP_LOG_API_REQUESTS', true),

    /**
     * Log sync operations
     */
    'log_sync_operations' => env('PRESTASHOP_LOG_SYNC_OPERATIONS', true),

];
