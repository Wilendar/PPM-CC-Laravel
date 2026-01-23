<?php

/**
 * Claims Permission Module
 *
 * Permissions for claims and returns management.
 */

return [
    'module' => 'claims',
    'name' => 'Reklamacje',
    'description' => 'Zarzadzanie reklamacjami i zwrotami',
    'icon' => 'exclamation-circle',
    'order' => 80,
    'color' => 'red',

    'permissions' => [
        'read' => [
            'name' => 'claims.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt reklamacji',
            'dangerous' => false,
        ],
        'create' => [
            'name' => 'claims.create',
            'label' => 'Tworzenie',
            'description' => 'Tworzenie reklamacji',
            'dangerous' => false,
        ],
        'update' => [
            'name' => 'claims.update',
            'label' => 'Obsluga',
            'description' => 'Obsluga reklamacji',
            'dangerous' => false,
        ],
        'resolve' => [
            'name' => 'claims.resolve',
            'label' => 'Rozwiazywanie',
            'description' => 'Rozwiazywanie reklamacji',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'create', 'update', 'resolve'],
        'Manager' => [],
        'Editor' => [],
        'Warehouseman' => [],
        'Salesperson' => [],
        'Claims' => ['read', 'create', 'update', 'resolve'],
        'User' => [],
    ],
];
