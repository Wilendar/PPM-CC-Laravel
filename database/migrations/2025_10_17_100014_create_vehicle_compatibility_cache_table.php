<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05a FAZA 1 - Migration 14/15
     *
     * Creates vehicle_compatibility_cache table for caching compatibility data.
     *
     * PURPOSE:
     * - Cache computed compatibility data per product per shop
     * - Avoid expensive JOIN queries on large compatibility dataset (10K+ products)
     * - Enable fast compatibility lookup for PrestaShop export
     * - Support TTL-based cache invalidation
     *
     * BUSINESS RULES:
     * - Cascade delete: if product deleted → cache deleted
     * - Cascade delete: if shop deleted → cache deleted
     * - TTL-based expiration (expires_at timestamp)
     * - JSON data format for flexibility
     *
     * SKU-FIRST PATTERN:
     * - part_sku column added by migration: 2025_10_17_000002_add_sku_column_to_compatibility_cache.php
     * - This migration creates base table WITHOUT SKU column
     * - Enhancement migration adds SKU column AFTER table creation
     *
     * CACHE DATA FORMAT (JSON):
     * ```json
     * {
     *   "vehicles": [
     *     {"brand": "Honda", "model": "CBR 600", "year_from": 2013, "year_to": 2020, "attribute": "Original"},
     *     {"brand": "Yamaha", "model": "YZF-R1", "year_from": 2015, "year_to": 2019, "attribute": "Replacement"}
     *   ],
     *   "cached_at": "2025-10-17 12:00:00",
     *   "total_count": 2
     * }
     * ```
     *
     * RELATIONSHIPS:
     * - belongs to Product (cascade delete)
     * - belongs to PrestaShopShop (nullable, cascade delete)
     */
    public function up(): void
    {
        Schema::create('vehicle_compatibility_cache', function (Blueprint $table) {
            $table->id();

            // Product relation (cascade delete)
            // NOTE: part_sku column added by 2025_10_17_000002_add_sku_column_to_compatibility_cache.php
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();

            // PrestaShop shop context (nullable - global cache if null)
            $table->foreignId('prestashop_shop_id')
                  ->nullable()
                  ->constrained('prestashop_shops')
                  ->cascadeOnDelete();

            // Cache data (JSON format)
            $table->text('data'); // JSON: vehicle models, compatibility attributes

            // TTL expiration
            $table->timestamp('expires_at');

            $table->timestamps();

            // Indexes for performance
            // NOTE: SKU index added by 2025_10_17_000002_add_sku_column_to_compatibility_cache.php
            $table->index(['product_id', 'prestashop_shop_id'], 'idx_cache_product_shop');
            $table->index('expires_at', 'idx_cache_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_compatibility_cache');
    }
};
