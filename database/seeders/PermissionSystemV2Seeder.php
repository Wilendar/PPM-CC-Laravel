<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission System v2.0 Seeder
 *
 * Loads ALL permission module configs from config/permissions/*.php,
 * creates permissions that don't exist yet, and assigns them to roles
 * according to role_defaults in each module config.
 *
 * Safe to run multiple times (idempotent via firstOrCreate).
 */
class PermissionSystemV2Seeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $configFiles = glob(config_path('permissions/*.php'));
        $created = 0;
        $existed = 0;

        foreach ($configFiles as $file) {
            $basename = basename($file);
            if ($basename === '_template.php') {
                continue;
            }

            $config = require $file;

            // Support both flat and nested module format
            $module = is_array($config['module'] ?? null)
                ? ($config['module']['name'] ?? null)
                : ($config['module'] ?? null);

            if (!$module) {
                $this->command?->warn("  SKIP: {$basename} - no module key");
                continue;
            }

            $permissions = $config['permissions'] ?? [];
            $roleDefaults = $config['role_defaults'] ?? [];

            $this->command?->info("Processing module: {$module} ({$basename})");

            // Create permissions
            foreach ($permissions as $key => $perm) {
                $permName = $perm['name'] ?? "{$module}.{$key}";

                $result = Permission::firstOrCreate(
                    ['name' => $permName, 'guard_name' => 'web']
                );

                if ($result->wasRecentlyCreated) {
                    $created++;
                    $this->command?->info("  CREATED: {$permName}");
                } else {
                    $existed++;
                }
            }

            // Assign to roles per role_defaults
            foreach ($roleDefaults as $roleName => $permKeys) {
                $role = Role::where('name', $roleName)->first();
                if (!$role) {
                    continue;
                }

                foreach ($permKeys as $key) {
                    $permConfig = $permissions[$key] ?? null;
                    $permName = $permConfig['name'] ?? "{$module}.{$key}";

                    if (!$role->hasPermissionTo($permName)) {
                        try {
                            $role->givePermissionTo($permName);
                            $this->command?->info("  ASSIGNED: {$permName} -> {$roleName}");
                        } catch (\Exception $e) {
                            $this->command?->warn("  SKIP assign: {$permName} -> {$roleName}: {$e->getMessage()}");
                        }
                    }
                }
            }
        }

        // Admin gets ALL permissions
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->syncPermissions(Permission::all());
            $this->command?->info('Admin role synced with ALL permissions.');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('');
        $this->command?->info("=== PermissionSystemV2Seeder DONE ===");
        $this->command?->info("  Created: {$created}");
        $this->command?->info("  Already existed: {$existed}");
    }
}
