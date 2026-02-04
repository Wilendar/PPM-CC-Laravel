<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix missing columns in product_scan_results and pending_products
 *
 * Fixes:
 * 1. Adds resolution_reason column to product_scan_results
 * 2. Makes imported_by nullable in pending_products (if needed)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add resolution_reason to product_scan_results
        if (!Schema::hasColumn('product_scan_results', 'resolution_reason')) {
            Schema::table('product_scan_results', function (Blueprint $table) {
                $table->text('resolution_reason')->nullable()->after('resolution_status');
            });
        }

        // Modify pending_products.imported_by to be nullable with default null
        if (Schema::hasColumn('pending_products', 'imported_by')) {
            Schema::table('pending_products', function (Blueprint $table) {
                $table->unsignedBigInteger('imported_by')->nullable()->default(null)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('product_scan_results', 'resolution_reason')) {
            Schema::table('product_scan_results', function (Blueprint $table) {
                $table->dropColumn('resolution_reason');
            });
        }
    }
};
