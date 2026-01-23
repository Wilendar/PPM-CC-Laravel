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
        'Admin' => ['read', 'sync', 'config', 'prestashop', 'erp'],
        'Manager' => ['read', 'sync', 'config', 'prestashop', 'erp'],
        'Editor' => ['read'],
        'Warehouseman' => ['read'],
        'Salesperson' => ['read'],
        'Claims' => [],
        'User' => ['read'],
    ],
];
