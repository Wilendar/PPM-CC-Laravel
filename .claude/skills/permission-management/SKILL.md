---
name: permission-management
description: "Manage PPM permissions - add, update, remove permission modules. Use when working with admin/permissions panel or adding new features requiring permissions."
version: 1.0.0
author: Claude Code
created: 2026-01-23
updated: 2026-01-23
tags: [permissions, roles, spatie, authorization, admin]
---

# Permission Management Skill

## Overview

Ten skill zawiera **kompletną wiedzę** o systemie uprawnień PPM opartym na Spatie Laravel Permission v6. Używaj go gdy:
- Dodajesz nowy moduł wymagający uprawnień
- Modyfikujesz istniejące uprawnienia
- Tworzysz nowe role z określonymi uprawnieniami
- Debugujesz problemy z autoryzacją

---

## ARCHITEKTURA SYSTEMU UPRAWNIEŃ

### Stack Technologiczny

| Komponent | Technologia | Lokalizacja |
|-----------|-------------|-------------|
| Backend | Spatie Permission v6 | `composer require spatie/laravel-permission` |
| Models | Role, Permission | `Spatie\Permission\Models\*` |
| Policies | Laravel Policies | `app/Policies/` |
| Config | Permission config | `config/permission.php` |
| UI | PermissionMatrix | `app/Http/Livewire/Admin/Permissions/` |

### Konwencja Nazewnictwa Uprawnień

**Format:** `module.action`

| Przykład | Moduł | Akcja |
|----------|-------|-------|
| `products.view` | products | view |
| `products.create` | products | create |
| `products.edit` | products | edit |
| `products.delete` | products | delete |
| `users.manage` | users | manage |
| `roles.assign` | roles | assign |

**Standardowe Akcje:**
- `view` - Podgląd/Lista
- `create` - Tworzenie nowych
- `edit` / `update` - Edycja istniejących
- `delete` - Usuwanie
- `manage` - Pełne zarządzanie (wszystkie akcje)
- `export` - Eksport danych
- `import` - Import danych

---

## DODAWANIE NOWEGO MODUŁU UPRAWNIEŃ

### Krok 1: Zdefiniuj Uprawnienia

Utwórz plik konfiguracyjny modułu w `config/permissions/`:

```php
// config/permissions/prices.php
<?php

return [
    'module' => 'prices',
    'label' => 'Ceny',
    'icon' => 'currency-dollar',
    'description' => 'Zarządzanie cenami produktów i grupami cenowymi',

    'permissions' => [
        'prices.view' => [
            'label' => 'Podgląd cen',
            'description' => 'Możliwość przeglądania cen produktów',
        ],
        'prices.edit' => [
            'label' => 'Edycja cen',
            'description' => 'Możliwość edycji cen produktów',
        ],
        'prices.manage_groups' => [
            'label' => 'Zarządzanie grupami cenowymi',
            'description' => 'Tworzenie i edycja grup cenowych',
        ],
        'prices.export' => [
            'label' => 'Eksport cen',
            'description' => 'Eksport cenników do plików',
        ],
        'prices.import' => [
            'label' => 'Import cen',
            'description' => 'Import cen z plików',
        ],
    ],

    'routes' => [
        '/admin/prices' => 'prices.view',
        '/admin/prices/groups' => 'prices.manage_groups',
        '/admin/prices/export' => 'prices.export',
        '/admin/prices/import' => 'prices.import',
    ],
];
```

### Krok 2: Uruchom Seeder

```bash
php artisan db:seed --class=PermissionSeeder
```

Lub dodaj permissions programowo:

```php
use Spatie\Permission\Models\Permission;

// W seeder lub migration
$permissions = [
    'prices.view',
    'prices.edit',
    'prices.manage_groups',
    'prices.export',
    'prices.import',
];

foreach ($permissions as $permission) {
    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
}
```

### Krok 3: Utwórz Policy (opcjonalnie)

