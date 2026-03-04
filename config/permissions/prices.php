<?php

/**
 * Prices Permission Module
 *
 * Permissions for price management in ProductForm context.
 * Price Groups management moved to separate price_groups module.
 */

return [
    'module' => 'prices',
    'parent_module' => 'products',
    'name' => 'Ceny',
    'description' => 'Zarzadzanie cenami produktow (odczyt/edycja w ProductForm)',
    'icon' => 'currency-dollar',
    'order' => 11,
    'color' => 'green',

    'permissions' => [
        'read' => [
            'name' => 'prices.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt cen sprzedazy',
            'dangerous' => false,
        ],
        'update' => [
            'name' => 'prices.update',
            'label' => 'Edycja',
            'description' => 'Edycja cen',
            'dangerous' => false,
        ],
        'cost' => [
            'name' => 'prices.cost',
            'label' => 'Ceny zakupu',
            'description' => 'Dostep do cen zakupu (poufne)',
            'dangerous' => true,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'update', 'cost'],
        'Manager' => ['read', 'update', 'cost'],
        'Edytor' => [],
        'Magazyn' => [],
        'Handlowy' => ['read'],
        'Reklamacje' => [],
        'User' => [],
    ],
];
