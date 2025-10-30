<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05a FAZA 1 - Migration 15/15
     *
     * Adds variant-related columns to products table.
     *
     * PURPOSE:
     * - Track which products have variants (has_variants boolean)
     * - Link to default variant for display/pricing (default_variant_id)
     * - Enable efficient filtering (products with/without variants)
     *
     * BUSINESS RULES:
     * - has_variants=false: Product has NO variants (simple product)
     * - has_variants=true: Product has 1+ variants (variable product)
     * - default_variant_id: Points to ProductVariant.id (nullable - no default variant yet)
     * - Null on delete: if default variant deleted → default_variant_id becomes null (product remains)
     *
     * USAGE PATTERNS:
     * - Simple product: has_variants=false, default_variant_id=null
     * - Variable product: has_variants=true, default_variant_id=X (where X is variant ID)
     *
     * EXAMPLES:
     * - Product "Brake Pad Generic" → has_variants=false, default_variant_id=null
     * - Product "T-Shirt" → has_variants=true, default_variant_id=5 (points to "Medium Black" variant)
     *
     * RELATIONSHIPS:
     * - Product has many ProductVariants
     * - Product belongs to ProductVariant (default_variant_id - nullable, null on delete)
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Variant tracking
            $table->boolean('has_variants')
                  ->default(false)
                  ->after('sku')
                  ->comment('TRUE if product has variants, FALSE for simple products');

            // Default variant link (nullable - no default variant yet)
            $table->foreignId('default_variant_id')
                  ->nullable()
                  ->constrained('product_variants')
                  ->nullOnDelete()
                  ->after('has_variants')
                  ->comment('Points to default ProductVariant for display/pricing');

            // Indexes for performance
            $table->index('has_variants', 'idx_products_has_variants');
            $table->index('default_variant_id', 'idx_products_default_variant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop indexes first (Laravel best practice)
            $table->dropIndex('idx_products_default_variant');
            $table->dropIndex('idx_products_has_variants');

            // Drop foreign key constraint
            $table->dropForeign(['default_variant_id']);

            // Drop columns
            $table->dropColumn(['has_variants', 'default_variant_id']);
        });
    }
};
