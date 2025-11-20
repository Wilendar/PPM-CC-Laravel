<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_07 FAZA 5 - Migration 5/5
     *
     * Extends variant_images table with lazy caching support for PrestaShop image URLs.
     *
     * PURPOSE:
     * - Enable lazy loading of PrestaShop images (URL → local cache)
     * - Avoid repeated API calls for same images
     * - Support cleanup of old cached images (30-day retention)
     * - Track cache status and timestamps
     *
     * BUSINESS RULES:
     * - image_url: Original PrestaShop image URL
     * - is_cached: Flag indicating local cache exists
     * - cache_path: Local storage path (storage/app/ps_images_cache/...)
     * - cached_at: Timestamp for cache expiration checks
     *
     * CACHING WORKFLOW:
     * 1. Import variants from PrestaShop → store image_url, is_cached=false
     * 2. User views variant → download image → store to cache_path, is_cached=true, cached_at=now()
     * 3. Cleanup job → delete cached images older than 30 days
     *
     * EXAMPLES:
     * - image_url='https://shop.com/img/p/1/2/3/123.jpg', is_cached=false
     * - cache_path='ps_images_cache/shop_1/variant_5/image_123.jpg', is_cached=true
     *
     * RELATIONSHIPS:
     * - Extends existing variant_images table (no new foreign keys)
     */
    public function up(): void
    {
        Schema::table('variant_images', function (Blueprint $table) {
            // PrestaShop image URL (original source)
            $table->string('image_url', 500)->nullable()
                  ->after('image_path')
                  ->comment('PrestaShop image URL (for API imports)');

            // Cache status
            $table->boolean('is_cached')->default(false)
                  ->after('image_url')
                  ->comment('Local cache exists?');

            // Cache storage path
            $table->string('cache_path')->nullable()
                  ->after('is_cached')
                  ->comment('Local cache path: storage/app/ps_images_cache/...');

            // Cache timestamp (for cleanup)
            $table->timestamp('cached_at')->nullable()
                  ->after('cache_path')
                  ->comment('When image was cached locally');

            // Index for cleanup queries (find old cached images)
            $table->index(['is_cached', 'cached_at'], 'idx_variant_img_cache');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variant_images', function (Blueprint $table) {
            $table->dropIndex('idx_variant_img_cache');
            $table->dropColumn(['image_url', 'is_cached', 'cache_path', 'cached_at']);
        });
    }
};
