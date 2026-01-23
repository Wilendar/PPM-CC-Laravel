<?php

/**
 * Products Permission Module
 *
 * Permissions for product management including CRUD, import/export, and variants.
 */

return [
    'module' => 'products',
    'name' => 'Produkty',
    'description' => 'Zarzadzanie produktami i wariantami',
    'icon' => 'cube',
    'order' => 10,
    'color' => 'blue',

    'permissions' => [
        'create' => [
            'name' => 'products.create',
            'label' => 'Tworzenie',
            'description' => 'Tworzenie nowych produktow',
            'dangerous' => false,
        ],
        'read' => [
            'name' => 'products.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt produktow',
            'dangerous' => false,
        ],
        'update' => [
            'name' => 'products.update',
            'label' => 'Edycja',
            'description' => 'Edycja produktow',
            'dangerous' => false,
        ],
        'delete' => [
            'name' => 'products.delete',
            'label' => 'Usuwanie',
            'description' => 'Usuwanie produktow',
            'dangerous' => true,
        ],
        'export' => [
            'name' => 'products.export',
            'label' => 'Eksport',
            'description' => 'Eksport produktow',
            'dangerous' => false,
        ],
        'import' => [
            'name' => 'products.import',
            'label' => 'Import',
            'description' => 'Import produktow',
            'dangerous' => false,
        ],
        'variants' => [
            'name' => 'products.variants',
            'label' => 'Warianty',
            'description' => 'Zarzadzanie wariantami produktow',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['create', 'read', 'update', 'delete', 'export', 'import', 'variants'],
        'Manager' => ['create', 'read', 'update', 'delete', 'export', 'import', 'variants'],
        'Editor' => ['read', 'update', 'export'],
        'Warehouseman' => ['read'],
        'Salesperson' => ['read'],
        'Claims' => ['read'],
        'User' => ['read'],
    ],
];
