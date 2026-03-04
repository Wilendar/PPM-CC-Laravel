<?php

/**
 * Dashboard Permission Module
 *
 * Permissions for dashboard access.
 */

return [
    'module' => 'dashboard',
    'name' => 'Dashboard',
    'description' => 'Dostep do panelu glownego',
    'icon' => 'home',
    'order' => 1,
    'color' => 'gray',

    'permissions' => [
        'read' => [
            'name' => 'dashboard.read',
            'label' => 'Odczyt',
            'description' => 'Dostep do dashboardu',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read'],
        'Manager' => ['read'],
        'Edytor' => ['read'],
        'Magazyn' => ['read'],
        'Handlowy' => ['read'],
        'Reklamacje' => ['read'],
        'User' => ['read'],
    ],
];
