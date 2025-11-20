<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Conflict Resolution Columns to product_shop_data
 *
 * PROBLEM 9.3: Conflict Resolution System (ETAP_07_Prestashop_API.md)
 *
 * Adds columns to store conflict data for manual resolution:
 * - conflict_log: JSON array of detected conflicts (field name, PPM value, PrestaShop value)
 * - has_conflicts: Boolean flag for quick filtering of products with conflicts
 * - conflicts_detected_at: Timestamp when conflicts were first detected
 *
 * USAGE:
 * php artisan migrate
 *
 * @since 2025-11-13
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            // conflict_log: Store detailed conflict information
            // Example: {"name": {"field": "name", "ppm": "Product A", "prestashop": "Product B"}}
            $table->json('conflict_log')->nullable()->after('conflict_data');

            // has_conflicts: Quick boolean flag for filtering
            $table->boolean('has_conflicts')->default(false)->after('conflict_log');

            // conflicts_detected_at: Timestamp when conflicts were first detected
            $table->timestamp('conflicts_detected_at')->nullable()->after('has_conflicts');

            // Add index for filtering products with conflicts
            $table->index(['has_conflicts', 'conflicts_detected_at'], 'idx_conflicts_filter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex('idx_conflicts_filter');

            // Drop columns
            $table->dropColumn(['conflict_log', 'has_conflicts', 'conflicts_detected_at']);
        });
    }
};
