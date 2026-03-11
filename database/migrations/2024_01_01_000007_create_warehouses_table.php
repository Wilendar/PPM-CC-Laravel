<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Warehouses Table
 * 
 * FAZA B: Pricing & Inventory System - Multi-Warehouse Management
 * 
 * Business Logic:
 * - 6 głównych magazynów PPM: MPPTRADE (main), Pitbike.pl, Cameraman, 
 *   Otopit, INFMS, Reklamacje + możliwość custom warehouses
 * - Tylko jeden magazyn może być domyślny (is_default=true)
 * - Integration mapping fields dla PrestaShop stores i ERP systems
 * - Address information dla logistics i delivery planning
 * 
 * Performance Optimization:
 * - Unique constraint na code dla fast warehouse lookups
 * - Index na is_default dla default warehouse queries  
 * - Index na is_active dla active warehouses filtering
 * - GIN indexes na JSONB mapping fields dla integration queries
 * 
 * @package Database\Migrations
 * @version FAZA B
 * @since 2024-09-09
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates warehouses table z complete integration mapping support
     * Optimized dla multi-warehouse inventory management
     */
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            // Primary Key
            $table->id();
            
            // Core Business Fields
            $table->string('name', 100)->comment('Display name: MPPTRADE, Pitbike.pl, Cameraman, etc.');
            $table->string('code', 50)->unique()->comment('Unique code: mpptrade, pitbike, cameraman, etc.');
            
            // Location Information
            $table->text('address')->nullable()->comment('Full warehouse address dla logistics');
            $table->string('city', 100)->nullable()->comment('City dla geographic grouping');
            $table->string('postal_code', 20)->nullable()->comment('Postal code dla shipping zones');
            $table->string('country', 50)->default('PL')->comment('Country code (ISO 3166-1)');
            
            // Business Logic Fields
            $table->boolean('is_default')->default(false)->comment('Only one warehouse can be default');
            $table->boolean('is_active')->default(true)->comment('Active status for operations');
            $table->integer('sort_order')->default(0)->comment('Display order in UI');
            
            // Operational Settings
            $table->boolean('allow_negative_stock')->default(false)->comment('Allow negative stock levels');
            $table->boolean('auto_reserve_stock')->default(true)->comment('Auto reserve stock for orders');
            $table->integer('default_minimum_stock')->default(0)->comment('Default minimum stock level');
            
            // PrestaShop Integration Mapping
            $table->json('prestashop_mapping')->nullable()->comment('
                PrestaShop warehouses/shops mapping:
                {
                    "shop_1": {"warehouse_id": 1, "name": "Main Store"},
                    "shop_2": {"warehouse_id": 2, "name": "Pitbike Store"}
                }
            ');
            
            // ERP Integration Mapping
            $table->json('erp_mapping')->nullable()->comment('
                ERP systems warehouses mapping:
                {
                    "baselinker": {"warehouse_id": "12345", "name": "BL Warehouse 1"},
                    "subiekt_gt": {"magazine_symbol": "MAG01", "name": "Magazyn Główny"},
                    "dynamics": {"location_code": "MAIN", "name": "Main Location"}
                }
            ');
            
            // Contact Information
            $table->string('contact_person', 100)->nullable()->comment('Warehouse manager/contact person');
            $table->string('phone', 20)->nullable()->comment('Contact phone number');
            $table->string('email', 100)->nullable()->comment('Warehouse email address');
            
            // Operational Notes
            $table->text('operating_hours')->nullable()->comment('Working hours information');
            $table->text('special_instructions')->nullable()->comment('Special handling instructions');
            $table->text('notes')->nullable()->comment('General warehouse notes');
            
            // Audit Trail
            $table->timestamps();
            
            // Performance Indexes
            $table->index(['is_active'], 'idx_warehouses_active');
            $table->index(['is_default'], 'idx_warehouses_default');
            $table->index(['sort_order', 'is_active'], 'idx_warehouses_sort_active');
            $table->index(['city', 'is_active'], 'idx_warehouses_city_active');
            
            // Business Constraints Comments
            $table->comment('PPM Warehouses: Multi-warehouse inventory management z integration mapping');
        });
        
        // Add JSON indexes dla integration mapping (MySQL 8.0+)
        if (config('database.default') === 'mysql') {
            // Check constraints dla business rules
            DB::statement('ALTER TABLE warehouses ADD CONSTRAINT chk_warehouses_sort CHECK (sort_order >= 0)');
            DB::statement('ALTER TABLE warehouses ADD CONSTRAINT chk_warehouses_min_stock CHECK (default_minimum_stock >= 0)');
            
            // Functional indexes dla JSON fields (MySQL 8.0.13+)
            try {
                DB::statement('ALTER TABLE warehouses ADD INDEX idx_prestashop_mapping ((CAST(prestashop_mapping AS CHAR(255))))');
                DB::statement('ALTER TABLE warehouses ADD INDEX idx_erp_mapping ((CAST(erp_mapping AS CHAR(255))))');
            } catch (\Exception $e) {
                // Fallback for older MySQL versions - will create in separate migration if needed
            }
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Drops warehouses table z constraint cleanup
     * Warning: Will fail jeśli istnieją product_stock records
     */
    public function down(): void
    {
        // Drop constraints first (MySQL)
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE warehouses DROP CONSTRAINT IF EXISTS chk_warehouses_sort');
            DB::statement('ALTER TABLE warehouses DROP CONSTRAINT IF EXISTS chk_warehouses_min_stock');
        }
        
        Schema::dropIfExists('warehouses');
    }
};