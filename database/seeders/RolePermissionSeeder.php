<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Services\Permissions\PermissionModuleLoader;

/**
 * Role and Permission Seeder
 *
 * FAZA D: Integration & System Tables
 * 5.1.2 Role i uprawnienia (Spatie Laravel Permission)
 *
 * Konfiguruje 7-poziomowy system uprawnien PPM:
 * 1. Admin - pelne uprawnienia
 * 2. Manager - CRUD produktow + import/export
 * 3. Editor - edycja opisow, zdjec, kategorii
 * 4. Warehouseman - panel dostaw
 * 5. Salesperson - zamowienia + rezerwacje
 * 6. Claims - reklamacje
 * 7. User - tylko odczyt
 *
 * Uprawnienia sa ladowane dynamicznie z config/permissions/*.php
 *
 * @see config/permissions/README.md for module format documentation
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Standard roles in PPM system
     */
    protected array $standardRoles = [
        'Admin',
        'Manager',
        'Editor',
        'Warehouseman',
        'Salesperson',
        'Claims',
        'User',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Get permission module loader
        $moduleLoader = app(PermissionModuleLoader::class);

        // === TWORZENIE UPRAWNIEN Z MODULOW ===
        $allPermissions = $moduleLoader->getAllPermissions();
        $createdCount = 0;

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission['name'],
                'guard_name' => 'web'
            ]);
            $createdCount++;
        }

        $this->command->info("Created/verified {$createdCount} permissions from " .
            count($moduleLoader->getModuleFiles()) . " modules");

        // === TWORZENIE ROL ===
        foreach ($this->standardRoles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web'
            ]);
        }

        // === PRZYPISYWANIE UPRAWNIEN DO ROL ===

        // Admin - pelny dostep do wszystkiego
        $adminRole = Role::findByName('Admin');
        $adminRole->syncPermissions(Permission::all());

        // Pozostale role - z role_defaults w modulach
        $roleDefaults = $moduleLoader->getAllRoleDefaults();

        foreach ($this->standardRoles as $roleName) {
            if ($roleName === 'Admin') {
                continue; // Admin juz ma wszystkie uprawnienia
            }

            $role = Role::findByName($roleName);
            $permissions = $roleDefaults[$roleName] ?? [];

            if (!empty($permissions)) {
                // Filter only existing permissions
                $existingPermissions = Permission::whereIn('name', $permissions)
                    ->pluck('name')
                    ->toArray();

                $role->syncPermissions($existingPermissions);
            } else {
                $role->syncPermissions([]);
            }
        }

        // === PODSUMOWANIE ===
        $this->command->info('');
        $this->command->info('=== PERMISSION SEEDER SUMMARY ===');
        $this->command->info("Roles created: " . count($this->standardRoles));
        $this->command->info("Permissions created: {$createdCount}");
        $this->command->info('');

        // Show permissions per role
        foreach ($this->standardRoles as $roleName) {
            $role = Role::findByName($roleName);
            $count = $role->permissions->count();
            $this->command->info("  {$roleName}: {$count} permissions");
        }

        $this->command->info('');
        $this->command->info('Modules loaded:');
        foreach ($moduleLoader->getPermissionCounts() as $moduleName => $count) {
            $this->command->info("  - {$moduleName}: {$count} permissions");
        }

        $this->command->info('');
        $this->command->info('Role i uprawnienia zostaly utworzone pomyslnie');
    }
}
