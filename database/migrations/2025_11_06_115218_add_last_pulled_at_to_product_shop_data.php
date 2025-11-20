<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add last_pulled_at column to product_shop_data table
 *
 * BUGFIX 2025-11-06: Track when PrestaShop data was pulled to PPM
 * - Separate from last_sync_at (PPM → PrestaShop push)
 * - last_pulled_at tracks PrestaShop → PPM pull operations
 *
 * @package Database\Migrations
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            $table->timestamp('last_pulled_at')
                  ->nullable()
                  ->after('last_sync_at')
                  ->comment('Last time PrestaShop data was pulled to PPM');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            $table->dropColumn('last_pulled_at');
        });
    }
};
