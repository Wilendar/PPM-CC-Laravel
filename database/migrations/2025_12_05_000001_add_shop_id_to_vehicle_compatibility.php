<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05d FAZA 1 - Migration 1/4
     *
     * Adds per-shop support to vehicle_compatibility table.
     *
     * CHANGES:
     * 1. TRUNCATE old data (user confirmed: start from scratch)
     * 2. Add shop_id column (NOT NULL - per-shop compatibility)
     * 3. Update unique constraint: (product_id, vehicle_model_id, shop_id)
     * 4. Add performance indexes
     *
     * BUSINESS RULES:
     * - Each product can have MULTIPLE compatibility records per vehicle (one per shop)
     * - Same product-vehicle pair can have DIFFERENT types per shop:
     *   - MRF26-73-012 → KAYO 125 TD = Original for B2B
     *   - MRF26-73-012 → KAYO 125 TD = Replacement for Pitbike.pl
     *
     * USER DECISIONS (2025-12-05):
     * - AI Suggestions: Per-Shop
     * - Existing data: DELETE (outdated, will import fresh from PrestaShop/CSV)
     * - Granularity: PARENT level (product, not variant)
     */
    public function up(): void
    {
        // Step 1: Truncate old data (user confirmed: start from scratch)
        // Using Schema::disableForeignKeyConstraints for safe truncate
        Schema::disableForeignKeyConstraints();

        DB::table('vehicle_compatibility')->truncate();
        DB::table('vehicle_compatibility_cache')->truncate();

        Schema::enableForeignKeyConstraints();

        // Step 2: Drop old unique constraint
        Schema::table('vehicle_compatibility', function (Blueprint $table) {
            $table->dropUnique('uniq_compat_product_vehicle');
        });

        // Step 3: Add shop_id column (NOT NULL - per-shop required)
        Schema::table('vehicle_compatibility', function (Blueprint $table) {
            $table->foreignId('shop_id')
                  ->after('vehicle_model_id')
                  ->constrained('prestashop_shops')
                  ->cascadeOnDelete()
                  ->comment('Per-shop compatibility (required)');
        });

        // Step 4: Add new unique constraint WITH shop_id
        Schema::table('vehicle_compatibility', function (Blueprint $table) {
            $table->unique(
                ['product_id', 'vehicle_model_id', 'shop_id'],
                'uniq_compat_product_vehicle_shop'
            );
        });

        // Step 5: Add performance indexes
        Schema::table('vehicle_compatibility', function (Blueprint $table) {
            $table->index(['shop_id', 'product_id'], 'idx_compat_shop_product');
            $table->index(['shop_id', 'vehicle_model_id'], 'idx_compat_shop_vehicle');
        });

        // Step 6: Add is_suggested and confidence_score for Smart Suggestions
        Schema::table('vehicle_compatibility', function (Blueprint $table) {
            $table->boolean('is_suggested')->default(false)->after('notes')
                  ->comment('True if added via SmartSuggestionEngine');
            $table->decimal('confidence_score', 3, 2)->nullable()->after('is_suggested')
                  ->comment('AI confidence 0.00-1.00 (null = manual)');
            $table->json('metadata')->nullable()->after('confidence_score')
                  ->comment('Additional metadata JSON');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_compatibility', function (Blueprint $table) {
            // Remove Smart Suggestions columns
            $table->dropColumn(['is_suggested', 'confidence_score', 'metadata']);

            // Remove indexes
            $table->dropIndex('idx_compat_shop_product');
            $table->dropIndex('idx_compat_shop_vehicle');

            // Remove new unique constraint
            $table->dropUnique('uniq_compat_product_vehicle_shop');

            // Remove shop_id
            $table->dropForeign(['shop_id']);
            $table->dropColumn('shop_id');

            // Restore old unique constraint
            $table->unique(['product_id', 'vehicle_model_id'], 'uniq_compat_product_vehicle');
        });
    }
};
