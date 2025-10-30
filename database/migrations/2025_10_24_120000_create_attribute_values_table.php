<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05b FAZA 2 - Migration for attribute_values table
     *
     * PURPOSE:
     * - Store predefined values for each attribute type (database-backed, NOT hardcoded!)
     * - Enable dynamic CRUD for attribute values
     * - Support color picker (color_hex column)
     * - Sortable values (position column)
     *
     * EXAMPLES:
     * - attribute_type_id: 1 (Color), code: 'red', label: 'Czerwony', color_hex: '#ff0000'
     * - attribute_type_id: 2 (Size), code: 'xl', label: 'XL', color_hex: null
     *
     * RELATIONSHIPS:
     * - belongs to AttributeType
     *
     * @version 1.0
     * @since 2025-10-24
     */
    public function up(): void
    {
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();

            // Foreign key to attribute_types
            $table->foreignId('attribute_type_id')
                  ->constrained('attribute_types')
                  ->onDelete('cascade'); // Cascade delete when type deleted

            // Value identification
            $table->string('code', 50); // 'red', 'xl', 'cotton'
            $table->string('label', 100); // 'Czerwony', 'XL', 'Bawełna'

            // Optional color hex (for color types)
            $table->string('color_hex', 7)->nullable(); // '#ff0000'

            // Ordering and status
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Unique constraint (code unique per attribute type)
            $table->unique(['attribute_type_id', 'code'], 'unique_code_per_type');

            // Indexes for performance
            $table->index('attribute_type_id', 'idx_attr_value_type');
            $table->index('is_active', 'idx_attr_value_active');
            $table->index('position', 'idx_attr_value_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
