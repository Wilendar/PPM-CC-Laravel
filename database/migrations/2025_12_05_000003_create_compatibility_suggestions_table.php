<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05d FAZA 1 - Migration 3/4
     *
     * Creates compatibility_suggestions table for AI-generated suggestions cache.
     *
     * PURPOSE:
     * - Cache SmartSuggestionEngine results (expensive to compute)
     * - Per-shop suggestions (user decision 2025-12-05)
     * - TTL-based expiration (24h default)
     * - Track applied/dismissed suggestions
     *
     * ALGORITHM (SmartSuggestionEngine):
     * - Brand match: product.manufacturer == vehicle.brand → +0.50
     * - Name match: product.name CONTAINS vehicle.model → +0.30
     * - Description match: product.description CONTAINS vehicle → +0.10
     * - Category match: matching category patterns → +0.10
     * - Total confidence: 0.00 - 1.00
     *
     * BUSINESS RULES:
     * - Suggestions are PER-SHOP (different shops may have different suggestions)
     * - Min confidence threshold from shop settings (default 0.50)
     * - Auto-apply if confidence >= 0.90 and shop.auto_apply_suggestions = true
     * - Expires after 24h, regenerated on next access
     */
    public function up(): void
    {
        Schema::create('compatibility_suggestions', function (Blueprint $table) {
            $table->id();

            // Product reference (SKU-first pattern)
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();
            $table->string('part_sku', 255)
                  ->comment('SKU backup for cache key');

            // Vehicle reference
            $table->foreignId('vehicle_model_id')
                  ->constrained('vehicle_models')
                  ->cascadeOnDelete();
            $table->string('vehicle_sku', 255)
                  ->comment('SKU backup for cache key');

            // Per-shop suggestions (user decision: per-shop)
            $table->foreignId('shop_id')
                  ->constrained('prestashop_shops')
                  ->cascadeOnDelete();

            // Suggestion details
            $table->enum('suggestion_reason', [
                'brand_match',
                'name_match',
                'description_match',
                'category_match',
                'similar_product'
            ])->comment('Primary reason for suggestion');

            $table->decimal('confidence_score', 3, 2)
                  ->comment('AI confidence 0.00-1.00');

            // Suggested compatibility type
            $table->enum('suggested_type', ['original', 'replacement'])
                  ->default('original')
                  ->comment('Suggested compatibility type');

            // Status tracking
            $table->boolean('is_applied')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('applied_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->boolean('is_dismissed')->default(false);
            $table->timestamp('dismissed_at')->nullable();
            $table->foreignId('dismissed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // TTL
            $table->timestamp('expires_at')
                  ->comment('Suggestion expires after this time (default 24h)');

            $table->timestamps();

            // Unique constraint: one suggestion per product-vehicle-shop
            $table->unique(
                ['product_id', 'vehicle_model_id', 'shop_id'],
                'uniq_suggestion_product_vehicle_shop'
            );

            // Performance indexes
            $table->index(['shop_id', 'product_id'], 'idx_suggestion_shop_product');
            $table->index(['shop_id', 'confidence_score'], 'idx_suggestion_shop_confidence');
            $table->index('expires_at', 'idx_suggestion_expires');
            $table->index('is_applied', 'idx_suggestion_applied');
            $table->index('part_sku', 'idx_suggestion_part_sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compatibility_suggestions');
    }
};
