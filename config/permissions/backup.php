<?php

/**
 * Backup Permission Module
 *
 * Permissions for backup management.
 */

return [
    'module' => 'backup',
    'name' => 'Kopie Zapasowe',
    'description' => 'Zarzadzanie kopiami zapasowymi',
    'icon' => 'archive-box',
    'order' => 95,
    'color' => 'stone',

    'permissions' => [
        'read' => [
            'name' => 'backup.read',
            'label' => 'Odczyt',
            'description' => 'Podglad kopii zapasowych',
            'dangerous' => false,
        ],
        'manage' => [
            'name' => 'backup.manage',
            'label' => 'Zarzadzanie',
            'description' => 'Zarzadzanie kopiami',
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
