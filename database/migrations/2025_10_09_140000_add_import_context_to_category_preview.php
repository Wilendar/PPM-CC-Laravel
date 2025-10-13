<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Import Context to Category Preview
 *
 * ETAP_07 FAZA 3D: Category Import Preview System - Context Enhancement
 *
 * Purpose: Store original import context (source category, product IDs, mode)
 * Use Case: Display category mapping info in modal (PrestaShop → PPM)
 *
 * Added Field:
 * - import_context_json: Original import options from BulkImportProducts
 *   Structure: {
 *     mode: 'category'|'individual'|'bulk',
 *     options: {
 *       category_id: 12,           // PrestaShop category ID
 *       product_ids: [1, 2, 3],    // Product IDs to import
 *       ...
 *     }
 *   }
 *
 * @package PPM-CC-Laravel
 * @version 1.1
 * @since 2025-10-09
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add import_context_json field dla storing original import options
     */
    public function up(): void
    {
        Schema::table('category_preview', function (Blueprint $table) {
            $table->json('import_context_json')
                  ->nullable()
                  ->after('user_selection_json')
                  ->comment('Original import context (mode, category_id, product_ids)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Remove import_context_json field
     */
    public function down(): void
    {
        Schema::table('category_preview', function (Blueprint $table) {
            $table->dropColumn('import_context_json');
        });
    }
};
