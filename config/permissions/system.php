<?php

/**
 * System Permission Module
 *
 * Permissions for system administration and maintenance.
 */

return [
    'module' => 'system',
    'name' => 'System',
    'description' => 'Administracja i konserwacja systemu',
    'icon' => 'cog',
    'order' => 100,
    'color' => 'gray',

    'permissions' => [
        'config' => [
            'name' => 'system.config',
            'label' => 'Konfiguracja',
            'description' => 'Konfiguracja systemu',
            'dangerous' => true,
        ],
        'maintenance' => [
            'name' => 'system.maintenance',
            'label' => 'Konserwacja',
            'description' => 'Konserwacja systemu',
            'dangerous' => true,
        ],
        'reports' => [
            'name' => 'reports.read',
            'label' => 'Raporty',
            'description' => 'Odczyt raportow',
            'dangerous' => false,
        ],
        'audit' => [
            'name' => 'audit.read',
            'label' => 'Audyt',
            'description' => 'Dostep do logów audytu',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['config', 'maintenance', 'reports', 'audit'],
        'Manager' => ['reports'],
        'Editor' => [],
        'Warehouseman' => [],
        'Salesperson' => [],
        'Claims' => [],
        'User' => [],
    ],
];
