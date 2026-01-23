<?php

/**
 * Categories Permission Module
 *
 * Permissions for category management including tree structure.
 */

return [
    'module' => 'categories',
    'name' => 'Kategorie',
    'description' => 'Zarzadzanie kategoriami i struktura drzewa',
    'icon' => 'folder',
    'order' => 20,
    'color' => 'indigo',

    'permissions' => [
        'create' => [
            'name' => 'categories.create',
            'label' => 'Tworzenie',
            'description' => 'Tworzenie nowych kategorii',
            'dangerous' => false,
        ],
        'read' => [
            'name' => 'categories.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt kategorii',
            'dangerous' => false,
        ],
        'update' => [
            'name' => 'categories.update',
            'label' => 'Edycja',
            'description' => 'Edycja kategorii',
            'dangerous' => false,
        ],
        'delete' => [
            'name' => 'categories.delete',
            'label' => 'Usuwanie',
            'description' => 'Usuwanie kategorii',
            'dangerous' => true,
        ],
        'tree' => [
            'name' => 'categories.tree',
            'label' => 'Struktura',
            'description' => 'Zarzadzanie struktura drzewa kategorii',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['create', 'read', 'update', 'delete', 'tree'],
        'Manager' => ['create', 'read', 'update', 'delete', 'tree'],
        'Editor' => ['read', 'update'],
        'Warehouseman' => [],
        'Salesperson' => [],
        'Claims' => [],
        'User' => ['read'],
    ],
];
