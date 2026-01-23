# Permission Modules - AI Agent Instructions

## Overview

This directory contains modular permission definitions for the PPM application.
Each file represents a permission module (e.g., products, categories, users).

**Auto-Discovery:** The system automatically discovers all `*.php` files in this directory.

---

## Quick Start (For AI Agents)

### Adding a New Permission Module

**Step 1:** Copy the template
```bash
cp config/permissions/_template.php config/permissions/{module_name}.php
```

**Step 2:** Edit the module file with appropriate values:
- `module` - Unique identifier (lowercase, no spaces)
- `name` - Display name in Polish
- `description` - Short description
- `icon` - Heroicon name (see icons list below)
- `order` - Sort order in UI (10, 20, 30...)
- `color` - Accent color (blue, green, red, yellow, purple, gray)
- `permissions` - Array of permission definitions
- `role_defaults` - Default permissions per role

**Step 3:** Run seeder to add permissions to database
```bash
php artisan db:seed --class=RolePermissionSeeder
```

**Step 4:** Clear cache
```bash
php artisan cache:clear
```

---

## Module Schema

```php
<?php
// config/permissions/{module_name}.php

return [
    // === METADATA ===
    'module' => 'module_name',           // Unique identifier (used in permission names)
    'name' => 'Module Display Name',     // Display name in UI (Polish)
    'description' => 'Module description',
    'icon' => 'document',                // Heroicon name
    'order' => 100,                      // Sort order (lower = first)
    'color' => 'gray',                   // Accent color

    // === PERMISSIONS ===
    'permissions' => [
        'create' => [
            'name' => 'module_name.create',   // Full permission name
            'label' => 'Tworzenie',           // Short label for UI
            'description' => 'Description',   // Tooltip text
            'dangerous' => false,             // Highlight in UI if true
        ],
        'read' => [
            'name' => 'module_name.read',
            'label' => 'Odczyt',
            'description' => 'Description',
            'dangerous' => false,
        ],
        // ... more permissions
    ],

    // === ROLE DEFAULTS ===
    'role_defaults' => [
        'Admin' => ['create', 'read', 'update', 'delete'],
        'Manager' => ['create', 'read', 'update'],
        'Editor' => ['read', 'update'],
        'User' => ['read'],
    ],
];
```

---

## Required Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `module` | string | YES | Unique identifier (lowercase) |
| `name` | string | YES | Display name in Polish |
| `description` | string | NO | Short description |
| `icon` | string | NO | Heroicon name (default: document) |
| `order` | int | NO | Sort order (default: 100) |
| `color` | string | NO | Accent color (default: gray) |
| `permissions` | array | YES | Permission definitions |
| `role_defaults` | array | NO | Default permissions per role |

### Permission Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | YES | Full permission name (module.action) |
| `label` | string | YES | Short label for UI |
| `description` | string | NO | Tooltip/help text |
| `dangerous` | bool | NO | Highlight as dangerous (default: false) |

---

## Naming Conventions

### Module Names
- Use lowercase with underscores: `price_groups`, `stock_locations`
- Keep it short and descriptive
- Must be unique across all modules

### Permission Names
- Format: `{module}.{action}`
- Common actions: `create`, `read`, `update`, `delete`, `export`, `import`
- Custom actions: `sync`, `config`, `manage`, `resolve`

### Examples
```
products.create
products.read
products.update
products.delete
products.export
products.import
products.variants

prices.read
prices.update
prices.groups
prices.cost
```

---

## Available Icons (Heroicons)

Common icons for permission modules:
- `cube` - Products
- `folder` - Categories
- `photograph` - Media
- `currency-dollar` - Prices
- `archive` - Stock/Warehouse
- `link` - Integrations
- `shopping-cart` - Orders
- `exclamation-circle` - Claims
- `users` - Users
- `cog` - System/Settings
- `document` - Default/Other
- `chart-bar` - Reports
- `shield-check` - Security
- `key` - Roles/Permissions

---

## Available Colors

- `blue` - Primary features
- `green` - Financial/Success
- `red` - Dangerous/Critical
- `yellow` - Warnings
- `purple` - Special features
- `gray` - Default/Other
- `indigo` - System features
- `pink` - Custom features

---

## Available Roles

Standard PPM roles for `role_defaults`:
- `Admin` - Full access
- `Manager` - Product management + import/export
- `Editor` - Content editing
- `Warehouseman` - Stock management
- `Salesperson` - Sales operations
- `Claims` - Claims handling
- `User` - Read-only access

---

## Existing Modules

| File | Module | Permissions Count |
|------|--------|-------------------|
| `products.php` | products | 7 |
| `categories.php` | categories | 5 |
| `media.php` | media | 5 |
| `prices.php` | prices | 4 |
| `stock.php` | stock | 5 |
| `integrations.php` | integrations | 5 |
| `orders.php` | orders | 4 |
| `claims.php` | claims | 4 |
| `users.php` | users | 5 |
| `system.php` | system | 4 |

---

## Artisan Command

Generate a new permission module:
```bash
php artisan make:permission-module {name}
```

Example:
```bash
php artisan make:permission-module shipping
# Creates: config/permissions/shipping.php
```

---

## After Adding Module

1. **Run seeder:**
   ```bash
   php artisan db:seed --class=RolePermissionSeeder
   ```

2. **Clear cache:**
   ```bash
   php artisan cache:clear
   ```

3. **Verify in UI:**
   Navigate to `/admin/permissions` to see the new module.

---

## Troubleshooting

### Module not appearing in UI
- Check file name ends with `.php`
- Verify `return` statement returns array
- Check required fields (`module`, `name`, `permissions`)
- Run `php artisan cache:clear`

### Permissions not in database
- Run `php artisan db:seed --class=RolePermissionSeeder`
- Check permission names are unique

### Validation errors
- Ensure `name` in each permission follows format: `{module}.{action}`
- Ensure `label` is not empty
- Ensure `permissions` array is not empty

---

## Example: Adding "Shipping" Module

```php
<?php
// config/permissions/shipping.php

return [
    'module' => 'shipping',
    'name' => 'Wysylka',
    'description' => 'Zarzadzanie wysylka i kurierami',
    'icon' => 'truck',
    'order' => 75,
    'color' => 'indigo',

    'permissions' => [
        'read' => [
            'name' => 'shipping.read',
            'label' => 'Odczyt',
            'description' => 'Odczyt informacji o wysylce',
            'dangerous' => false,
        ],
        'create' => [
            'name' => 'shipping.create',
            'label' => 'Tworzenie',
            'description' => 'Tworzenie nowych wysylek',
            'dangerous' => false,
        ],
        'update' => [
            'name' => 'shipping.update',
            'label' => 'Edycja',
            'description' => 'Edycja wysylek',
            'dangerous' => false,
        ],
        'config' => [
            'name' => 'shipping.config',
            'label' => 'Konfiguracja',
            'description' => 'Konfiguracja kurierow i metod wysylki',
            'dangerous' => false,
        ],
    ],

    'role_defaults' => [
        'Admin' => ['read', 'create', 'update', 'config'],
        'Manager' => ['read', 'create', 'update'],
        'Warehouseman' => ['read', 'create'],
        'User' => ['read'],
    ],
];
```

Then run:
```bash
php artisan db:seed --class=RolePermissionSeeder
php artisan cache:clear
```

---

**Last Updated:** 2025-01-23
**Maintainer:** PPM Development Team
