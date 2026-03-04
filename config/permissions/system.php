<?php

/**
 * System Permission Module
 *
 * Permissions for system administration.
 * v2.0: Removed audit/reports (moved to dedicated modules), added system.manage.
 */

return [
    'module' => 'system',
    'name' => 'System',
    'description' => 'Administracja systemu',
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
        'manage' => [
            'name' => 'system.manage',
            'label' => 'Zarzadzanie',
            'description' => 'Pelne zarzadzanie ustawieniami systemu',
            'dangerous' => true,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['config', 'manage'],
        'Manager' => [],
        'Edytor' => [],
        'Magazyn' => [],
        'Handlowy' => [],
        'Reklamacje' => [],
        'User' => [],
    ],
];
