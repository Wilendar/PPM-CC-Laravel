<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Reorganize Permissions V3 - Faza 3
 *
 * 1. Rename prices.groups -> price_groups.manage
 * 2. Create new permissions: price_groups.*, vehicle_features.*, parameters.*.*
 * 3. Transfer existing role assignments to new permissions
 */
return new class extends Migration
{
    public function up(): void
    {
        // Clear permission cache before changes
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // =============================================
        // STEP 1: Rename prices.groups -> price_groups.manage
        // =============================================
        $oldPerm = Permission::where('name', 'prices.groups')->first();
        if ($oldPerm) {
            $oldPerm->update(['name' => 'price_groups.manage']);
            Log::info('Renamed prices.groups -> price_groups.manage');
        }

        // =============================================
        // STEP 2: Create all new permissions
        // =============================================
        $newPermissions = [
            // Price groups
            'price_groups.read',
            'price_groups.delete',

            // Vehicle features
            'vehicle_features.browser.read',
            'vehicle_features.browser.assign',
            'vehicle_features.library.read',
            'vehicle_features.library.edit',
            'vehicle_features.templates.read',
            'vehicle_features.templates.edit',
            'vehicle_features.bulk_assign',

            // Parameters per-tab
            'parameters.attributes.read',
            'parameters.attributes.edit',
            'parameters.manufacturers.read',
            'parameters.manufacturers.edit',
            'parameters.warehouses.read',
            'parameters.warehouses.edit',
            'parameters.product_types.read',
            'parameters.product_types.edit',
            'parameters.data_cleanup.read',
            'parameters.data_cleanup.run',
            'parameters.status_monitoring.read',
            'parameters.status_monitoring.edit',
            'parameters.smart_matching.read',
            'parameters.smart_matching.edit',
            'parameters.category_mappings.read',
            'parameters.category_mappings.edit',
        ];

        $created = 0;
        foreach ($newPermissions as $permName) {
            if (!Permission::where('name', $permName)->exists()) {
                Permission::create(['name' => $permName, 'guard_name' => 'web']);
                $created++;
            }
        }
        Log::info("Created {$created} new permissions");

        // Clear cache after creating permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // =============================================
        // STEP 3: Transfer role assignments
        // =============================================

        // 3a. Users who had prices.groups (now price_groups.manage) also get read+delete
        $this->transferFromRenamed();

        // 3b. Users who had parameters.read get per-tab permissions based on role
        $this->transferParameterPermissions();

        // 3c. Assign vehicle_features permissions based on role defaults
        $this->assignVehicleFeaturesDefaults();

        // Clear cache after all changes
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Log::info('Permission reorganization V3 completed');
    }

    /**
     * Transfer price_groups permissions to roles that had prices.groups
     */
    private function transferFromRenamed(): void
    {
        $managePerm = Permission::where('name', 'price_groups.manage')->first();
        if (!$managePerm) {
            return;
        }

        // Find roles with price_groups.manage (renamed from prices.groups)
        $roleIds = DB::table('role_has_permissions')
            ->where('permission_id', $managePerm->id)
            ->pluck('role_id');

        if ($roleIds->isEmpty()) {
            return;
        }

        $readPerm = Permission::where('name', 'price_groups.read')->first();
        $deletePerm = Permission::where('name', 'price_groups.delete')->first();

        foreach ($roleIds as $roleId) {
            $role = Role::find($roleId);
            if (!$role) continue;

            // Everyone with manage also gets read
            if ($readPerm) {
                $this->assignIfNotExists($roleId, $readPerm->id);
            }

            // Only Admin gets delete
            if ($deletePerm && $role->name === 'Admin') {
                $this->assignIfNotExists($roleId, $deletePerm->id);
            }
        }

        Log::info('Transferred price_groups permissions to ' . $roleIds->count() . ' roles');
    }

    /**
     * Transfer parameters.read -> per-tab permissions based on role
     */
    private function transferParameterPermissions(): void
    {
        $paramReadPerm = Permission::where('name', 'parameters.read')->first();
        if (!$paramReadPerm) {
            return;
        }

        $roleIds = DB::table('role_has_permissions')
            ->where('permission_id', $paramReadPerm->id)
            ->pluck('role_id');

        // Role-based permission mapping
        $roleMapping = [
            'Admin' => [
                'parameters.attributes.read', 'parameters.attributes.edit',
                'parameters.manufacturers.read', 'parameters.manufacturers.edit',
                'parameters.warehouses.read', 'parameters.warehouses.edit',
                'parameters.product_types.read', 'parameters.product_types.edit',
                'parameters.data_cleanup.read', 'parameters.data_cleanup.run',
                'parameters.status_monitoring.read', 'parameters.status_monitoring.edit',
                'parameters.smart_matching.read', 'parameters.smart_matching.edit',
                'parameters.category_mappings.read', 'parameters.category_mappings.edit',
            ],
            'Manager' => [
                'parameters.attributes.read', 'parameters.attributes.edit',
                'parameters.manufacturers.read', 'parameters.manufacturers.edit',
                'parameters.warehouses.read', 'parameters.warehouses.edit',
                'parameters.product_types.read', 'parameters.product_types.edit',
                'parameters.data_cleanup.read',
                'parameters.status_monitoring.read', 'parameters.status_monitoring.edit',
                'parameters.smart_matching.read', 'parameters.smart_matching.edit',
                'parameters.category_mappings.read', 'parameters.category_mappings.edit',
            ],
            'Edytor' => [
                'parameters.attributes.read',
                'parameters.manufacturers.read',
                'parameters.product_types.read',
            ],
        ];

        foreach ($roleIds as $roleId) {
            $role = Role::find($roleId);
            if (!$role) continue;

            $permsToAssign = $roleMapping[$role->name] ?? [];

            foreach ($permsToAssign as $permName) {
                $perm = Permission::where('name', $permName)->first();
                if ($perm) {
                    $this->assignIfNotExists($roleId, $perm->id);
                }
            }
        }

        Log::info('Transferred parameters per-tab permissions to ' . $roleIds->count() . ' roles');
    }

    /**
     * Assign vehicle_features permissions based on role defaults
     */
    private function assignVehicleFeaturesDefaults(): void
    {
        $roleMapping = [
            'Admin' => [
                'vehicle_features.browser.read', 'vehicle_features.browser.assign',
                'vehicle_features.library.read', 'vehicle_features.library.edit',
                'vehicle_features.templates.read', 'vehicle_features.templates.edit',
                'vehicle_features.bulk_assign',
            ],
            'Manager' => [
                'vehicle_features.browser.read', 'vehicle_features.browser.assign',
                'vehicle_features.library.read',
                'vehicle_features.templates.read', 'vehicle_features.templates.edit',
                'vehicle_features.bulk_assign',
            ],
            'Edytor' => [
                'vehicle_features.browser.read',
                'vehicle_features.library.read',
                'vehicle_features.templates.read',
            ],
        ];

        foreach ($roleMapping as $roleName => $permNames) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) continue;

            foreach ($permNames as $permName) {
                $perm = Permission::where('name', $permName)->first();
                if ($perm) {
                    $this->assignIfNotExists($role->id, $perm->id);
                }
            }
        }

        Log::info('Assigned vehicle_features defaults to roles');
    }

    /**
     * Helper: assign permission to role if not already assigned
     */
    private function assignIfNotExists(int $roleId, int $permissionId): void
    {
        $exists = DB::table('role_has_permissions')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->exists();

        if (!$exists) {
            DB::table('role_has_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Rename back
        $perm = Permission::where('name', 'price_groups.manage')->first();
        if ($perm) {
            $perm->update(['name' => 'prices.groups']);
        }

        // Remove new permissions
        $toRemove = [
            'price_groups.read', 'price_groups.delete',
            'vehicle_features.browser.read', 'vehicle_features.browser.assign',
            'vehicle_features.library.read', 'vehicle_features.library.edit',
            'vehicle_features.templates.read', 'vehicle_features.templates.edit',
            'vehicle_features.bulk_assign',
            'parameters.attributes.read', 'parameters.attributes.edit',
            'parameters.manufacturers.read', 'parameters.manufacturers.edit',
            'parameters.warehouses.read', 'parameters.warehouses.edit',
            'parameters.product_types.read', 'parameters.product_types.edit',
            'parameters.data_cleanup.read', 'parameters.data_cleanup.run',
            'parameters.status_monitoring.read', 'parameters.status_monitoring.edit',
            'parameters.smart_matching.read', 'parameters.smart_matching.edit',
            'parameters.category_mappings.read', 'parameters.category_mappings.edit',
        ];

        foreach ($toRemove as $permName) {
            $perm = Permission::where('name', $permName)->first();
            if ($perm) {
                DB::table('role_has_permissions')->where('permission_id', $perm->id)->delete();
                $perm->delete();
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
