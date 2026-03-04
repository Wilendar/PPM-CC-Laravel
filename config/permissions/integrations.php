<?php

/**
 * Integrations Permission Module
 *
 * Permissions for external system integrations (PrestaShop, ERP).
 */

return [
    'module' => 'integrations',
    'name' => 'Integracje',
    'description' => 'Integracje z systemami zewnetrznymi',
    'icon' => 'link',
    'order' => 60,
    'color' => 'indigo',

    'permissions' => [
        'read' => [
            'name' => 'integrations.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt statusu integracji',
            'dangerous' => false,
        ],
        'test' => [
            'name' => 'integrations.test',
            'label' => 'Testowanie',
            'description' => 'Testowanie polaczen z systemami zewnetrznymi',
            'dangerous' => false,
        ],
        'sync' => [
            'name' => 'integrations.sync',
            'label' => 'Synchronizacja',
            'description' => 'Uruchamianie synchronizacji',
            'dangerous' => false,
        ],
        'config' => [
            'name' => 'integrations.config',
            'label' => 'Konfiguracja',
            'description' => 'Konfiguracja integracji',
            'dangerous' => true,
        ],
        'prestashop' => [
            'name' => 'integrations.prestashop',
            'label' => 'PrestaShop',
            'description' => 'Integracja z PrestaShop',
            'dangerous' => false,
        ],
        'erp' => [
            'name' => 'integrations.erp',
            'label' => 'ERP',
            'description' => 'Integracje z systemami ERP',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'test', 'sync', 'config', 'prestashop', 'erp'],
        'Manager' => ['read', 'test', 'sync', 'config', 'prestashop', 'erp'],
        'Edytor' => ['read'],
        'Magazyn' => ['read'],
        'Handlowy' => ['read'],
        'Reklamacje' => [],
        'User' => ['read'],
    ],
];
