<?php

/**
 * Product Scan System Permissions
 *
 * ETAP_10: Product Scan System - Permission configuration
 *
 * Defines permissions for the product scanning and linking system.
 * Used by Spatie Laravel Permission package.
 *
 * @package Config\Permissions
 * @version 1.0
 * @since ETAP_10 - Product Scan System
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Module Information
    |--------------------------------------------------------------------------
    */
    'module' => [
        'name' => 'scan',
        'label' => 'Skanowanie Produktów',
        'description' => 'System skanowania i powiązań produktów między PPM a ERP/PrestaShop',
        'icon' => 'search',
        'order' => 25,
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'admin.scan.view' => [
            'label' => 'Przeglądanie skanów',
            'description' => 'Dostęp do panelu skanowania i przeglądanie wyników',
            'default_roles' => ['admin', 'manager'],
        ],
        'admin.scan.start' => [
            'label' => 'Uruchamianie skanów',
            'description' => 'Możliwość uruchamiania nowych skanów produktów',
            'default_roles' => ['admin', 'manager'],
        ],
        'admin.scan.link' => [
            'label' => 'Łączenie produktów',
            'description' => 'Możliwość tworzenia powiązań między produktami PPM a źródłami zewnętrznymi',
            'default_roles' => ['admin', 'manager'],
        ],
        'admin.scan.create' => [
            'label' => 'Import produktów',
            'description' => 'Możliwość importowania produktów ze źródeł zewnętrznych do PPM',
            'default_roles' => ['admin'],
        ],
        'admin.scan.bulk' => [
            'label' => 'Akcje masowe',
            'description' => 'Możliwość wykonywania operacji masowych na wynikach skanów',
            'default_roles' => ['admin'],
        ],
        'admin.scan.history' => [
            'label' => 'Historia skanów',
            'description' => 'Dostęp do historii poprzednich skanów',
            'default_roles' => ['admin', 'manager'],
        ],
        'admin.scan.export' => [
            'label' => 'Eksport wyników',
            'description' => 'Możliwość eksportowania wyników skanów do CSV',
            'default_roles' => ['admin', 'manager'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Presets
    |--------------------------------------------------------------------------
    */
    'role_presets' => [
        'admin' => [
            'admin.scan.view',
            'admin.scan.start',
            'admin.scan.link',
            'admin.scan.create',
            'admin.scan.bulk',
            'admin.scan.history',
            'admin.scan.export',
        ],
        'manager' => [
            'admin.scan.view',
            'admin.scan.start',
            'admin.scan.link',
            'admin.scan.history',
            'admin.scan.export',
        ],
        'editor' => [
            'admin.scan.view',
            'admin.scan.history',
        ],
    ],
];
