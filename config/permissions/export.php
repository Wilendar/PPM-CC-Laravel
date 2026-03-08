<?php

/**
 * Export & Feeds Permission Module
 *
 * Permissions for export profiles and public feed management.
 */

return [
    'module' => 'export',
    'name' => 'Eksport & Feedy',
    'description' => 'Zarzadzanie profilami eksportu i feedami publicznymi',
    'icon' => 'arrow-up-tray',
    'order' => 16,
    'color' => 'purple',

    'permissions' => [
        'read' => [
            'name' => 'export.read',
            'label' => 'Dostep do panelu',
            'description' => 'Dostep do panelu eksportu i przegladanie profili',
            'dangerous' => false,
        ],

        'create' => [
            'name' => 'export.create',
            'label' => 'Tworzenie profili',
            'description' => 'Tworzenie nowych profili eksportu',
            'dangerous' => false,
        ],

        'update' => [
            'name' => 'export.update',
            'label' => 'Edycja profili',
            'description' => 'Edycja istniejacych profili eksportu',
            'dangerous' => false,
        ],

        'delete' => [
            'name' => 'export.delete',
            'label' => 'Usuwanie profili',
            'description' => 'Usuwanie profili eksportu',
            'dangerous' => true,
        ],

        'manage_feeds' => [
            'name' => 'export.manage_feeds',
            'label' => 'Zarzadzanie feedami',
            'description' => 'Zarzadzanie feedami publicznymi (tworzenie, edycja, usuwanie)',
            'dangerous' => true,
        ],

        'view_logs' => [
            'name' => 'export.view_logs',
            'label' => 'Logi eksportu',
            'description' => 'Przegladanie logow i historii operacji eksportu',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'create', 'update', 'delete', 'manage_feeds', 'view_logs'],
        'Manager' => ['read', 'create', 'update', 'delete', 'manage_feeds', 'view_logs'],
        'Edytor' => ['read'],
        'Magazyn' => [],
        'Handlowy' => ['read'],
        'Reklamacje' => [],
        'User' => [],
    ],
];
