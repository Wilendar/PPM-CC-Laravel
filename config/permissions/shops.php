<?php

/**
 * Shops Permission Module
 *
 * Permissions for shop management including sync, import/export, and CSS editing.
 */

return [
    'module' => 'shops',
    'name' => 'Sklepy',
    'description' => 'Zarzadzanie sklepami PrestaShop',
    'icon' => 'building-storefront',
    'order' => 12,
    'color' => 'blue',

    'permissions' => [
        'read' => [
            'name' => 'shops.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt sklepow',
            'dangerous' => false,
        ],
        'create' => [
            'name' => 'shops.create',
            'label' => 'Tworzenie',
            'description' => 'Tworzenie nowych sklepow',
            'dangerous' => false,
        ],
        'update' => [
            'name' => 'shops.update',
            'label' => 'Edycja',
            'description' => 'Edycja sklepow',
            'dangerous' => false,
        ],
        'delete' => [
            'name' => 'shops.delete',
            'label' => 'Usuwanie',
            'description' => 'Usuwanie sklepow',
            'dangerous' => true,
        ],
        'sync' => [
            'name' => 'shops.sync',
            'label' => 'Synchronizacja',
            'description' => 'Synchronizacja danych ze sklepami',
            'dangerous' => false,
        ],
        'export' => [
            'name' => 'shops.export',
            'label' => 'Eksport',
            'description' => 'Eksport danych sklepow',
            'dangerous' => false,
        ],
        'import' => [
            'name' => 'shops.import',
            'label' => 'Import',
            'description' => 'Import danych do sklepow',
            'dangerous' => false,
        ],
        'css_edit' => [
            'name' => 'shops.css_edit',
            'label' => 'Edycja CSS',
            'description' => 'Edycja CSS sklepow',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'create', 'update', 'delete', 'sync', 'export', 'import', 'css_edit'],
        'Manager' => ['read', 'sync'],
        'Edytor' => ['read'],
        'Magazyn' => [],
        'Handlowy' => [],
        'Reklamacje' => [],
        'User' => [],
    ],
];
