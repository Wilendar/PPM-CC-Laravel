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
        Schema::table('variant_attributes', function (Blueprint $table) {
            // Drop old columns (string-based values)
            $table->dropColumn(['value', 'value_code']);

            // Add new value_id foreign key
            $table->foreignId('value_id')
                  ->after('attribute_type_id')
                  ->constrained('attribute_values')
                  ->cascadeOnDelete();

            // Drop old index (no longer needed)
            $table->dropIndex('idx_variant_attr_value');

            // Add new index for value_id
            $table->index('value_id', 'idx_variant_value_id');
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
