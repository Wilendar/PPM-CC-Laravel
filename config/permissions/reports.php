<?php

/**
 * Reports Permission Module
 *
 * Permissions for report viewing and export.
 */

return [
    'module' => 'reports',
    'name' => 'Raporty',
    'description' => 'Dostep do raportow',
    'icon' => 'chart-bar',
    'order' => 80,
    'color' => 'violet',

    'permissions' => [
        'read' => [
            'name' => 'reports.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt raportow',
            'dangerous' => false,
        ],
        'export' => [
            'name' => 'reports.export',
            'label' => 'Eksport',
            'description' => 'Eksport raportow',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'export'],
        'Manager' => ['read', 'export'],
        'Edytor' => ['read'],
        'Magazyn' => [],
        'Handlowy' => [],
        'Reklamacje' => [],
        'User' => [],
    ],
];
