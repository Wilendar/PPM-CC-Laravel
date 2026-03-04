<?php

/**
 * Visual Editor Permission Module
 *
 * Permissions for visual editor (UVE) access and editing.
 */

return [
    'module' => 'visual-editor',
    'name' => 'Edytor Wizualny',
    'description' => 'Dostep do edytora wizualnego opisow',
    'icon' => 'paint-brush',
    'order' => 35,
    'color' => 'pink',

    'permissions' => [
        'read' => [
            'name' => 'visual-editor.read',
            'label' => 'Odczyt',
            'description' => 'Podglad edytora wizualnego',
            'dangerous' => false,
        ],
        'update' => [
            'name' => 'visual-editor.update',
            'label' => 'Edycja',
            'description' => 'Edycja w edytorze wizualnym',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'update'],
        'Manager' => ['read', 'update'],
        'Edytor' => ['read', 'update'],
        'Magazyn' => [],
        'Handlowy' => [],
        'Reklamacje' => [],
        'User' => [],
    ],
];
