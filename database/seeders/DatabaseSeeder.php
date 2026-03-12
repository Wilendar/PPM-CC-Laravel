<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Database Seeder - Main orchestrator dla PPM-CC-Laravel
 * 
 * FAZA D: Integration & System Tables - Production Data Seeding
 * 
 * Orchestrates seeding process dla production-ready PPM system:
 * - FAZA A: Core entities (Products, Categories) ✅
 * - FAZA B: Pricing & Inventory (Price Groups, Warehouses) ✅
 * - FAZA C: Media & Relations ✅
 * - FAZA D: Integration & System (Users, Roles, Audit) ✅
 * 
 * @package Database\Seeders
 * @version FAZA D
 * @since 2024-09-09
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 
     * Runs seeders in proper order respecting foreign key dependencies:
     * 1. FAZA B: Business entities (Price Groups, Warehouses)
     * 2. FAZA C: EAV System (Product Attributes)
     * 3. FAZA D: System & Integration (Roles, Permissions, Users)
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting PPM-CC-Laravel Database Seeding...');
        $this->command->info('📋 FAZA D: Integration & System Tables');
        $this->command->newLine();

        // =================================================================
        // FAZA B: PRICING & INVENTORY SYSTEM SEEDERS
        // =================================================================
        
        $this->command->info('🏷️  PHASE 1: Price Groups & Warehouses');
        $this->command->info('⏱️  Estimated time: ~30 seconds');
        $this->command->newLine();

        // Price Groups - Must be first (referenced by product_prices)
        $this->call(PriceGroupSeeder::class);
        $this->command->newLine();

        // Warehouses - Must be before stock (referenced by product_stock)
        $this->call(WarehouseSeeder::class);
        $this->command->newLine();

        // =================================================================
        // FAZA C: EAV SYSTEM SEEDERS
        // =================================================================
        
        $this->command->info('🏗️  PHASE 2: EAV System (Product Attributes)');
        $this->command->info('⏱️  Estimated time: ~45 seconds');
        $this->command->newLine();

        // Product Attributes - EAV system foundation
        $this->call(ProductAttributeSeederFixed::class);
        $this->command->newLine();

        // =================================================================
        // FAZA D: SYSTEM & INTEGRATION SEEDERS
        // =================================================================
        
        $this->command->info('👥 PHASE 3: System & Integration (Roles, Users)');
        $this->command->info('⏱️  Estimated time: ~20 seconds');
        $this->command->newLine();

        // Roles & Permissions - Must be before users
        $this->call(RolePermissionSeeder::class);
        $this->command->newLine();

        // Users with assigned roles
        $this->call(UserSeeder::class);
        $this->command->newLine();

        // =================================================================
        // ETAP_05a: VARIANTS, FEATURES & COMPATIBILITY SEEDERS
        // =================================================================

        $this->command->info('🎯 PHASE 4: Variants, Features & Compatibility');
        $this->command->info('⏱️  Estimated time: ~15 seconds');
        $this->command->newLine();

        // Attribute Types - Variant attributes (Size, Color, Material)
        $this->call(AttributeTypeSeeder::class);
        $this->command->newLine();

        // Attribute Values - Predefined values for attribute types
        $this->call(AttributeValueSeeder::class);
        $this->command->newLine();

        // Feature Types - Product features (Engine Type, Power, Weight, etc.)
        $this->call(FeatureTypeSeeder::class);
        $this->command->newLine();

        // Compatibility Attributes - Original, Replacement, Performance
        $this->call(CompatibilityAttributeSeeder::class);
        $this->command->newLine();

        // Compatibility Sources - Manufacturer, TecDoc, Manual Entry
        $this->call(CompatibilitySourceSeeder::class);
        $this->command->newLine();

        // Vehicle Models - Example motorcycles for testing
        $this->call(VehicleModelSeeder::class);
        $this->command->newLine();

        // =================================================================
        // SEEDING SUMMARY & VALIDATION
        // =================================================================

        $this->showSeedingSummary();
        $this->validateSeedingIntegrity();
        
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->info('🎯 System ready for FAZA D testing and ETAP_03 implementation');
    }

    /**
     * Display comprehensive seeding summary
     */
    private function showSeedingSummary(): void
    {
        $this->command->info('📊 SEEDING SUMMARY');
        $this->command->info('==================');
        
        // Price Groups Summary
        $priceGroups = \App\Models\PriceGroup::count();
        $defaultPriceGroup = \App\Models\PriceGroup::getDefault()?->name ?? 'NONE';
        $this->command->info("🏷️  Price Groups: {$priceGroups} (Default: {$defaultPriceGroup})");
        
        // Warehouses Summary
        $warehouses = \App\Models\Warehouse::count();
        $defaultWarehouse = \App\Models\Warehouse::getDefault()?->name ?? 'NONE';
        $this->command->info("🏢 Warehouses: {$warehouses} (Default: {$defaultWarehouse})");
        
        // Product Attributes Summary
        $productAttributes = \App\Models\ProductAttribute::count();
        $this->command->info("🏗️  Product Attributes: {$productAttributes}");
        
        // Roles & Permissions Summary
        $roles = \Spatie\Permission\Models\Role::count();
        $permissions = \Spatie\Permission\Models\Permission::count();
        $this->command->info("👥 Roles: {$roles}, Permissions: {$permissions}");
        
        // Users Summary
        $users = \App\Models\User::count();
        $activeUsers = \App\Models\User::where('is_active', true)->count();
        $this->command->info("👤 Users: {$users} (Active: {$activeUsers})");
        
        // Integration Readiness
        $integrationReady = $priceGroups > 0 && $warehouses > 0 && $roles > 0 && $users > 0;
        $status = $integrationReady ? '✅ READY' : '❌ NOT READY';
        $this->command->info("🔗 Integration Status: {$status}");
        
        $this->command->newLine();
    }

    /**
     * Validate seeding data integrity
     * 
     * @throws \Exception if data integrity issues found
     */
    private function validateSeedingIntegrity(): void
    {
        $this->command->info('🔍 VALIDATING DATA INTEGRITY');
        $this->command->info('============================');
        
        $errors = [];
        
        // Validate Price Groups
        try {
            $defaultPriceGroups = \App\Models\PriceGroup::where('is_default', true)->count();
            if ($defaultPriceGroups !== 1) {
                $errors[] = "Price Groups: Expected 1 default group, found {$defaultPriceGroups}";
            }
            
            $duplicatePriceGroupCodes = \App\Models\PriceGroup::select('code')
                ->groupBy('code')
                ->having(\DB::raw('count(*)'), '>', 1)
                ->count();
            
            if ($duplicatePriceGroupCodes > 0) {
                $errors[] = "Price Groups: {$duplicatePriceGroupCodes} duplicate codes found";
            }
            
            $this->command->info('✅ Price Groups integrity: OK');
            
        } catch (\Exception $e) {
            $errors[] = "Price Groups validation failed: " . $e->getMessage();
        }
        
        // Validate Warehouses
        try {
            $defaultWarehouses = \App\Models\Warehouse::where('is_default', true)->count();
            if ($defaultWarehouses !== 1) {
                $errors[] = "Warehouses: Expected 1 default warehouse, found {$defaultWarehouses}";
            }
            
            $duplicateWarehouseCodes = \App\Models\Warehouse::select('code')
                ->groupBy('code')
                ->having(\DB::raw('count(*)'), '>', 1)
                ->count();
            
            if ($duplicateWarehouseCodes > 0) {
                $errors[] = "Warehouses: {$duplicateWarehouseCodes} duplicate codes found";
            }
            
            $this->command->info('✅ Warehouses integrity: OK');
            
        } catch (\Exception $e) {
            $errors[] = "Warehouses validation failed: " . $e->getMessage();
        }
        
        // Validate Roles & Permissions
        try {
            $expectedRoles = ['Admin', 'Manager', 'Editor', 'Warehouseman', 'Salesperson', 'Claims', 'User'];
            $actualRoles = \Spatie\Permission\Models\Role::pluck('name')->toArray();
            $missingRoles = array_diff($expectedRoles, $actualRoles);
            
            if (!empty($missingRoles)) {
                $errors[] = "Roles: Missing roles: " . implode(', ', $missingRoles);
            }
            
            // Validate admin role has all permissions
            $adminRole = \Spatie\Permission\Models\Role::where('name', 'Admin')->first();
            if ($adminRole) {
                $allPermissions = \Spatie\Permission\Models\Permission::count();
                $adminPermissions = $adminRole->permissions()->count();
                
                if ($adminPermissions !== $allPermissions) {
                    $errors[] = "Admin role: Has {$adminPermissions} permissions, expected {$allPermissions}";
                }
            }
            
            $this->command->info('✅ Roles & Permissions integrity: OK');
            
        } catch (\Exception $e) {
            $errors[] = "Roles & Permissions validation failed: " . $e->getMessage();
        }
        
        // Validate Users
        try {
            $adminUser = \App\Models\User::where('email', 'admin@ppm.mpptrade.pl')->first();
            if (!$adminUser) {
                $errors[] = "Admin user not found: admin@ppm.mpptrade.pl";
            } else {
                if (!$adminUser->hasRole('Admin')) {
                    $errors[] = "Admin user does not have Admin role";
                }
                
                if (!$adminUser->is_active) {
                    $errors[] = "Admin user is not active";
                }
            }
            
            $this->command->info('✅ Users integrity: OK');
            
        } catch (\Exception $e) {
            $errors[] = "Users validation failed: " . $e->getMessage();
        }
        
        // Report validation results
        if (empty($errors)) {
            $this->command->info('✅ Data integrity validation: PASSED');
        } else {
            $this->command->error('❌ Data integrity validation: FAILED');
            foreach ($errors as $error) {
                $this->command->error("   - {$error}");
            }
            throw new \Exception('Database seeding validation failed');
        }
        
        $this->command->newLine();
    }
}