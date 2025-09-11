<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Product Stock Table
 * 
 * FAZA B: Pricing & Inventory System - Multi-Warehouse Stock Management
 * 
 * Business Logic:
 * - Multi-warehouse stock tracking dla 6+ magazynów PPM
 * - Support dla product variants (product OR product_variant stock)
 * - Advanced stock reservation system (quantity vs reserved_quantity)
 * - Delivery tracking z container numbers i status workflow
 * - Warehouse locations (wielowartościowe przez ';' separator)
 * - Minimum stock levels dla automatic reorder alerts
 * 
 * Performance Optimization:
 * - Composite unique constraint (product_id, product_variant_id, warehouse_id)
 * - Strategic indexes dla inventory queries i low stock alerts
 * - Optimized dla high-frequency stock updates
 * - Partial indexes dla non-zero stock levels
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
     * Creates product_stock table z advanced inventory management features
     * Enterprise-grade stock system dla multi-warehouse operations
     */
    public function up(): void
    {
        Schema::create('product_stock', function (Blueprint $table) {
            // Primary Key
            $table->id();
            
            // Foreign Keys - Product Relations
            $table->unsignedBigInteger('product_id')->comment('Products.id - REQUIRED');
            $table->unsignedBigInteger('product_variant_id')->nullable()->comment('Product_variants.id - OPTIONAL for variant-specific stock');
            $table->unsignedBigInteger('warehouse_id')->comment('Warehouses.id - REQUIRED');
            
            // Core Stock Fields
            $table->integer('quantity')->default(0)->comment('Current stock quantity (can be negative if allowed)');
            $table->integer('reserved_quantity')->default(0)->comment('Reserved stock for orders/reservations');
            $table->integer('available_quantity')->storedAs('quantity - reserved_quantity')->comment('Available stock (computed: quantity - reserved)');
            
            // Stock Management Settings
            $table->integer('minimum_stock')->default(0)->comment('Minimum stock level dla reorder alerts');
            $table->integer('maximum_stock')->nullable()->comment('Maximum stock level dla warehouse capacity');
            $table->integer('reorder_point')->nullable()->comment('Auto-reorder trigger point');
            $table->integer('reorder_quantity')->nullable()->comment('Default quantity to reorder');
            
            // Warehouse Location Tracking
            $table->text('warehouse_location')->nullable()->comment('Physical locations in warehouse (semicolon-separated): A1-01;A1-02;B2-15');
            $table->string('bin_location', 50)->nullable()->comment('Primary bin/shelf location');
            $table->text('location_notes')->nullable()->comment('Special location instructions');
            
            // Delivery Tracking System
            $table->date('last_delivery_date')->nullable()->comment('Date of last stock delivery');
            $table->string('container_number', 50)->nullable()->comment('Container number dla import tracking');
            $table->enum('delivery_status', [
                'not_ordered',      // Nie zamówione
                'ordered',          // Zamówione u dostawcy
                'confirmed',        // Potwierdzone przez dostawcę
                'in_production',    // W produkcji
                'ready_to_ship',    // Gotowe do wysyłki
                'shipped',          // Wysłane
                'in_container',     // W kontenerze
                'in_transit',       // W transporcie
                'customs',          // Odprawa celna
                'delayed',          // Opóźnione
                'receiving',        // W trakcie odboju
                'received',         // Odebrane
                'available',        // Dostępne w magazynie
                'cancelled'         // Anulowane
            ])->default('not_ordered')->comment('Delivery workflow status');
            
            // Delivery Planning
            $table->date('expected_delivery_date')->nullable()->comment('Expected delivery date');
            $table->integer('expected_quantity')->nullable()->comment('Expected delivery quantity');
            $table->text('delivery_notes')->nullable()->comment('Delivery notes and special instructions');
            
            // Cost Tracking
            $table->decimal('average_cost', 10, 4)->nullable()->comment('Average cost per unit (weighted average)');
            $table->decimal('last_cost', 10, 4)->nullable()->comment('Cost from last delivery');
            $table->timestamp('last_cost_update')->nullable()->comment('When cost was last updated');
            
            // ERP Integration Fields
            $table->json('erp_mapping')->nullable()->comment('
                ERP systems stock mapping:
                {
                    "baselinker": {"stock_id": "12345", "sync_enabled": true},
                    "subiekt_gt": {"stan_symbol": "ST001", "magazine_id": 1},
                    "dynamics": {"item_ledger_entry": "ILE001"}
                }
            ');
            
            // Stock Movement Tracking
            $table->integer('movements_count')->default(0)->comment('Total number of stock movements');
            $table->timestamp('last_movement_at')->nullable()->comment('Timestamp of last stock movement');
            $table->unsignedBigInteger('last_movement_by')->nullable()->comment('User who made last stock movement');
            
            // Alert Settings
            $table->boolean('low_stock_alert')->default(true)->comment('Enable low stock alerts');
            $table->boolean('out_of_stock_alert')->default(true)->comment('Enable out of stock alerts');
            $table->timestamp('last_alert_sent')->nullable()->comment('When last alert was sent');
            
            // Business Status
            $table->boolean('is_active')->default(true)->comment('Stock record is active');
            $table->boolean('track_stock')->default(true)->comment('Whether to track stock for this item');
            $table->boolean('allow_negative')->default(false)->comment('Allow negative stock levels');
            
            // Audit Trail
            $table->text('notes')->nullable()->comment('General stock management notes');
            $table->unsignedBigInteger('created_by')->nullable()->comment('User who created this stock record');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('User who last updated stock');
            $table->timestamps();
            
            // Foreign Key Constraints
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('last_movement_by')->references('id')->on('users')->onDelete('set null');
            
            // Business Constraints
            $table->unique(['product_id', 'product_variant_id', 'warehouse_id'], 'uk_product_variant_warehouse');
            
            // Performance Indexes
            $table->index(['product_id', 'warehouse_id'], 'idx_product_warehouse');
            $table->index(['product_variant_id', 'warehouse_id'], 'idx_variant_warehouse');
            $table->index(['warehouse_id', 'quantity'], 'idx_warehouse_quantity');
            $table->index(['warehouse_id', 'available_quantity'], 'idx_warehouse_available');
            $table->index(['delivery_status', 'expected_delivery_date'], 'idx_delivery_status_date');
            $table->index(['container_number'], 'idx_container_number');
            $table->index(['last_delivery_date'], 'idx_last_delivery');
            $table->index(['is_active', 'track_stock'], 'idx_active_tracked');
            
            // Low Stock Alert Indexes
            $table->index(['minimum_stock', 'available_quantity', 'low_stock_alert'], 'idx_low_stock_alert');
            $table->index(['quantity', 'out_of_stock_alert'], 'idx_out_of_stock_alert');
            
            // Partial indexes dla non-zero stock (better performance)
            $table->index(['warehouse_id'], 'idx_warehouse_positive_stock')->where('quantity', '>', 0);
            $table->index(['product_id'], 'idx_product_available_stock')->where('available_quantity', '>', 0);
            
            $table->comment('PPM Product Stock: Multi-warehouse inventory management z delivery tracking');
        });
        
        // Add check constraints dla business rules
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE product_stock ADD CONSTRAINT chk_stock_reserved_logical CHECK (reserved_quantity >= 0 AND reserved_quantity <= ABS(quantity))');
            DB::statement('ALTER TABLE product_stock ADD CONSTRAINT chk_stock_minimum_positive CHECK (minimum_stock >= 0)');
            DB::statement('ALTER TABLE product_stock ADD CONSTRAINT chk_stock_maximum_logical CHECK (maximum_stock IS NULL OR maximum_stock >= minimum_stock)');
            DB::statement('ALTER TABLE product_stock ADD CONSTRAINT chk_stock_reorder_logical CHECK (reorder_point IS NULL OR reorder_point >= 0)');
            DB::statement('ALTER TABLE product_stock ADD CONSTRAINT chk_stock_reorder_qty_positive CHECK (reorder_quantity IS NULL OR reorder_quantity > 0)');
            DB::statement('ALTER TABLE product_stock ADD CONSTRAINT chk_stock_expected_qty_positive CHECK (expected_quantity IS NULL OR expected_quantity >= 0)');
            DB::statement('ALTER TABLE product_stock ADD CONSTRAINT chk_stock_costs_positive CHECK (average_cost IS NULL OR average_cost >= 0)');
            DB::statement('ALTER TABLE product_stock ADD CONSTRAINT chk_stock_last_cost_positive CHECK (last_cost IS NULL OR last_cost >= 0)');
            DB::statement('ALTER TABLE product_stock ADD CONSTRAINT chk_stock_movements_count CHECK (movements_count >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Drops product_stock table z constraint cleanup
     * Safe rollback z proper foreign key handling
     */
    public function down(): void
    {
        // Drop check constraints first (MySQL)
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE product_stock DROP CONSTRAINT IF EXISTS chk_stock_reserved_logical');
            DB::statement('ALTER TABLE product_stock DROP CONSTRAINT IF EXISTS chk_stock_minimum_positive');
            DB::statement('ALTER TABLE product_stock DROP CONSTRAINT IF EXISTS chk_stock_maximum_logical');
            DB::statement('ALTER TABLE product_stock DROP CONSTRAINT IF EXISTS chk_stock_reorder_logical');
            DB::statement('ALTER TABLE product_stock DROP CONSTRAINT IF EXISTS chk_stock_reorder_qty_positive');
            DB::statement('ALTER TABLE product_stock DROP CONSTRAINT IF EXISTS chk_stock_expected_qty_positive');
            DB::statement('ALTER TABLE product_stock DROP CONSTRAINT IF EXISTS chk_stock_costs_positive');
            DB::statement('ALTER TABLE product_stock DROP CONSTRAINT IF EXISTS chk_stock_last_cost_positive');
            DB::statement('ALTER TABLE product_stock DROP CONSTRAINT IF EXISTS chk_stock_movements_count');
        }
        
        Schema::dropIfExists('product_stock');
    }
};