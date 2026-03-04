<?php

/**
 * Maintenance Permission Module
 *
 * Permissions for database maintenance operations.
 */

return [
    'module' => 'maintenance',
    'name' => 'Konserwacja',
    'description' => 'Konserwacja bazy danych',
    'icon' => 'wrench-screwdriver',
    'order' => 96,
    'color' => 'neutral',

    'permissions' => [
        'read' => [
            'name' => 'maintenance.read',
            'label' => 'Odczyt',
            'description' => 'Podglad stanu bazy',
            'dangerous' => false,
        ],
        'manage' => [
            'name' => 'maintenance.manage',
            'label' => 'Zarzadzanie',
            'description' => 'Wykonywanie konserwacji',
            'dangerous' => true,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'manage'],
        'Manager' => [],
        'Edytor' => [],
        'Magazyn' => [],
        'Handlowy' => [],
        'Reklamacje' => [],
        'User' => [],
    ],
];
