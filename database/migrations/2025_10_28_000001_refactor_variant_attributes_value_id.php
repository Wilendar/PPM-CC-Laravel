<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * HOTFIX: Refactor variant_attributes table - value_id foreign key
     *
     * BACKGROUND:
     * - Original schema (Phase 1): value (string) + value_code (string)
     * - New schema (Phase 2+): value_id → attribute_values.id
     * - Phase 3+4+5 code expects value_id column
     *
     * CHANGES:
     * 1. Drop old columns: value, value_code
     * 2. Add new column: value_id (foreignId → attribute_values.id)
     *
     * SAFE BECAUSE: variant_attributes table is empty (verified 2025-10-28)
     */
    public function up(): void
    {
        // SAFE DROP: Check if columns exist before dropping
        if (Schema::hasColumn('variant_attributes', 'value')) {
            Schema::table('variant_attributes', function (Blueprint $table) {
                $table->dropColumn('value');
            });
        }

        if (Schema::hasColumn('variant_attributes', 'value_code')) {
            Schema::table('variant_attributes', function (Blueprint $table) {
                $table->dropColumn('value_code');
            });
        }

        // Add new value_id foreign key (only if doesn't exist)
        if (!Schema::hasColumn('variant_attributes', 'value_id')) {
            Schema::table('variant_attributes', function (Blueprint $table) {
                $table->foreignId('value_id')
                      ->after('attribute_type_id')
                      ->constrained('attribute_values')
                      ->cascadeOnDelete();
            });
        }

        // Drop old index (try-catch if doesn't exist)
        try {
            Schema::table('variant_attributes', function (Blueprint $table) {
                $table->dropIndex('idx_variant_attr_value');
            });
        } catch (\Exception $e) {
            // Index doesn't exist - that's OK
        }

        // Add new index for value_id (idempotent)
        Schema::table('variant_attributes', function (Blueprint $table) {
            // Only add if column exists and index doesn't
            if (Schema::hasColumn('variant_attributes', 'value_id')) {
                try {
                    $table->index('value_id', 'idx_variant_value_id');
                } catch (\Exception $e) {
                    // Index already exists - that's OK
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variant_attributes', function (Blueprint $table) {
            // Restore old columns
            $table->string('value', 255)->after('attribute_type_id');
            $table->string('value_code', 100)->nullable()->after('value');

            // Drop new foreign key and column
            $table->dropForeign(['value_id']);
            $table->dropColumn('value_id');

            // Restore old index
            $table->index('value_code', 'idx_variant_attr_value');

            // Drop new index
            $table->dropIndex('idx_variant_value_id');
        });
    }
};
