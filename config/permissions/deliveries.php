<?php

/**
 * Deliveries Permission Module
 *
 * Permissions for delivery management including receiving and documentation.
 */

return [
    'module' => 'deliveries',
    'name' => 'Dostawy',
    'description' => 'Zarzadzanie dostawami',
    'icon' => 'truck',
    'order' => 45,
    'color' => 'orange',

    'permissions' => [
        'read' => [
            'name' => 'deliveries.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt dostaw',
            'dangerous' => false,
        ],
        'create' => [
            'name' => 'deliveries.create',
            'label' => 'Tworzenie',
            'description' => 'Tworzenie nowych dostaw',
            'dangerous' => false,
        ],
        'receive' => [
            'name' => 'deliveries.receive',
            'label' => 'Przyjecia',
            'description' => 'Przyjecia magazynowe',
            'dangerous' => false,
        ],
        'documents' => [
            'name' => 'deliveries.documents',
            'label' => 'Dokumenty',
            'description' => 'Dokumenty odpraw',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'create', 'receive', 'documents'],
        'Manager' => ['read'],
        'Edytor' => [],
        'Magazyn' => ['read', 'create', 'receive'],
        'Handlowy' => [],
        'Reklamacje' => [],
        'User' => [],
    ],
];
