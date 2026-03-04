<?php

/**
 * Stock Permission Module
 *
 * Permissions for stock and warehouse management.
 * Child of products module in PermissionMatrix hierarchy.
 */

return [
    'module' => 'stock',
    'parent_module' => 'products',
    'name' => 'Magazyn',
    'description' => 'Zarzadzanie stanami magazynowymi i dostawami',
    'icon' => 'archive',
    'order' => 13,
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

        // Granularne uprawnienia do odblokowywania kolumn stanów magazynowych
        'unlock_quantity' => [
            'name' => 'stock.unlock_quantity',
            'label' => 'Odblokuj stan dostepny',
            'description' => 'Mozliwosc odblokowania i edycji kolumny "Stan dostepny". Zmiany beda synchronizowane do ERP.',
            'dangerous' => true,
        ],
        'unlock_reserved' => [
            'name' => 'stock.unlock_reserved',
            'label' => 'Odblokuj rezerwacje',
            'description' => 'Mozliwosc odblokowania i edycji kolumny "Zarezerwowane".',
            'dangerous' => true,
        ],
        'unlock_minimum' => [
            'name' => 'stock.unlock_minimum',
            'label' => 'Odblokuj minimum',
            'description' => 'Mozliwosc odblokowania i edycji kolumny "Minimum". Zmiany beda synchronizowane do ERP.',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'update', 'reservations', 'delivery', 'locations', 'unlock_quantity', 'unlock_reserved', 'unlock_minimum'],
        'Manager' => ['read', 'update', 'reservations', 'delivery', 'locations', 'unlock_minimum'],
        'Edytor' => ['read'],
        'Magazyn' => ['read', 'update', 'delivery', 'locations'],
        'Handlowy' => ['read', 'reservations'],
        'Reklamacje' => ['read'],
        'User' => ['read'],
    ],
];