```php
// app/Policies/PricePolicy.php
<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PricePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('prices.view');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('prices.edit');
    }

    public function manageGroups(User $user): bool
    {
        return $user->hasPermissionTo('prices.manage_groups');
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('prices.export');
    }

    public function import(User $user): bool
    {
        return $user->hasPermissionTo('prices.import');
    }
}
```

Zarejestruj w `AuthServiceProvider`:

```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    \App\Models\Price::class => \App\Policies\PricePolicy::class,
];
```

### Krok 4: Zabezpiecz Route/Controller

```php
// W Livewire Component
public function mount()
{
    $this->authorize('viewAny', Price::class);
    // lub bezpośrednio:
    // if (!auth()->user()->can('prices.view')) abort(403);
}

// W Route
Route::get('/admin/prices', PriceList::class)
    ->middleware('can:prices.view')
    ->name('admin.prices.index');
```

### Krok 5: Zabezpiecz Blade View

```blade
@can('prices.edit')
    <button wire:click="edit({{ $price->id }})">Edytuj</button>
@endcan

@canany(['prices.export', 'prices.import'])
    <div class="import-export-section">
        @can('prices.export')
            <button>Eksportuj</button>
        @endcan
        @can('prices.import')
            <button>Importuj</button>
        @endcan
    </div>
@endcanany
```

---

## KLUCZOWE PLIKI

### PermissionMatrix.php

**Lokalizacja:** `app/Http/Livewire/Admin/Permissions/PermissionMatrix.php`

**Odpowiada za:**
- Wyświetlanie matrycy uprawnień (role vs permissions)
- Przypisywanie/odbieranie uprawnień rolom
- Grupowanie uprawnień wg modułów

**Kluczowe metody:**

```php
// Pobierz wszystkie permissions pogrupowane wg modułu
public function getPermissionsByModuleProperty()
{
    $permissions = Permission::orderBy('name')->get();
    $grouped = [];

    foreach ($permissions as $permission) {
        $module = explode('.', $permission->name)[0];
        $grouped[$module][] = $permission;
    }

    return $grouped;
}

// Toggle permission dla roli
public function togglePermission($roleId, $permissionName)
{
    $role = Role::findOrFail($roleId);

    if ($role->hasPermissionTo($permissionName)) {
        $role->revokePermissionTo($permissionName);
    } else {
        $role->givePermissionTo($permissionName);
    }
}
```

### RoleList.php

**Lokalizacja:** `app/Http/Livewire/Admin/Roles/RoleList.php`

**CRITICAL FIX:** Nie używaj `withCount('users')` z Spatie Permission!

```php
// ❌ BŁĘDNE - powoduje "Class name must be a valid object or a string"
return Role::withCount('users')->get();

// ✅ POPRAWNE - użyj raw SQL subquery
return Role::query()
    ->selectRaw('roles.*, (SELECT COUNT(*) FROM model_has_roles WHERE model_has_roles.role_id = roles.id) as users_count')
    ->get();
```

---

## HIERARCHIA RÓL PPM

| Level | Rola | Opis | Kolor |
|-------|------|------|-------|
| 1 | Admin | Pełny dostęp | red |
| 2 | Menadżer | Produkty + Import/Export | orange |
| 3 | Redaktor | Edycja opisów/zdjęć | green |
| 4 | Magazynier | Panel dostaw | blue |
| 5 | Handlowiec | Rezerwacje (bez cen zakupu) | purple |
| 6 | Reklamacje | Panel reklamacji | teal |
| 7 | Użytkownik | Tylko odczyt | gray |

---

## ISTNIEJĄCE MODUŁY UPRAWNIEŃ

| Moduł | Uprawnienia | Opis |
|-------|-------------|------|
| `products` | view, create, edit, delete | Produkty |
| `categories` | view, create, edit, delete | Kategorie |
| `users` | view, create, edit, delete, manage | Użytkownicy |
| `roles` | view, create, edit, delete, assign | Role |
| `permissions` | view, manage | Uprawnienia |
| `shops` | view, create, edit, delete, sync | Sklepy PrestaShop |
| `erp` | view, sync, configure | Integracje ERP |
| `reports` | view, export | Raporty |
| `audit` | view, export | Logi audytu |
| `settings` | view, edit | Ustawienia systemu |
| `backup` | view, create, restore | Kopie zapasowe |

