<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05c: Per-Shop Variants System
     *
     * Creates shop_variants table for per-shop variant customization.
     *
     * FEATURES:
     * - ADD: New variants specific to shop (not in product_variants)
     * - OVERRIDE: Modify existing variants per shop
     * - DELETE: Hide variants in specific shop
     * - INHERIT: Use default variants from product_variants
     * - Sync tracking per shop
     * - PrestaShop combinations mapping
     *
     * DATA FLOW:
     * 1. Shop without overrides -> inherits from product_variants (INHERIT)
     * 2. Shop with edits -> gets own data in shop_variants (OVERRIDE)
     * 3. Shop creates new variant -> ADD operation (variant_id = null)
     * 4. Shop deletes variant -> DELETE operation (hidden, not synced)
     *
     * SYNC:
     * - pullShopData pulls variants live from PrestaShop
     * - Save triggers SyncVariantsToPrestaShopJob
     * - UI blocked during sync ("oczekiwanie na synchronizacje")
     */
    public function up(): void
    {
        Schema::create('shop_variants', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('shop_id')
                  ->constrained('prestashop_shops')
                  ->cascadeOnDelete()
                  ->comment('FK to prestashop_shops.id');

            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete()
                  ->comment('FK to products.id');

            $table->foreignId('variant_id')
                  ->nullable()
                  ->constrained('product_variants')
                  ->nullOnDelete()
                  ->comment('FK to product_variants.id (NULL for ADD operations - shop-only variants)');

            // PrestaShop Integration
            $table->unsignedBigInteger('prestashop_combination_id')
                  ->nullable()
                  ->comment('External ID from PrestaShop (ps_product_attribute.id_product_attribute)');

            // Operation Type
            $table->enum('operation_type', ['ADD', 'OVERRIDE', 'DELETE', 'INHERIT'])
                  ->default('INHERIT')
                  ->comment('ADD=new shop-only variant, OVERRIDE=modify existing, DELETE=hide in shop, INHERIT=use default');

            // Variant Data (JSON) - stores full variant data for ADD/OVERRIDE
            $table->json('variant_data')
                  ->nullable()
                  ->comment('Full variant data: {sku, name, is_active, attributes, prices, stock, images}');

            // Synchronization
            $table->enum('sync_status', ['pending', 'in_progress', 'synced', 'failed'])
                  ->default('pending')
                  ->comment('Sync status with PrestaShop');
            $table->timestamp('last_sync_at')->nullable();
            $table->text('sync_error_message')->nullable();

            // Audit
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['shop_id', 'product_id'], 'idx_shop_variants_shop_product');
            $table->index('variant_id', 'idx_shop_variants_variant');
            $table->index('prestashop_combination_id', 'idx_shop_variants_prestashop');
            $table->index('sync_status', 'idx_shop_variants_sync_status');

            // Unique Constraint - one entry per shop+product+variant combination
            $table->unique(['shop_id', 'product_id', 'variant_id'], 'uk_shop_variant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_variants');
    }
};
