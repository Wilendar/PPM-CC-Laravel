<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add pending_fields column to product_shop_data table
 *
 * FEATURE 2025-11-07: Field-Level Pending Tracking
 *
 * Purpose:
 * - Track WHICH specific fields are pending sync (not just "pending" status)
 * - Enables granular UI: "Oczekuje: nazwa, cena" instead of just "Oczekuje"
 * - Improves user experience by showing exactly what changed
 *
 * Schema:
 * - JSON column storing array of pending field names
 * - Example: ["name", "price", "stock_quantity"]
 * - NULL = no pending changes (sync_status should be 'synced')
 * - Empty array [] = general pending (fallback for legacy)
 *
 * Integration:
 * - Set when ProductForm saves changes to shop-specific fields
 * - Cleared when sync completes successfully
 * - Used in UI to display field-level pending indicators
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
            $table->json('pending_fields')
                  ->nullable()
                  ->after('sync_status')
                  ->comment('JSON array of field names pending sync (e.g. ["name", "price"])');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            $table->dropColumn('pending_fields');
        });
    }
};