---

## DEBUGOWANIE

### Sprawdź uprawnienia użytkownika

```php
// W tinker lub kontrolerze
$user = User::find(8);

// Wszystkie uprawnienia
$user->getAllPermissions()->pluck('name');

// Sprawdź konkretne uprawnienie
$user->can('products.edit');
$user->hasPermissionTo('products.edit');

// Uprawnienia przez rolę
$user->getPermissionsViaRoles()->pluck('name');

// Bezpośrednie uprawnienia (nie przez rolę)
$user->getDirectPermissions()->pluck('name');
```

### Sprawdź uprawnienia roli

```php
$role = Role::findByName('Admin');

// Wszystkie uprawnienia roli
$role->permissions->pluck('name');

// Sprawdź konkretne uprawnienie
$role->hasPermissionTo('products.edit');
```

### Cache Permission

**CRITICAL:** Po zmianie uprawnień wyczyść cache!

```bash
php artisan permission:cache-reset
php artisan cache:clear
```

```php
// Programowo
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

---

## TYPOWE BŁĘDY I ROZWIĄZANIA

### Błąd: "There is no permission named X"

**Przyczyna:** Permission nie istnieje w bazie danych.

**Rozwiązanie:**
```php
Permission::firstOrCreate(['name' => 'missing.permission', 'guard_name' => 'web']);
```

### Błąd: "Class name must be a valid object or a string"

**Przyczyna:** Używanie `withCount('users')` w query na Role.

**Rozwiązanie:** Użyj raw SQL subquery (patrz sekcja KLUCZOWE PLIKI).

### Błąd: Permission nie działa po dodaniu

**Przyczyna:** Cache permissions.

**Rozwiązanie:**
```bash
php artisan permission:cache-reset
php artisan config:clear
php artisan cache:clear
```

### Błąd: Guard mismatch

**Przyczyna:** Permission ma inny guard niż user.

**Rozwiązanie:** Upewnij się że permission ma `guard_name => 'web'`:
```php
Permission::create(['name' => 'my.permission', 'guard_name' => 'web']);
```

---

## CHECKLIST DODAWANIA NOWEGO MODUŁU

- [ ] Zdefiniuj permissions w `config/permissions/{module}.php`
- [ ] Uruchom seeder: `php artisan db:seed --class=PermissionSeeder`
- [ ] Utwórz Policy w `app/Policies/{Model}Policy.php`
- [ ] Zarejestruj Policy w `AuthServiceProvider`
- [ ] Dodaj middleware/authorize w Livewire component mount()
- [ ] Zabezpiecz Route z `->middleware('can:permission.name')`
- [ ] Dodaj `@can` directives w Blade views
- [ ] Wyczyść cache: `php artisan permission:cache-reset`
- [ ] Przetestuj z różnymi rolami
- [ ] Sprawdź w /admin/permissions czy nowe permissions są widoczne

---

## PLIKI REFERENCYJNE

| Plik | Opis |
|------|------|
| `app/Http/Livewire/Admin/Permissions/PermissionMatrix.php` | Panel uprawnień |
| `app/Http/Livewire/Admin/Roles/RoleList.php` | Lista ról |
| `app/Policies/` | Wszystkie policy |
| `config/permission.php` | Konfiguracja Spatie |
| `database/seeders/PermissionSeeder.php` | Seeder uprawnień |
| `config/permissions/` | Moduły uprawnień (do utworzenia) |

---

## CHANGELOG

### v1.0.0 (2026-01-23)
- [INIT] Utworzono skill z kompletną dokumentacją systemu uprawnień PPM
- [DOCS] Dodano instrukcje dodawania nowych modułów
- [FIX] Udokumentowano fix dla withCount('users') bug
- [REF] Dodano hierarchię ról PPM
- [CHECKLIST] Dodano checklist dla nowych modułów
