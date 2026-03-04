<?php

/**
 * Suppliers Permission Module
 *
 * Permissions for supplier management.
 */

return [
    'module' => 'suppliers',
    'name' => 'Dostawcy',
    'description' => 'Zarzadzanie dostawcami',
    'icon' => 'truck',
    'order' => 17,
    'color' => 'amber',

    'permissions' => [
        'read' => [
            'name' => 'suppliers.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt dostawcow',
            'dangerous' => false,
        ],
        'create' => [
            'name' => 'suppliers.create',
            'label' => 'Tworzenie',
            'description' => 'Tworzenie nowych dostawcow',
            'dangerous' => false,
        ],
        'update' => [
            'name' => 'suppliers.update',
            'label' => 'Edycja',
            'description' => 'Edycja dostawcow',
            'dangerous' => false,
        ],
        'delete' => [
            'name' => 'suppliers.delete',
            'label' => 'Usuwanie',
            'description' => 'Usuwanie dostawcow',
            'dangerous' => true,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'create', 'update', 'delete'],
        'Manager' => ['read', 'create', 'update'],
        'Edytor' => ['read'],
        'Magazyn' => [],
        'Handlowy' => [],
        'Reklamacje' => [],
        'User' => [],
    ],
];
