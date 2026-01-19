<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add prestashop_updated_at column to product_shop_data table
 *
 * PURPOSE: Cache PrestaShop's date_upd timestamp for smart sync optimization
 *
 * When pulling products from PrestaShop, we can compare:
 * - prestashop_updated_at: cached PrestaShop date_upd from last pull
 * - Fresh date_upd from PrestaShop API
 *
 * If they match (product unchanged in PrestaShop) -> skip full data fetch
 * This reduces API calls and database writes significantly for unchanged products.
 *
 * WORKFLOW:
 * 1. Job calls lightweight API: getProductsDateUpd([ids]) -> [id => date_upd]
 * 2. Compare with cached prestashop_updated_at
 * 3. If unchanged -> skip full getProduct() call
 * 4. If changed -> fetch full data, update prestashop_updated_at
 *
 * @since 2026-01-19 - Job Deduplication + date_upd Optimization
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            // PrestaShop date_upd timestamp - cached for change detection
            $table->timestamp('prestashop_updated_at')
                ->nullable()
                ->after('last_push_at')
                ->comment('Cached PrestaShop date_upd for change detection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            $table->dropColumn('prestashop_updated_at');
        });
    }
};
