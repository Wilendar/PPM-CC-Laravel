<?php

/**
 * Price Groups Permission Module
 *
 * Separated from prices module for independent access control.
 * Sidebar link "Grupy cenowe" uses price_groups.read (not prices.read).
 */

return [
    'module' => 'price_groups',
    'name' => 'Grupy cenowe',
    'description' => 'Zarzadzanie grupami cenowymi (oddzielny panel administracyjny)',
    'icon' => 'tag',
    'order' => 41,
    'color' => 'green',

    'permissions' => [
        'read' => [
            'name' => 'price_groups.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt grup cenowych',
            'dangerous' => false,
        ],
        'manage' => [
            'name' => 'price_groups.manage',
            'label' => 'Zarzadzanie',
            'description' => 'Tworzenie i edycja grup cenowych',
            'dangerous' => false,
        ],
        'delete' => [
            'name' => 'price_groups.delete',
            'label' => 'Usuwanie',
            'description' => 'Usuwanie grup cenowych',
            'dangerous' => true,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'manage', 'delete'],
        'Manager' => ['read', 'manage'],
        'Edytor' => [],
        'Magazyn' => [],
        'Handlowy' => ['read'],
        'Reklamacje' => [],
        'User' => [],
    ],
];
