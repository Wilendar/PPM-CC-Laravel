<?php

/**
 * Job Types Configuration - ETAP_07c FAZA 4
 *
 * Configuration for job progress bar display and behavior.
 * Used by JobProgressBar component for UI rendering.
 *
 * @package Config
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Job Type Definitions
    |--------------------------------------------------------------------------
    |
    | Each job type defines:
    | - icon: Heroicon name (used in UI)
    | - color: Tailwind color class (without 'bg-' prefix)
    | - label: Human-readable Polish label
    | - cancellable: Whether job can be cancelled mid-execution
    | - requires_confirmation: Whether completion requires user action
    |
    */

    'import' => [
        'icon' => 'arrow-down-tray',
        'color' => 'blue',
        'label' => 'Import produktów',
        'cancellable' => false,
        'requires_confirmation' => false,
    ],

    'category_analysis' => [
        'icon' => 'magnifying-glass',
        'color' => 'yellow',
        'label' => 'Analiza kategorii',
        'cancellable' => false,
        'requires_confirmation' => true,
    ],

    'sync' => [
        'icon' => 'arrow-path',
        'color' => 'cyan',
        'label' => 'Synchronizacja produktów',
        'cancellable' => true,
        'requires_confirmation' => false,
    ],

    'bulk_export' => [
        'icon' => 'arrow-up-tray',
        'color' => 'green',
        'label' => 'Eksport produktów',
        'cancellable' => true,
        'requires_confirmation' => false,
    ],

    'bulk_update' => [
        'icon' => 'pencil-square',
        'color' => 'orange',
        'label' => 'Aktualizacja produktów',
        'cancellable' => true,
        'requires_confirmation' => false,
    ],

    'stock_sync' => [
        'icon' => 'cube',
        'color' => 'purple',
        'label' => 'Synchronizacja stanów',
        'cancellable' => true,
        'requires_confirmation' => false,
    ],

    'price_sync' => [
        'icon' => 'currency-dollar',
        'color' => 'emerald',
        'label' => 'Synchronizacja cen',
        'cancellable' => true,
        'requires_confirmation' => false,
    ],

    'category_sync' => [
        'icon' => 'folder',
        'color' => 'indigo',
        'label' => 'Synchronizacja kategorii',
        'cancellable' => false,
        'requires_confirmation' => false,
    ],

    'category_delete' => [
        'icon' => 'trash',
        'color' => 'red',
        'label' => 'Usuwanie kategorii',
        'cancellable' => false,
        'requires_confirmation' => false,
    ],

    'single_product_sync' => [
        'icon' => 'arrow-path',
        'color' => 'sky',
        'label' => 'Sync pojedynczego produktu',
        'cancellable' => false,
        'requires_confirmation' => false,
    ],

    'feature_sync' => [
        'icon' => 'tag',
        'color' => 'amber',
        'label' => 'Synchronizacja cech',
        'cancellable' => true,
        'requires_confirmation' => false,
    ],

    'feature_import' => [
        'icon' => 'arrow-down-tray',
        'color' => 'teal',
        'label' => 'Import cech z PrestaShop',
        'cancellable' => false,
        'requires_confirmation' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Mode Labels
    |--------------------------------------------------------------------------
    |
    | Labels for sync mode display in metadata
    |
    */

    'sync_modes' => [
        'full_sync' => 'Pełna synchronizacja',
        'prices_only' => 'Tylko ceny',
        'stock_only' => 'Tylko stany magazynowe',
        'descriptions_only' => 'Tylko opisy',
        'images_only' => 'Tylko zdjęcia',
        'categories_only' => 'Tylko kategorie',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Job Type
    |--------------------------------------------------------------------------
    |
    | Fallback configuration for unknown job types
    |
    */

    'default' => [
        'icon' => 'cog',
        'color' => 'gray',
        'label' => 'Zadanie',
        'cancellable' => false,
        'requires_confirmation' => false,
    ],
];
