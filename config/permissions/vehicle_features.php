<?php

/**
 * Vehicle Features Permission Module
 *
 * Per-tab permissions for /admin/features/vehicles panel.
 * Tabs: browser, library, templates + bulk_assign action.
 */

return [
    'module' => 'vehicle_features',
    'name' => 'Cechy pojazdow',
    'description' => 'Zarzadzanie cechami pojazdow (przegladarka, biblioteka, szablony)',
    'icon' => 'truck',
    'order' => 17,
    'color' => 'indigo',

    'permissions' => [
        'browser_read' => [
            'name' => 'vehicle_features.browser.read',
            'label' => 'Przegladarka - odczyt',
            'description' => 'Przegladanie cech pojazdow',
            'dangerous' => false,
        ],
        'browser_assign' => [
            'name' => 'vehicle_features.browser.assign',
            'label' => 'Przegladarka - przypisywanie',
            'description' => 'Przypisywanie cech do produktow',
            'dangerous' => false,
        ],
        'library_read' => [
            'name' => 'vehicle_features.library.read',
            'label' => 'Biblioteka - odczyt',
            'description' => 'Przegladanie biblioteki cech',
            'dangerous' => false,
        ],
        'library_edit' => [
            'name' => 'vehicle_features.library.edit',
            'label' => 'Biblioteka - edycja',
            'description' => 'Edycja biblioteki cech',
            'dangerous' => false,
        ],
        'templates_read' => [
            'name' => 'vehicle_features.templates.read',
            'label' => 'Szablony - odczyt',
            'description' => 'Przegladanie szablonow cech',
            'dangerous' => false,
        ],
        'templates_edit' => [
            'name' => 'vehicle_features.templates.edit',
            'label' => 'Szablony - edycja',
            'description' => 'Edycja szablonow cech',
            'dangerous' => false,
        ],
        'bulk_assign' => [
            'name' => 'vehicle_features.bulk_assign',
            'label' => 'Masowe przypisywanie',
            'description' => 'Masowe przypisywanie cech do wielu produktow',
            'dangerous' => true,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['browser_read', 'browser_assign', 'library_read', 'library_edit', 'templates_read', 'templates_edit', 'bulk_assign'],
        'Manager' => ['browser_read', 'browser_assign', 'library_read', 'templates_read', 'templates_edit', 'bulk_assign'],
        'Edytor' => ['browser_read', 'library_read', 'templates_read'],
        'Magazyn' => [],
        'Handlowy' => [],
        'Reklamacje' => [],
        'User' => [],
    ],
];
