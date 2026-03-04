<?php

/**
 * Media Permission Module
 *
 * Permissions for media and file management.
 */

return [
    'module' => 'media',
    'name' => 'Media',
    'description' => 'Zarzadzanie plikami i zdjeciami',
    'icon' => 'photograph',
    'order' => 30,
    'color' => 'purple',

    'permissions' => [
        'create' => [
            'name' => 'media.create',
            'label' => 'Tworzenie',
            'description' => 'Dodawanie nowych mediow',
            'dangerous' => false,
        ],
        'read' => [
            'name' => 'media.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt mediow',
            'dangerous' => false,
        ],
        'update' => [
            'name' => 'media.update',
            'label' => 'Edycja',
            'description' => 'Edycja mediow',
            'dangerous' => false,
        ],
        'delete' => [
            'name' => 'media.delete',
            'label' => 'Usuwanie',
            'description' => 'Usuwanie mediow',
            'dangerous' => true,
        ],
        'upload' => [
            'name' => 'media.upload',
            'label' => 'Upload',
            'description' => 'Upload plikow',
            'dangerous' => false,
        ],
        'manage' => [
            'name' => 'media.manage',
            'label' => 'Zarzadzanie',
            'description' => 'Pelne zarzadzanie mediami w panelu admin',
            'dangerous' => true,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['create', 'read', 'update', 'delete', 'upload', 'manage'],
        'Manager' => ['create', 'read', 'update', 'delete', 'upload', 'manage'],
        'Edytor' => ['create', 'read', 'update', 'delete', 'upload'],
        'Magazyn' => [],
        'Handlowy' => [],
        'Reklamacje' => [],
        'User' => ['read'],
    ],
];
