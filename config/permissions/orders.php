<?php

/**
 * Orders Permission Module
 *
 * Permissions for order and sales management.
 */

return [
    'module' => 'orders',
    'name' => 'Zamowienia',
    'description' => 'Zarzadzanie zamowieniami i sprzedaza',
    'icon' => 'shopping-cart',
    'order' => 70,
    'color' => 'blue',

    'permissions' => [
        'read' => [
            'name' => 'orders.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt zamowien',
            'dangerous' => false,
        ],
        'create' => [
            'name' => 'orders.create',
            'label' => 'Tworzenie',
            'description' => 'Tworzenie zamowien',
            'dangerous' => false,
        ],
        'update' => [
            'name' => 'orders.update',
            'label' => 'Edycja',
            'description' => 'Edycja zamowien',
            'dangerous' => false,
        ],
        'reservations' => [
            'name' => 'orders.reservations',
            'label' => 'Rezerwacje',
            'description' => 'Rezerwacje z kontenera',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'create', 'update', 'reservations'],
        'Manager' => ['read', 'create', 'update', 'reservations'],
        'Editor' => [],
        'Warehouseman' => [],
        'Salesperson' => ['read', 'create', 'update', 'reservations'],
        'Claims' => ['read'],
        'User' => [],
    ],
];
