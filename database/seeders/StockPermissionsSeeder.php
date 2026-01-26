<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeder dla granularnych uprawnień do edycji stanów magazynowych.
 *
 * Dodaje uprawnienia:
 * - products.stock.unlock_quantity - odblokowanie kolumny "Stan dostępny"
 * - products.stock.unlock_reserved - odblokowanie kolumny "Zarezerwowane"
 * - products.stock.unlock_minimum - odblokowanie kolumny "Minimum"
 */
class StockPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define new granular permissions
        $permissions = [
            [
                'name' => 'products.stock.unlock_quantity',
                'label' => 'Odblokowanie stanu dostępnego',
                'description' => 'Możliwość odblokowania i edycji kolumny "Stan dostępny" w zakładce Stany magazynowe. Zmiany będą synchronizowane do ERP.',
            ],
            [
                'name' => 'products.stock.unlock_reserved',
                'label' => 'Odblokowanie rezerwacji',
                'description' => 'Możliwość odblokowania i edycji kolumny "Zarezerwowane" w zakładce Stany magazynowe.',
            ],
            [
                'name' => 'products.stock.unlock_minimum',
                'label' => 'Odblokowanie minimum',
                'description' => 'Możliwość odblokowania i edycji kolumny "Minimum" w zakładce Stany magazynowe. Zmiany będą synchronizowane do ERP.',
            ],
        ];

        // Create permissions
        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name'], 'guard_name' => 'web'],
                ['guard_name' => 'web']
            );

            $this->command->info("Created permission: {$permissionData['name']}");
        }

        // Assign all stock permissions to Admin role
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $permissionNames = array_column($permissions, 'name');
            $adminRole->givePermissionTo($permissionNames);
            $this->command->info('Assigned stock permissions to Admin role');
        }

        // Assign unlock_minimum to Menadżer role (they can set minimum levels without affecting actual stock)
        $managerRole = Role::where('name', 'Menadżer')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo('products.stock.unlock_minimum');
            $this->command->info('Assigned products.stock.unlock_minimum to Menadżer role');
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Stock permissions seeder completed');
    }
}
