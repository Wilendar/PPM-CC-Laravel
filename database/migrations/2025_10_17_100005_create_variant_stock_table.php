<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05a FAZA 1 - Migration 5/15
     *
     * Creates variant_stock table for storing variant inventory per warehouse.
     *
     * PURPOSE:
     * - Track stock levels for each variant across multiple warehouses
     * - Support reservations (quantity reserved for orders)
     * - Calculate available stock automatically (quantity - reserved)
     *
     * BUSINESS RULES:
     * - Each variant can have ONLY ONE stock record per warehouse (unique constraint)
     * - Cascade delete: if variant deleted → stock records deleted
     * - Cascade delete: if warehouse deleted → stock records deleted
     * - Available stock = quantity - reserved (computed/stored column)
     *
     * WAREHOUSES (from existing system):
     * - MPPTRADE, Pitbike.pl, Cameraman, Otopit, INFMS, Reklamacje + custom
     *
     * EXAMPLES:
     * - variant_id=1, warehouse_id=1(MPPTRADE), quantity=100, reserved=20, available=80
     * - variant_id=2, warehouse_id=2(Pitbike.pl), quantity=50, reserved=0, available=50
     *
     * RELATIONSHIPS:
     * - belongs to ProductVariant (cascade delete)
     * - belongs to Warehouse (cascade delete)
     */
    public function up(): void
    {
        Schema::create('variant_stock', function (Blueprint $table) {
            $table->id();

            // Relations (both cascade delete)
            $table->foreignId('variant_id')
                  ->constrained('product_variants')
                  ->cascadeOnDelete();

            $table->foreignId('warehouse_id')
                  ->constrained('warehouses')
                  ->cascadeOnDelete();

            // Stock data
            $table->integer('quantity')->default(0);
            $table->integer('reserved')->default(0);

            // Computed column: available = quantity - reserved
            // MySQL/MariaDB: STORED generated column
            $table->integer('available')
                  ->storedAs('quantity - reserved');

            $table->timestamps();

            // Indexes for performance
            $table->index(['variant_id', 'warehouse_id'], 'idx_variant_stock_warehouse');
            $table->index('available', 'idx_variant_stock_available');

            // Unique constraint: ONE stock record per warehouse per variant
            $table->unique(['variant_id', 'warehouse_id'], 'uniq_variant_stock_warehouse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_stock');
    }
};
