<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add SKU Backup Columns to vehicle_compatibility Table
 *
 * Purpose: SKU-FIRST architecture enhancement (compliance with SKU_ARCHITECTURE_GUIDE.md)
 * Context: Part of ETAP_05a SKU-first compliance improvements
 *
 * Adds:
 * - part_sku (VARCHAR 255, NULLABLE) - SKU backup for part product lookup
 * - vehicle_sku (VARCHAR 255, NULLABLE) - SKU backup for vehicle product lookup
 * - Indexes: idx_vehicle_compat_part_sku, idx_vehicle_compat_vehicle_sku, idx_vehicle_compat_sku_pair
 *
 * Why NULLABLE:
 * - Backward compatibility (existing rows won't break)
 * - Allows gradual SKU population via background jobs
 * - Fallback to product_id lookup still works
 *
 * Related:
 * - _DOCS/SKU_ARCHITECTURE_GUIDE.md - SKU-first patterns
 * - Plan_Projektu/ETAP_05a_Produkty.md - Section 1.3.1 (lines 890-910)
 * - CLAUDE.md - SKU jako główny klucz produktu
 *
 * @see https://laravel.com/docs/12.x/migrations#adding-indexes
 * @created 2025-10-17
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only add columns if table exists and columns don't exist yet
        if (Schema::hasTable('vehicle_compatibility')) {
            Schema::table('vehicle_compatibility', function (Blueprint $table) {
                // Check if columns already exist (avoid duplicate column error)
                if (!Schema::hasColumn('vehicle_compatibility', 'part_sku')) {
                    $table->string('part_sku', 255)
                        ->nullable()
                        ->after('part_product_id')
                        ->comment('SKU backup dla part product lookup (gdy product_id zmieni się)');
                }

                if (!Schema::hasColumn('vehicle_compatibility', 'vehicle_sku')) {
                    $table->string('vehicle_sku', 255)
                        ->nullable()
                        ->after('vehicle_product_id')
                        ->comment('SKU backup dla vehicle product lookup (gdy product_id zmieni się)');
                }
            });

            // Add indexes in separate schema call (recommended Laravel pattern)
            Schema::table('vehicle_compatibility', function (Blueprint $table) {
                // Check if indexes don't exist before creating
                if (!Schema::hasIndex('vehicle_compatibility', 'idx_vehicle_compat_part_sku')) {
                    $table->index('part_sku', 'idx_vehicle_compat_part_sku');
                }

                if (!Schema::hasIndex('vehicle_compatibility', 'idx_vehicle_compat_vehicle_sku')) {
                    $table->index('vehicle_sku', 'idx_vehicle_compat_vehicle_sku');
                }

                if (!Schema::hasIndex('vehicle_compatibility', 'idx_vehicle_compat_sku_pair')) {
                    $table->index(['part_sku', 'vehicle_sku'], 'idx_vehicle_compat_sku_pair');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('vehicle_compatibility')) {
            Schema::table('vehicle_compatibility', function (Blueprint $table) {
                // Drop indexes first (Laravel best practice)
                if (Schema::hasIndex('vehicle_compatibility', 'idx_vehicle_compat_sku_pair')) {
                    $table->dropIndex('idx_vehicle_compat_sku_pair');
                }

                if (Schema::hasIndex('vehicle_compatibility', 'idx_vehicle_compat_vehicle_sku')) {
                    $table->dropIndex('idx_vehicle_compat_vehicle_sku');
                }

                if (Schema::hasIndex('vehicle_compatibility', 'idx_vehicle_compat_part_sku')) {
                    $table->dropIndex('idx_vehicle_compat_part_sku');
                }
            });

            // Drop columns in separate schema call
            Schema::table('vehicle_compatibility', function (Blueprint $table) {
                if (Schema::hasColumn('vehicle_compatibility', 'vehicle_sku')) {
                    $table->dropColumn('vehicle_sku');
                }

                if (Schema::hasColumn('vehicle_compatibility', 'part_sku')) {
                    $table->dropColumn('part_sku');
                }
            });
        }
    }
};
