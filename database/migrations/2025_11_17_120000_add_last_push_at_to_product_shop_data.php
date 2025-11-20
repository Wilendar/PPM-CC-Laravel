<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add last_push_at column to product_shop_data table
 *
 * ETAP_13: Sync Panel UX Refactoring - Backend Foundation
 *
 * Separate timestamp dla PPM → PS push operations
 * (currently using last_sync_at which is ambiguous)
 *
 * Harmonogram timestamps:
 * - last_pulled_at: PrestaShop → PPM (read) - ALREADY EXISTS (2025-11-06)
 * - last_push_at:   PPM → PrestaShop (write) - NEW
 * - last_sync_at:   Generic timestamp (keep for backward compat)
 *
 * @since ETAP_13 (2025-11-17)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            $table->timestamp('last_push_at')
                  ->nullable()
                  ->after('last_pulled_at')
                  ->comment('Last time PPM data was pushed to PrestaShop');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            $table->dropColumn('last_push_at');
        });
    }
};
