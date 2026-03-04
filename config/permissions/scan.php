<?php

/**
 * Product Scan System Permissions
 *
 * ETAP_10: Product Scan System - Permission configuration
 * v2.0: Migrated from nested module.name format to flat format.
 */

return [
    'module' => 'scan',
    'name' => 'Skanowanie Produktow',
    'description' => 'System skanowania i powiazan produktow miedzy PPM a ERP/PrestaShop',
    'icon' => 'magnifying-glass',
    'order' => 25,
    'color' => 'cyan',

    'permissions' => [
        'read' => [
            'name' => 'scan.read',
            'label' => 'Przegladanie skanow',
            'description' => 'Dostep do panelu skanowania i przegladanie wynikow',
            'dangerous' => false,
        ],
        'start' => [
            'name' => 'scan.start',
            'label' => 'Uruchamianie skanow',
            'description' => 'Mozliwosc uruchamiania nowych skanow produktow',
            'dangerous' => false,
        ],
        'link' => [
            'name' => 'scan.link',
            'label' => 'Laczenie produktow',
            'description' => 'Tworzenie powiazan miedzy produktami PPM a zrodlami zewnetrznymi',
            'dangerous' => false,
        ],
        'create' => [
            'name' => 'scan.create',
            'label' => 'Import produktow',
            'description' => 'Importowanie produktow ze zrodel zewnetrznych do PPM',
            'dangerous' => false,
        ],
        'bulk' => [
            'name' => 'scan.bulk',
            'label' => 'Akcje masowe',
            'description' => 'Wykonywanie operacji masowych na wynikach skanow',
            'dangerous' => true,
        ],
        'history' => [
            'name' => 'scan.history',
            'label' => 'Historia skanow',
            'description' => 'Dostep do historii poprzednich skanow',
            'dangerous' => false,
        ],
        'export' => [
            'name' => 'scan.export',
            'label' => 'Eksport wynikow',
            'description' => 'Eksportowanie wynikow skanow do CSV',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'start', 'link', 'create', 'bulk', 'history', 'export'],
        'Manager' => ['read', 'start', 'link', 'history', 'export'],
        'Edytor' => ['read', 'history'],
        'Magazyn' => [],
        'Handlowy' => [],
        'Reklamacje' => [],
        'User' => [],
    ],
];
