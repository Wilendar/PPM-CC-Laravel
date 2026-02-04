<?php

/**
 * FAZA 9.1.3: Import Panel Configuration
 *
 * Konfiguracja ERP Glownego, targetow publikacji i schedulera.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | ERP Connections (GRUPA D)
    |--------------------------------------------------------------------------
    |
    | ERP connections are now managed via ERPConnection model (erp_connections table).
    | The default ERP connection is marked with is_default=true in the database.
    |
    | Legacy 'erp_primary' config is DEPRECATED - kept only for backward compatibility.
    | All new code should use ERPConnection::default()->active()->first().
    |
    | @see App\Models\ERPConnection
    | @see App\Services\Import\PublicationTargetService
    |
    */
    'erp_primary' => [
        'enabled' => true,
        'name' => env('IMPORT_ERP_PRIMARY_NAME', 'PPM'),
        'type' => env('IMPORT_ERP_PRIMARY_TYPE', 'ppm'),
        'deprecated' => true, // GRUPA D: Use ERPConnection model instead
    ],

    /*
    |--------------------------------------------------------------------------
    | Publication Targets
    |--------------------------------------------------------------------------
    |
    | ERP targets are loaded from erp_connections table (ERPConnection model).
    | PrestaShop shops are loaded dynamically from prestashop_shops table.
    |
    | publication_targets format in PendingProduct:
    |   { "erp_connections": [5, 8], "prestashop_shops": [1, 3] }
    |
    | Legacy format (backward compatible):
    |   { "erp_primary": true, "prestashop_shops": [1] }
    |
    */
    'publication_targets' => [
        'prestashop_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduled Publication (Scheduler)
    |--------------------------------------------------------------------------
    |
    | Konfiguracja auto-publikacji zaplanowanych produktow.
    | Scheduler uruchamiany jest co minute przez Laravel.
    |
    */
    'scheduler' => [
        'enabled' => env('IMPORT_SCHEDULER_ENABLED', true),
        'batch_limit' => env('IMPORT_SCHEDULER_BATCH_LIMIT', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | CSV Import Settings
    |--------------------------------------------------------------------------
    */
    'csv' => [
        'max_file_size_mb' => 50,
        'default_separator' => ';',
        'supported_encodings' => ['UTF-8', 'Windows-1250', 'ISO-8859-2'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Column Mode Settings
    |--------------------------------------------------------------------------
    */
    'column_mode' => [
        'max_rows_per_import' => 500,
        'max_paste_rows' => 200,
    ],

];
