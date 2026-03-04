<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder dla brakujacych permissions wymaganych przez sidebar navigation.
 *
 * Dodaje permissions ktore nie istnieja w config/permissions/ modules,
 * ale sa wymagane przez admin sidebar do kontroli widocznosci linkow.
 */
class MissingPermissionsSeeder extends Seeder
{
    /**
     * @deprecated v2.0 - All permissions now defined in dedicated config/permissions/*.php modules.
     * This seeder is kept for backward compatibility but does nothing.
     * Use PermissionSystemV2Seeder instead.
     */
    protected array $missingPermissions = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $created = 0;
        $skipped = 0;

        foreach ($this->missingPermissions as $name => $description) {
            $permission = Permission::where('name', $name)->where('guard_name', 'web')->first();

            if ($permission) {
                $skipped++;
                $this->command->info("  SKIP (exists): {$name}");
                continue;
            }

            Permission::create([
                'name' => $name,
                'guard_name' => 'web',
            ]);
            $created++;
            $this->command->info("  CREATED: {$name}");
        }

        // Assign all new permissions to Admin role
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->syncPermissions(Permission::all());
            $this->command->info('Admin role synced with ALL permissions.');
        }

        // Assign selected permissions to Manager role
        $managerRole = Role::where('name', 'Manager')->first();
        if ($managerRole) {
            $managerPermissions = [
                'admin.shops.view',
                'admin.shops.sync',
                'admin.erp.view',
                'admin.media.manage',
                'system.reports',
            ];

            foreach ($managerPermissions as $permName) {
                $perm = Permission::where('name', $permName)->first();
                if ($perm && !$managerRole->hasPermissionTo($permName)) {
                    $managerRole->givePermissionTo($permName);
                }
            }
            $this->command->info('Manager role updated with sidebar permissions.');
        }

        // Reset cache after changes
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('');
        $this->command->info("=== MissingPermissionsSeeder DONE ===");
        $this->command->info("  Created: {$created}");
        $this->command->info("  Skipped (already exist): {$skipped}");
    }
}
