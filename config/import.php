<?php

/**
 * FAZA 9.1.3: Import Panel Configuration
 *
 * Konfiguracja ERP Glownego, targetow publikacji i schedulera.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | ERP Glowny (Primary)
    |--------------------------------------------------------------------------
    |
    | Konfigurowalny per instalacja. Definiuje domyslny system docelowy
    | przy publikacji produktow z panelu importu.
    |
    | Typy: ppm, subiekt_gt, baselinker, dynamics
    |
    */
    'erp_primary' => [
        'enabled' => true,
        'name' => env('IMPORT_ERP_PRIMARY_NAME', 'PPM'),
        'type' => env('IMPORT_ERP_PRIMARY_TYPE', 'ppm'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Publication Targets
    |--------------------------------------------------------------------------
    |
    | Konfiguracja dodatkowych targetow publikacji.
    | PrestaShop shops sa ladowane dynamicznie z bazy danych.
    |
    */
    'publication_targets' => [
        'prestashop_enabled' => true,
        'erp_exports' => [
            // Dodatkowe systemy ERP do eksportu
            // 'subiekt_gt' => ['enabled' => true, 'name' => 'Subiekt GT'],
            // 'baselinker' => ['enabled' => true, 'name' => 'BaseLinker'],
            // 'dynamics' => ['enabled' => false, 'name' => 'Microsoft Dynamics'],
        ],
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
