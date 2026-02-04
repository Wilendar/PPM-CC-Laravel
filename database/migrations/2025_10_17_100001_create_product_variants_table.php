<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05a FAZA 1 - Migration 1/15
     *
     * Creates product_variants table for storing product variants.
     *
     * KEY FEATURES:
     * - SKU-first architecture: Each variant has unique SKU
     * - Soft deletes: Preserve historical data
     * - Position-based ordering: For consistent display
     * - Default variant tracking: One variant can be marked as default
     *
     * RELATIONSHIPS:
     * - belongs to Product (cascade delete)
     * - has many VariantAttributes
     * - has many VariantPrices
     * - has many VariantStock
     * - has many VariantImages
     */
    public function up(): void
    {
        if (Schema::hasTable('product_variants')) {
            return;
        }

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            // Product relation (cascade delete - variant without product = invalid)
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();

            // SKU-first: variant ma własny unique SKU
            $table->string('sku', 255)->unique();

            // Variant metadata
            $table->string('name', 255);
            $table->boolean('is_default')->default(false);
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['product_id', 'is_default'], 'idx_variant_product_default');
            $table->index('is_active', 'idx_variant_active');
            $table->index('sku', 'idx_variant_sku'); // SKU-first
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
