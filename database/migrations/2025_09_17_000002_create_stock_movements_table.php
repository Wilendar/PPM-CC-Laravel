<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Stock Movements Table
 *
 * STOCK MANAGEMENT SYSTEM - Historia ruchów magazynowych
 *
 * Business Logic:
 * - Complete audit trail wszystkich stock movements
 * - Support dla IN/OUT/TRANSFER/ADJUSTMENT operations
 * - Integration z container tracking i delivery system
 * - User tracking dla accountability
 * - Reference system dla orders, deliveries, adjustments
 * - Cost tracking per movement dla accounting
 *
 * Performance Optimization:
 * - Strategic indexes dla history queries
 * - Partial indexes dla recent movements
 * - Optimized dla high-frequency stock operations
 * - Date-based indexing dla reporting
 *
 * @package Database\Migrations
 * @version STOCK MANAGEMENT SYSTEM
 * @since 2025-09-17
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates stock_movements table z complete audit trail functionality
     * Enterprise-grade stock history tracking dla PPM operations
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            // Primary Key
            $table->id();

            // Foreign Keys - Core Relations
            $table->unsignedBigInteger('product_id')->comment('Products.id - REQUIRED');
            $table->unsignedBigInteger('product_variant_id')->nullable()->comment('Product_variants.id - for variant-specific movements');
            $table->unsignedBigInteger('warehouse_id')->comment('Warehouses.id - REQUIRED');
            $table->unsignedBigInteger('product_stock_id')->comment('Product_stock.id - reference to stock record');

            // Movement Details
            $table->enum('movement_type', [
                'in',           // Stock IN - receiving, returns, adjustments positive
                'out',          // Stock OUT - sales, damages, adjustments negative
                'transfer',     // Transfer between warehouses
                'reservation',  // Reserve stock for order
                'release',      // Release reserved stock
                'adjustment',   // Manual stock adjustment
                'return',       // Product return
                'damage',       // Damaged stock removal
                'lost',         // Lost inventory
                'found',        // Found inventory
                'production',   // Production consumption/output
                'correction'    // Data correction
            ])->comment('Type of stock movement');

            // Quantity & Stock Levels
            $table->integer('quantity_before')->comment('Stock quantity before movement');
            $table->integer('quantity_change')->comment('Quantity change (positive/negative)');
            $table->integer('quantity_after')->comment('Stock quantity after movement');
            $table->integer('reserved_before')->default(0)->comment('Reserved quantity before');
            $table->integer('reserved_after')->default(0)->comment('Reserved quantity after');

            // Transfer Details (dla movement_type = 'transfer')
            $table->unsignedBigInteger('from_warehouse_id')->nullable()->comment('Source warehouse dla transfers');
            $table->unsignedBigInteger('to_warehouse_id')->nullable()->comment('Destination warehouse dla transfers');

            // Cost Information
            $table->decimal('unit_cost', 10, 4)->nullable()->comment('Unit cost at time of movement');
            $table->decimal('total_cost', 12, 4)->nullable()->comment('Total cost of movement (quantity * unit_cost)');
            $table->string('currency', 3)->default('PLN')->comment('Currency code');
            $table->decimal('exchange_rate', 8, 4)->default(1.0000)->comment('Exchange rate at movement time');

            // Reference Information
            $table->enum('reference_type', [
                'order',           // Sales order
                'purchase_order',  // Purchase order
                'delivery',        // Delivery/receiving
                'container',       // Container import
                'adjustment',      // Manual adjustment
                'return',          // Product return
                'transfer',        // Warehouse transfer
                'production',      // Production order
                'inventory',       // Inventory count
                'correction',      // Data correction
                'integration'      // ERP/API integration
            ])->nullable()->comment('Type of reference document');

            $table->string('reference_id', 100)->nullable()->comment('Reference document ID/number');
            $table->text('reference_notes')->nullable()->comment('Additional reference information');

            // Delivery & Container Tracking
            $table->string('container_number', 50)->nullable()->comment('Container number dla import tracking');
            $table->date('delivery_date')->nullable()->comment('Actual delivery date');
            $table->string('delivery_document', 100)->nullable()->comment('Delivery document number');

            // Location Information
            $table->string('location_from', 100)->nullable()->comment('Source location in warehouse');
            $table->string('location_to', 100)->nullable()->comment('Destination location in warehouse');
            $table->text('location_notes')->nullable()->comment('Location-specific notes');

            // Business Context
            $table->text('reason')->nullable()->comment('Business reason for movement');
            $table->text('notes')->nullable()->comment('Additional notes and comments');
            $table->boolean('is_automatic')->default(false)->comment('Movement was created automatically');
            $table->boolean('is_correction')->default(false)->comment('Movement is a correction/reversal');

            // Integration Data
            $table->json('erp_data')->nullable()->comment('
                ERP system data for this movement:
                {
                    "baselinker": {"document_id": "12345", "sync_status": "synced"},
                    "subiekt_gt": {"document_number": "MM/001/2025", "magazine": "MAG01"},
                    "dynamics": {"journal_entry": "INV001", "posting_date": "2025-09-17"}
                }
            ');

            // Audit Trail
            $table->unsignedBigInteger('created_by')->comment('User who created this movement');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('User who approved this movement');
            $table->timestamp('approved_at')->nullable()->comment('When movement was approved');
            $table->timestamp('movement_date')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Actual date/time of movement');
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('product_stock_id')->references('id')->on('product_stock')->onDelete('cascade');
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // Performance Indexes
            $table->index(['product_id', 'movement_date'], 'idx_movements_product_date');
            $table->index(['warehouse_id', 'movement_date'], 'idx_movements_warehouse_date');
            $table->index(['movement_type', 'movement_date'], 'idx_movements_type_date');
            $table->index(['reference_type', 'reference_id'], 'idx_movements_reference');
            $table->index(['container_number'], 'idx_movements_container');
            $table->index(['delivery_date'], 'idx_movements_delivery_date');
            $table->index(['created_by', 'movement_date'], 'idx_movements_user_date');
            $table->index(['is_automatic', 'movement_date'], 'idx_movements_auto_date');

            // Recent movements index (last 30 days) - better performance
            $table->index(['movement_date'], 'idx_movements_recent')
                  ->where('movement_date', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 30 DAY)'));

            // Transfer movements index
            $table->index(['movement_type', 'from_warehouse_id', 'to_warehouse_id'], 'idx_movements_transfer')
                  ->where('movement_type', '=', 'transfer');

            $table->comment('PPM Stock Movements: Complete audit trail dla warehouse operations');
        });

        // Add check constraints dla business rules
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT chk_movements_quantity_change CHECK (quantity_change != 0)');
            DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT chk_movements_quantity_logical CHECK (quantity_after = quantity_before + quantity_change)');
            DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT chk_movements_reserved_logical CHECK (reserved_before >= 0 AND reserved_after >= 0)');
            DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT chk_movements_transfer_warehouses CHECK (
                movement_type != \'transfer\' OR (from_warehouse_id IS NOT NULL AND to_warehouse_id IS NOT NULL AND from_warehouse_id != to_warehouse_id)
            )');
            DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT chk_movements_costs_positive CHECK (
                (unit_cost IS NULL OR unit_cost >= 0) AND (total_cost IS NULL OR total_cost >= 0)
            )');
            DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT chk_movements_exchange_rate CHECK (exchange_rate > 0)');
        }
    }

    /**
     * Reverse the migrations.
     *
     * Drops stock_movements table z constraint cleanup
     * Safe rollback z proper foreign key handling
     */
    public function down(): void
    {
        // Drop check constraints first (MySQL)
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS chk_movements_quantity_change');
            DB::statement('ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS chk_movements_quantity_logical');
            DB::statement('ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS chk_movements_reserved_logical');
            DB::statement('ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS chk_movements_transfer_warehouses');
            DB::statement('ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS chk_movements_costs_positive');
            DB::statement('ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS chk_movements_exchange_rate');
        }

        Schema::dropIfExists('stock_movements');
    }
};