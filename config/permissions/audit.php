<?php

/**
 * Audit Permission Module
 *
 * Permissions for audit log access and export.
 */

return [
    'module' => 'audit',
    'name' => 'Logi Audytu',
    'description' => 'Dostep do logow audytu',
    'icon' => 'clipboard-document-list',
    'order' => 92,
    'color' => 'zinc',

    'permissions' => [
        'read' => [
            'name' => 'audit.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt logow audytu',
            'dangerous' => false,
        ],
        'export' => [
            'name' => 'audit.export',
            'label' => 'Eksport',
            'description' => 'Eksport logow audytu',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'export'],
        'Manager' => ['read'],
        'Edytor' => [],
        'Magazyn' => [],
        'Handlowy' => [],
        'Reklamacje' => [],
        'User' => [],
    ],
];
