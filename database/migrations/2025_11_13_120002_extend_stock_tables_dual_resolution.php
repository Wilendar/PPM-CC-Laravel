<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Extend Stock Tables with Dual Resolution (Strategy B - Complex)
 *
 * CRITICAL Strategy B Feature:
 * - Add shop_id column for shop-specific stock overrides
 * - Preserve existing warehouse_id column (NO data loss)
 * - Dual-column support: warehouse_id OR shop_id (NOT both)
 *
 * Stock Resolution Logic:
 * - warehouse_id = NULL, shop_id = NULL → Default stock
 * - warehouse_id = X, shop_id = NULL → Warehouse stock (global)
 * - warehouse_id = NULL, shop_id = Y → Shop override (shop-specific)
 * - warehouse_id = X, shop_id = Y → INVALID (enforced by app logic)
 *
 * Performance:
 * - Dual indexes for both resolution paths
 * - Composite unique constraint (product, warehouse, shop)
 *
 * @package Database\Migrations
 * @version Strategy B - Complex Warehouse Redesign
 * @since 2025-11-13
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Extends product_stock table with shop-specific stock support
     * ZERO data loss - preserves all existing warehouse stock data
     */
    public function up(): void
    {
        Schema::table('product_stock', function (Blueprint $table) {
            // CRITICAL: Add shop_id for shop-specific stock overrides
            // This is the KEY difference from Strategy A (which would drop shop_specific)
            if (!Schema::hasColumn('product_stock', 'shop_id')) {
                $table->foreignId('shop_id')
                      ->nullable()
                      ->after('warehouse_id')
                      ->constrained('prestashop_shops')
                      ->onDelete('cascade')
                      ->comment('PrestaShop shop ID for shop-specific stock overrides (Strategy B)');
            }
        });

        // Add dual-resolution indexes (AFTER column creation)
        Schema::table('product_stock', function (Blueprint $table) {
            // Drop old unique constraint (product_id, product_variant_id, warehouse_id)
            // We need to replace it with new constraint that includes shop_id
            if (Schema::hasIndex('product_stock', 'uk_product_variant_warehouse')) {
                $table->dropUnique('uk_product_variant_warehouse');
            }

            // New composite unique constraint (Strategy B)
            // Allows: (prod, warehouse, null) OR (prod, null, shop) but NOT (prod, warehouse, shop)
            $table->unique(
                ['product_id', 'product_variant_id', 'warehouse_id', 'shop_id'],
                'uk_product_variant_warehouse_shop'
            );

            // Performance indexes for dual-column queries
            // Index 1: Product + Warehouse (global stock queries)
            if (!Schema::hasIndex('product_stock', 'idx_product_warehouse_stock')) {
                $table->index(
                    ['product_id', 'warehouse_id'],
                    'idx_product_warehouse_stock'
                );
            }

            // Index 2: Product + Shop (shop-specific override queries)
            if (!Schema::hasIndex('product_stock', 'idx_product_shop_stock')) {
                $table->index(
                    ['product_id', 'shop_id'],
                    'idx_product_shop_stock'
                );
            }

            // Index 3: Shop-only queries (for shop dashboard)
            if (!Schema::hasIndex('product_stock', 'idx_shop_stock')) {
                $table->index('shop_id', 'idx_shop_stock');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * Rollback: Remove shop_id and restore original constraints
     * Safe rollback to pre-Strategy-B state
     */
    public function down(): void
    {
        Schema::table('product_stock', function (Blueprint $table) {
            // Drop new indexes
            if (Schema::hasIndex('product_stock', 'idx_product_warehouse_stock')) {
                $table->dropIndex('idx_product_warehouse_stock');
            }
            if (Schema::hasIndex('product_stock', 'idx_product_shop_stock')) {
                $table->dropIndex('idx_product_shop_stock');
            }
            if (Schema::hasIndex('product_stock', 'idx_shop_stock')) {
                $table->dropIndex('idx_shop_stock');
            }

            // Drop new composite unique
            if (Schema::hasIndex('product_stock', 'uk_product_variant_warehouse_shop')) {
                $table->dropUnique('uk_product_variant_warehouse_shop');
            }

            // Restore original unique constraint
            $table->unique(
                ['product_id', 'product_variant_id', 'warehouse_id'],
                'uk_product_variant_warehouse'
            );

            // Drop shop_id column and foreign key
            if (Schema::hasColumn('product_stock', 'shop_id')) {
                $table->dropForeign(['shop_id']);
                $table->dropColumn('shop_id');
            }
        });
    }
};
