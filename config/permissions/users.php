<?php

/**
 * Users Permission Module
 *
 * Permissions for user and role management.
 */

return [
    'module' => 'users',
    'name' => 'Uzytkownicy',
    'description' => 'Zarzadzanie uzytkownikami i rolami',
    'icon' => 'users',
    'order' => 90,
    'color' => 'gray',

    'permissions' => [
        'read' => [
            'name' => 'users.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt uzytkownikow',
            'dangerous' => false,
        ],
        'create' => [
            'name' => 'users.create',
            'label' => 'Tworzenie',
            'description' => 'Tworzenie uzytkownikow',
            'dangerous' => false,
        ],
        'update' => [
            'name' => 'users.update',
            'label' => 'Edycja',
            'description' => 'Edycja uzytkownikow',
            'dangerous' => false,
        ],
        'delete' => [
            'name' => 'users.delete',
            'label' => 'Usuwanie',
            'description' => 'Usuwanie uzytkownikow',
            'dangerous' => true,
        ],
        'roles' => [
            'name' => 'users.roles',
            'label' => 'Role',
            'description' => 'Zarzadzanie rolami uzytkownikow',
            'dangerous' => true,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'create', 'update', 'delete', 'roles'],
        'Manager' => [],
        'Editor' => [],
        'Warehouseman' => [],
        'Salesperson' => [],
        'Claims' => [],
        'User' => [],
    ],
];
