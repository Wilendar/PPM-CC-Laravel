<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

/**
 * Warehouse Seeder - Strategy B Minimalist Approach
 *
 * Strategy B Phase 1: Database
 * - Creates ONLY MPPTRADE master warehouse
 * - Additional warehouses created through:
 *   - Shop Wizard (Step 3) for shop-linked warehouses
 *   - Admin panel CRUD for custom warehouses
 *   - Import system for auto-created warehouses
 *
 * @package Database\Seeders
 * @version Strategy B - Complex Warehouse Redesign
 * @since 2025-11-13
 */
class WarehouseSeeder extends Seeder
{
    /**
     * MPPTRADE master warehouse (ONLY)
     *
     * Strategy B: Start minimal, grow organically
     */
    private const MPPTRADE_WAREHOUSE = [
        'name' => 'MPPTRADE',
        'code' => 'mpptrade',
        'type' => 'master', // Strategy B: Master warehouse
        'shop_id' => null, // Master warehouse not linked to shop
        'is_default' => true,
        'address' => null, // Will be configured in admin panel
        'city' => null,
        'postal_code' => null,
        'country' => 'PL',
        'sort_order' => 1,
        'allow_negative_stock' => false,
        'auto_reserve_stock' => true,
        'default_minimum_stock' => 5,
        'inherit_from_shop' => false, // Strategy B: Master doesn't inherit
        'is_active' => true,
        'notes' => 'MPPTRADE master warehouse - central inventory hub',
    ];

    /**
     * Run the warehouses seeder
     *
     * Strategy B: Create ONLY MPPTRADE master warehouse
     */
    public function run(): void
    {
        $this->command->info('🏢 Creating MPPTRADE master warehouse (Strategy B)...');

        // Create MPPTRADE master warehouse
        $warehouse = Warehouse::create(self::MPPTRADE_WAREHOUSE);

        $this->command->info("✅ Created warehouse: {$warehouse->name} (type: {$warehouse->type})");

        // Validate business constraints
        $this->validateWarehouses();

        $this->command->info('✅ Warehouse seeding completed (Strategy B)!');
        $this->command->info('📊 Total warehouses: ' . Warehouse::count());
        $this->command->info('🎯 Default warehouse: ' . Warehouse::getDefault()?->name ?? 'NONE');
        $this->command->info('');
        $this->command->info('ℹ️  Additional warehouses will be created through:');
        $this->command->info('   - Shop Wizard (Step 3) for shop-linked warehouses');
        $this->command->info('   - Admin panel CRUD for custom warehouses');
        $this->command->info('   - Import system for auto-created warehouses');
    }


    /**
     * Validate warehouses business constraints
     * 
     * @throws \Exception
     */
    private function validateWarehouses(): void
    {
        // Check if exactly one default warehouse exists
        $defaultCount = Warehouse::where('is_default', true)->count();
        if ($defaultCount !== 1) {
            throw new \Exception("Invalid warehouses setup: Found {$defaultCount} default warehouses, expected exactly 1");
        }

        // Check if all codes are unique
        $totalWarehouses = Warehouse::count();
        $uniqueCodes = Warehouse::distinct('code')->count();
        if ($totalWarehouses !== $uniqueCodes) {
            throw new \Exception('Invalid warehouses setup: Duplicate codes found');
        }

        // Validate minimum stock ranges
        $invalidMinStock = Warehouse::where('default_minimum_stock', '<', 0)->count();
        if ($invalidMinStock > 0) {
            throw new \Exception('Invalid warehouses setup: Negative minimum stock found');
        }

        $this->command->info('✅ Warehouses validation passed');
    }
}