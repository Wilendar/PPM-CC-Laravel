<?php

/**
 * Stock Permission Module
 *
 * Permissions for stock and warehouse management.
 */

return [
    'module' => 'stock',
    'name' => 'Magazyn',
    'description' => 'Zarzadzanie stanami magazynowymi i dostawami',
    'icon' => 'archive',
    'order' => 50,
    'color' => 'yellow',

    'permissions' => [
        'read' => [
            'name' => 'stock.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt stanow magazynowych',
            'dangerous' => false,
        ],
        'update' => [
            'name' => 'stock.update',
            'label' => 'Edycja',
            'description' => 'Aktualizacja stanow magazynowych',
            'dangerous' => false,
        ],
        'reservations' => [
            'name' => 'stock.reservations',
            'label' => 'Rezerwacje',
            'description' => 'Zarzadzanie rezerwacjami',
            'dangerous' => false,
        ],
        'delivery' => [
            'name' => 'stock.delivery',
            'label' => 'Dostawy',
            'description' => 'Panel dostaw',
            'dangerous' => false,
        ],
        'locations' => [
            'name' => 'stock.locations',
            'label' => 'Lokalizacje',
            'description' => 'Zarzadzanie lokalizacjami magazynowymi',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'update', 'reservations', 'delivery', 'locations'],
        'Manager' => ['read', 'update', 'reservations', 'delivery', 'locations'],
        'Editor' => ['read'],
        'Warehouseman' => ['read', 'update', 'delivery', 'locations'],
        'Salesperson' => ['read', 'reservations'],
        'Claims' => ['read'],
        'User' => ['read'],
    ],
];
