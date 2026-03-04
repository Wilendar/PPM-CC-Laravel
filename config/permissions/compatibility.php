<?php

/**
 * Compatibility Permission Module
 *
 * Permissions for parts compatibility management.
 */

return [
    'module' => 'compatibility',
    'name' => 'Dopasowania Czesci',
    'description' => 'Zarzadzanie dopasowaniami czesci',
    'icon' => 'puzzle-piece',
    'order' => 16,
    'color' => 'emerald',

    'permissions' => [
        'read' => [
            'name' => 'compatibility.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt dopasowan czesci',
            'dangerous' => false,
        ],
        'update' => [
            'name' => 'compatibility.update',
            'label' => 'Edycja',
            'description' => 'Edycja dopasowan czesci',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'update'],
        'Manager' => ['read', 'update'],
        'Edytor' => ['read', 'update'],
        'Magazyn' => [],
        'Handlowy' => [],
        'Reklamacje' => [],
        'User' => [],
    ],
];
