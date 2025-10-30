<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add SKU Column to vehicle_compatibility_cache Table
 *
 * Purpose: SKU-FIRST cache key architecture enhancement
 * Context: Part of ETAP_05a SKU-first compliance improvements
 *
 * Adds:
 * - part_sku (VARCHAR 255, NULLABLE) - SKU backup for cache key generation
 * - Index: idx_compat_cache_part_sku - Fast SKU-based cache lookups
 *
 * Cache Key Pattern:
 * OLD: "product:{product_id}:shop:{shop_id}:compatibility" ❌ Breaks on product ID change
 * NEW: "sku:{part_sku}:shop:{shop_id}:compatibility" ✅ Survives product re-import
 *
 * Why NULLABLE:
 * - Backward compatibility (existing cache rows won't break)
 * - Allows gradual SKU population during cache refresh
 * - Fallback to product_id-based cache key still works
 *
 * Related:
 * - _DOCS/SKU_ARCHITECTURE_GUIDE.md - Cache keys based on SKU (not ID)
 * - Plan_Projektu/ETAP_05a_Produkty.md - Section 1.3.2 (lines 934-940)
 * - CLAUDE.md - SKU jako główny klucz produktu
 *
 * @see https://laravel.com/docs/12.x/migrations#available-index-types
 * @created 2025-10-17
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only add column if table exists and column doesn't exist yet
        if (Schema::hasTable('vehicle_compatibility_cache')) {
            Schema::table('vehicle_compatibility_cache', function (Blueprint $table) {
                // Check if column already exists (avoid duplicate column error)
                if (!Schema::hasColumn('vehicle_compatibility_cache', 'part_sku')) {
                    $table->string('part_sku', 255)
                        ->nullable()
                        ->after('part_product_id')
                        ->comment('SKU backup dla cache key generation (cache key = sku:{part_sku}:shop:{shop_id}:compatibility)');
                }
            });

            // Add index in separate schema call (recommended Laravel pattern)
            Schema::table('vehicle_compatibility_cache', function (Blueprint $table) {
                // Check if index doesn't exist before creating
                if (!Schema::hasIndex('vehicle_compatibility_cache', 'idx_compat_cache_part_sku')) {
                    $table->index('part_sku', 'idx_compat_cache_part_sku');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('vehicle_compatibility_cache')) {
            // Drop index first (Laravel best practice)
            Schema::table('vehicle_compatibility_cache', function (Blueprint $table) {
                if (Schema::hasIndex('vehicle_compatibility_cache', 'idx_compat_cache_part_sku')) {
                    $table->dropIndex('idx_compat_cache_part_sku');
                }
            });

            // Drop column in separate schema call
            Schema::table('vehicle_compatibility_cache', function (Blueprint $table) {
                if (Schema::hasColumn('vehicle_compatibility_cache', 'part_sku')) {
                    $table->dropColumn('part_sku');
                }
            });
        }
    }
};
