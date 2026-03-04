<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rename permission names to consistent module.action format.
 *
 * Part of Permission System v2.0 audit fix.
 * Renames ~20 old-format names (admin.*, products.stock.*, system.reports)
 * to new flat module.action format.
 *
 * Safe: model_has_permissions uses permission_id (not name), so rename preserves assignments.
 */
return new class extends Migration
{
    protected array $renames = [
        // Shop Management
        'admin.shops.view' => 'shops.read',
        'admin.shops.create' => 'shops.create',
        'admin.shops.sync' => 'shops.sync',
        'admin.shops.edit' => 'shops.update',
        'admin.shops.export' => 'shops.export',
        'admin.shops.import' => 'shops.import',

        // ERP Integration
        'admin.erp.view' => 'integrations.read',
        'admin.erp.test' => 'integrations.test',

        // Scan System
        'admin.scan.view' => 'scan.read',
        'admin.scan.start' => 'scan.start',
        'admin.scan.link' => 'scan.link',
        'admin.scan.create' => 'scan.create',
        'admin.scan.bulk' => 'scan.bulk',
        'admin.scan.history' => 'scan.history',
        'admin.scan.export' => 'scan.export',

        // System Administration
        'admin.backup.manage' => 'backup.manage',
        'admin.maintenance.manage' => 'maintenance.manage',
        'admin.settings.manage' => 'system.manage',
        'admin.media.manage' => 'media.manage',

        // Reports
        'system.reports' => 'reports.read',

        // Stock (unlock granular permissions)
        'products.stock.unlock_quantity' => 'stock.unlock_quantity',
        'products.stock.unlock_reserved' => 'stock.unlock_reserved',
        'products.stock.unlock_minimum' => 'stock.unlock_minimum',
    ];

    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->renames as $old => $new) {
            $permission = \DB::table('permissions')
                ->where('name', $old)
                ->where('guard_name', 'web')
                ->first();

            if (!$permission) {
                continue;
            }

            // Check if new name already exists (avoid duplicate)
            $existing = \DB::table('permissions')
                ->where('name', $new)
                ->where('guard_name', 'web')
                ->first();

            if ($existing) {
                // Transfer role assignments from old to new, then delete old
                $oldAssignments = \DB::table('role_has_permissions')
                    ->where('permission_id', $permission->id)
                    ->get();

                foreach ($oldAssignments as $assignment) {
                    \DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $existing->id,
                        'role_id' => $assignment->role_id,
                    ]);
                }

                // Clean up old permission references
                \DB::table('role_has_permissions')
                    ->where('permission_id', $permission->id)
                    ->delete();
                \DB::table('model_has_permissions')
                    ->where('permission_id', $permission->id)
                    ->delete();
                \DB::table('permissions')
                    ->where('id', $permission->id)
                    ->delete();
            } else {
                // Simple rename
                \DB::table('permissions')
                    ->where('id', $permission->id)
                    ->update(['name' => $new]);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->renames as $old => $new) {
            \DB::table('permissions')
                ->where('name', $new)
                ->where('guard_name', 'web')
                ->update(['name' => $old]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
