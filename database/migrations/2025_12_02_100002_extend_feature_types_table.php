<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_07e FAZA 1.1.1 - Extend feature_types table
     *
     * PURPOSE:
     * - Add FK to feature_groups table (normalize grouping)
     * - Add new fields for Excel import/PrestaShop sync
     * - Add validation and placeholder fields
     *
     * NEW COLUMNS:
     * - feature_group_id: FK to feature_groups (replaces string 'group')
     * - input_placeholder: Hint text for input field
     * - validation_rules: JSON rules (min, max, pattern)
     * - conditional_group: 'elektryczne'/'spalinowe' for conditional display
     * - excel_column: Original Excel column letter (for import mapping)
     * - prestashop_name: Name as it appears in PrestaShop
     */
    public function up(): void
    {
        Schema::table('feature_types', function (Blueprint $table) {
            // FK to feature_groups (nullable for migration compatibility)
            $table->foreignId('feature_group_id')
                  ->nullable()
                  ->after('group')
                  ->constrained('feature_groups')
                  ->nullOnDelete();

            // Input configuration
            $table->string('input_placeholder', 100)
                  ->nullable()
                  ->after('unit')
                  ->comment('Placeholder text for input field');

            // Validation rules as JSON
            $table->json('validation_rules')
                  ->nullable()
                  ->after('input_placeholder')
                  ->comment('JSON: {min, max, pattern, required}');

            // Conditional display
            $table->string('conditional_group', 50)
                  ->nullable()
                  ->after('validation_rules')
                  ->comment('elektryczne/spalinowe - show only for this vehicle type');

            // Excel mapping
            $table->string('excel_column', 10)
                  ->nullable()
                  ->after('conditional_group')
                  ->comment('Original Excel column letter (A, B, AA, etc.)');

            // PrestaShop mapping
            $table->string('prestashop_name', 100)
                  ->nullable()
                  ->after('excel_column')
                  ->comment('Feature name in PrestaShop');

            // Indexes
            $table->index('feature_group_id', 'idx_ft_group_id');
            $table->index('conditional_group', 'idx_ft_conditional');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feature_types', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_ft_group_id');
            $table->dropIndex('idx_ft_conditional');

            // Drop FK constraint
            $table->dropForeign(['feature_group_id']);

            // Drop columns
            $table->dropColumn([
                'feature_group_id',
                'input_placeholder',
                'validation_rules',
                'conditional_group',
                'excel_column',
                'prestashop_name',
            ]);
        });
    }
};
