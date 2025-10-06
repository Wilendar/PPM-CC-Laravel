<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Stock Reservations Table
 *
 * STOCK MANAGEMENT SYSTEM - System rezerwacji stanów
 *
 * Business Logic:
 * - Detailed stock reservations dla orders/quotes
 * - Time-based reservation expiry system
 * - Priority-based reservation queue
 * - Integration z order management system
 * - Automatic release dla expired reservations
 * - Support dla partial fulfillment
 *
 * Performance Optimization:
 * - Strategic indexes dla reservation queries
 * - Expiry date indexes dla cleanup jobs
 * - Optimized dla high-frequency reservation operations
 * - Composite unique constraints dla data integrity
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
     * Creates stock_reservations table z advanced reservation system
     * Enterprise-grade stock allocation dla PPM operations
     */
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table) {
            // Primary Key
            $table->id();

            // Foreign Keys - Core Relations
            $table->unsignedBigInteger('product_id')->comment('Products.id - REQUIRED');
            $table->unsignedBigInteger('product_variant_id')->nullable()->comment('Product_variants.id - for variant-specific reservations');
            $table->unsignedBigInteger('warehouse_id')->comment('Warehouses.id - REQUIRED');
            $table->unsignedBigInteger('product_stock_id')->comment('Product_stock.id - reference to stock record');

            // Reservation Identification
            $table->string('reservation_number', 50)->unique()->comment('Unique reservation identifier');
            $table->enum('reservation_type', [
                'order',        // Sales order reservation
                'quote',        // Quote reservation
                'pre_order',    // Pre-order dla upcoming stock
                'allocation',   // Manual stock allocation
                'production',   // Production reservation
                'transfer',     // Transfer reservation
                'sample',       // Sample/demo reservation
                'warranty',     // Warranty replacement
                'exchange',     // Product exchange
                'temp'         // Temporary reservation
            ])->comment('Type of reservation');

            // Reservation Quantities
            $table->integer('quantity_requested')->comment('Originally requested quantity');
            $table->integer('quantity_reserved')->comment('Actually reserved quantity');
            $table->integer('quantity_fulfilled')->default(0)->comment('Quantity already fulfilled/shipped');
            $table->integer('quantity_remaining')->storedAs('quantity_reserved - quantity_fulfilled')->comment('Remaining reserved quantity');

            // Time Management
            $table->timestamp('reserved_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('When reservation was created');
            $table->timestamp('expires_at')->nullable()->comment('When reservation expires (NULL = no expiry)');
            $table->timestamp('fulfilled_at')->nullable()->comment('When reservation was fully fulfilled');
            $table->integer('duration_minutes')->nullable()->comment('Reservation duration in minutes');

            // Priority & Status
            $table->enum('status', [
                'pending',      // Waiting for confirmation
                'confirmed',    // Confirmed reservation
                'partial',      // Partially fulfilled
                'fulfilled',    // Fully fulfilled
                'expired',      // Expired reservation
                'cancelled',    // Cancelled reservation
                'on_hold',      // Temporarily on hold
                'processing'    // Being processed
            ])->default('pending')->comment('Reservation status');

            $table->integer('priority')->default(5)->comment('Reservation priority (1=highest, 10=lowest)');
            $table->boolean('auto_release')->default(true)->comment('Automatically release when expired');

            // Reference Information
            $table->enum('reference_type', [
                'sales_order',     // Sales order
                'quote',           // Customer quote
                'internal_order',  // Internal order
                'production_order', // Production order
                'transfer_request', // Warehouse transfer
                'sample_request',   // Sample request
                'warranty_claim',   // Warranty claim
                'exchange_request', // Product exchange
                'manual'           // Manual reservation
            ])->nullable()->comment('Type of reference document');

            $table->string('reference_id', 100)->nullable()->comment('Reference document ID/number');
            $table->string('reference_line_id', 100)->nullable()->comment('Reference line item ID');
            $table->text('reference_notes')->nullable()->comment('Reference information');

            // Customer/Business Context
            $table->string('customer_id', 50)->nullable()->comment('Customer identifier');
            $table->string('customer_name', 200)->nullable()->comment('Customer name');
            $table->string('sales_person', 100)->nullable()->comment('Responsible salesperson');
            $table->string('department', 100)->nullable()->comment('Requesting department');

            // Pricing Information (at time of reservation)
            $table->decimal('unit_price', 10, 4)->nullable()->comment('Unit price at reservation');
            $table->decimal('total_value', 12, 4)->nullable()->comment('Total value of reservation');
            $table->string('currency', 3)->default('PLN')->comment('Currency code');
            $table->string('price_group', 50)->nullable()->comment('Price group used');

            // Delivery Information
            $table->date('requested_delivery_date')->nullable()->comment('Customer requested delivery date');
            $table->date('promised_delivery_date')->nullable()->comment('Promised delivery date');
            $table->string('delivery_method', 100)->nullable()->comment('Delivery method');
            $table->text('delivery_address')->nullable()->comment('Delivery address');
            $table->text('delivery_notes')->nullable()->comment('Special delivery instructions');

            // Business Logic
            $table->text('reason')->nullable()->comment('Business reason for reservation');
            $table->text('special_instructions')->nullable()->comment('Special handling instructions');
            $table->text('notes')->nullable()->comment('Additional notes');
            $table->boolean('is_firm')->default(false)->comment('Firm reservation (cannot be auto-released)');
            $table->boolean('allow_partial')->default(true)->comment('Allow partial fulfillment');
            $table->boolean('notify_expiry')->default(true)->comment('Send notification before expiry');

            // Integration Data
            $table->json('erp_data')->nullable()->comment('
                ERP system data for this reservation:
                {
                    "baselinker": {"order_id": "12345", "status": "waiting"},
                    "subiekt_gt": {"document_number": "ZZ/001/2025", "status": "active"},
                    "dynamics": {"sales_order": "SO001", "line_number": 1}
                }
            ');

            // Audit Trail
            $table->unsignedBigInteger('reserved_by')->comment('User who created reservation');
            $table->unsignedBigInteger('confirmed_by')->nullable()->comment('User who confirmed reservation');
            $table->timestamp('confirmed_at')->nullable()->comment('When reservation was confirmed');
            $table->unsignedBigInteger('released_by')->nullable()->comment('User who released reservation');
            $table->timestamp('released_at')->nullable()->comment('When reservation was released');
            $table->text('release_reason')->nullable()->comment('Reason for release');
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('product_stock_id')->references('id')->on('product_stock')->onDelete('cascade');
            $table->foreign('reserved_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('released_by')->references('id')->on('users')->onDelete('set null');

            // Business Constraints
            $table->unique(['reservation_number'], 'uk_reservation_number');

            // Performance Indexes
            $table->index(['product_id', 'warehouse_id', 'status'], 'idx_reservations_product_warehouse_status');
            $table->index(['status', 'expires_at'], 'idx_reservations_status_expiry');
            $table->index(['reference_type', 'reference_id'], 'idx_reservations_reference');
            $table->index(['customer_id', 'status'], 'idx_reservations_customer_status');
            $table->index(['reserved_at', 'status'], 'idx_reservations_date_status');
            $table->index(['priority', 'reserved_at'], 'idx_reservations_priority_date');
            $table->index(['warehouse_id', 'status'], 'idx_reservations_warehouse_status');

            // Expiry management indexes
            $table->index(['expires_at', 'auto_release'], 'idx_reservations_auto_expiry')
                  ->where('status', 'in', ['pending', 'confirmed']);
            $table->index(['notify_expiry', 'expires_at'], 'idx_reservations_expiry_notify')
                  ->where('expires_at', '>=', DB::raw('NOW()'));

            // Active reservations index (most frequent queries)
            $table->index(['status', 'product_id', 'warehouse_id'], 'idx_reservations_active')
                  ->where('status', 'in', ['pending', 'confirmed', 'partial']);

            $table->comment('PPM Stock Reservations: Advanced stock allocation system');
        });

        // Add check constraints dla business rules
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE stock_reservations ADD CONSTRAINT chk_reservations_quantities_logical CHECK (
                quantity_requested > 0 AND
                quantity_reserved >= 0 AND
                quantity_reserved <= quantity_requested AND
                quantity_fulfilled >= 0 AND
                quantity_fulfilled <= quantity_reserved
            )');
            DB::statement('ALTER TABLE stock_reservations ADD CONSTRAINT chk_reservations_priority_range CHECK (priority BETWEEN 1 AND 10)');
            DB::statement('ALTER TABLE stock_reservations ADD CONSTRAINT chk_reservations_duration_positive CHECK (duration_minutes IS NULL OR duration_minutes > 0)');
            DB::statement('ALTER TABLE stock_reservations ADD CONSTRAINT chk_reservations_prices_positive CHECK (
                (unit_price IS NULL OR unit_price >= 0) AND (total_value IS NULL OR total_value >= 0)
            )');
            DB::statement('ALTER TABLE stock_reservations ADD CONSTRAINT chk_reservations_dates_logical CHECK (
                expires_at IS NULL OR expires_at >= reserved_at
            )');
        }
    }

    /**
     * Reverse the migrations.
     *
     * Drops stock_reservations table z constraint cleanup
     * Safe rollback z proper foreign key handling
     */
    public function down(): void
    {
        // Drop check constraints first (MySQL)
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE stock_reservations DROP CONSTRAINT IF EXISTS chk_reservations_quantities_logical');
            DB::statement('ALTER TABLE stock_reservations DROP CONSTRAINT IF EXISTS chk_reservations_priority_range');
            DB::statement('ALTER TABLE stock_reservations DROP CONSTRAINT IF EXISTS chk_reservations_duration_positive');
            DB::statement('ALTER TABLE stock_reservations DROP CONSTRAINT IF EXISTS chk_reservations_prices_positive');
            DB::statement('ALTER TABLE stock_reservations DROP CONSTRAINT IF EXISTS chk_reservations_dates_logical');
        }

        Schema::dropIfExists('stock_reservations');
    }
};