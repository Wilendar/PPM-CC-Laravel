<?php

/**
 * Bug Reports Permission Module
 *
 * Permissions for bug report management.
 */

return [
    'module' => 'bug-reports',
    'name' => 'Zgloszenia Bledow',
    'description' => 'Zarzadzanie zgloszeniami bledow',
    'icon' => 'bug-ant',
    'order' => 90,
    'color' => 'rose',

    'permissions' => [
        'read' => [
            'name' => 'bug-reports.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt zgloszen bledow',
            'dangerous' => false,
        ],
        'create' => [
            'name' => 'bug-reports.create',
            'label' => 'Tworzenie',
            'description' => 'Tworzenie zgloszen bledow',
            'dangerous' => false,
        ],
        'update' => [
            'name' => 'bug-reports.update',
            'label' => 'Edycja',
            'description' => 'Edycja zgloszen bledow',
            'dangerous' => false,
        ],
        'resolve' => [
            'name' => 'bug-reports.resolve',
            'label' => 'Rozwiazywanie',
            'description' => 'Rozwiazywanie zgloszen bledow',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'create', 'update', 'resolve'],
        'Manager' => ['read', 'create'],
        'Edytor' => ['read', 'create'],
        'Magazyn' => ['read', 'create'],
        'Handlowy' => ['read', 'create'],
        'Reklamacje' => ['read', 'create'],
        'User' => ['read', 'create'],
    ],
];
