<?php

/**
 * Permission Module Template
 *
 * INSTRUCTIONS FOR AI AGENTS:
 * 1. Copy this file to config/permissions/{module_name}.php
 * 2. Replace all {placeholders} with actual values
 * 3. Add/remove permissions as needed
 * 4. Run: php artisan db:seed --class=RolePermissionSeeder
 * 5. Run: php artisan cache:clear
 *
 * @see config/permissions/README.md for detailed documentation
 */

return [
    // =========================================================================
    // MODULE METADATA
    // =========================================================================

    // Unique identifier (lowercase, no spaces, used in permission names)
    // Example: 'products', 'categories', 'price_groups'
    'module' => '{module_name}',

    // Display name in Polish (shown in UI)
    // Example: 'Produkty', 'Kategorie', 'Grupy Cenowe'
    'name' => '{Module Display Name}',

    // Short description (optional, shown in UI tooltip)
    'description' => '{Module description}',

    // Heroicon name for UI (see README.md for available icons)
    // Common: 'cube', 'folder', 'currency-dollar', 'users', 'cog', 'document'
    'icon' => 'document',

    // Sort order in permission matrix (lower = first)
    // Recommended: 10, 20, 30... for main modules, 100+ for additional
    'order' => 100,

    // Accent color for UI (blue, green, red, yellow, purple, gray, indigo, pink)
    'color' => 'gray',

    // =========================================================================
    // PERMISSIONS
    // =========================================================================

    'permissions' => [
        // Standard CRUD permissions (modify as needed)

        'create' => [
            'name' => '{module_name}.create',
            'label' => 'Tworzenie',
            'description' => 'Tworzenie nowych rekordow',
            'dangerous' => false,
        ],

        'read' => [
            'name' => '{module_name}.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt rekordow',
            'dangerous' => false,
        ],

        'update' => [
            'name' => '{module_name}.update',
            'label' => 'Edycja',
            'description' => 'Edycja rekordow',
            'dangerous' => false,
        ],

        'delete' => [
            'name' => '{module_name}.delete',
            'label' => 'Usuwanie',
            'description' => 'Usuwanie rekordow',
            'dangerous' => true, // Mark dangerous operations!
        ],

        // Add custom permissions below:
        // 'export' => [
        //     'name' => '{module_name}.export',
        //     'label' => 'Eksport',
        //     'description' => 'Eksport danych',
        //     'dangerous' => false,
        // ],
    ],

    // =========================================================================
    // ROLE DEFAULTS
    // =========================================================================

    // Which roles should have which permissions by default
    // Use permission keys from 'permissions' array above
    // Available roles: Admin, Manager, Editor, Warehouseman, Salesperson, Claims, User

    'role_defaults' => [
        'Admin' => ['create', 'read', 'update', 'delete'],
        'Manager' => ['create', 'read', 'update'],
        'Editor' => ['read', 'update'],
        'Warehouseman' => ['read'],
        'Salesperson' => ['read'],
        'Claims' => ['read'],
        'User' => ['read'],
    ],
];
