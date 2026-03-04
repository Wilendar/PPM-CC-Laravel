<?php

/**
 * Sessions Management Permission Module
 *
 * Permissions for user session management.
 */

return [
    'module' => 'sessions',
    'name' => 'Sesje Uzytkownikow',
    'description' => 'Zarzadzanie sesjami uzytkownikow',
    'icon' => 'computer-desktop',
    'order' => 85,
    'color' => 'slate',

    'permissions' => [
        'read' => [
            'name' => 'sessions.read',
            'label' => 'Odczyt',
            'description' => 'Podglad sesji uzytkownikow',
            'dangerous' => false,
        ],
        'manage' => [
            'name' => 'sessions.manage',
            'label' => 'Zarzadzanie',
            'description' => 'Zarzadzanie sesjami',
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
