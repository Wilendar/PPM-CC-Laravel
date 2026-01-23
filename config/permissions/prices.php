<?php

/**
 * Prices Permission Module
 *
 * Permissions for price management including price groups and cost prices.
 */

return [
    'module' => 'prices',
    'name' => 'Ceny',
    'description' => 'Zarzadzanie cenami i grupami cenowymi',
    'icon' => 'currency-dollar',
    'order' => 40,
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
        'groups' => [
            'name' => 'prices.groups',
            'label' => 'Grupy cenowe',
            'description' => 'Zarzadzanie grupami cenowymi',
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
        'Admin' => ['read', 'update', 'groups', 'cost'],
        'Manager' => ['read', 'update', 'groups', 'cost'],
        'Editor' => [],
        'Warehouseman' => [],
        'Salesperson' => ['read'],
        'Claims' => [],
        'User' => [],
    ],
];
