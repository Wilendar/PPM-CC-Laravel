<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05a FAZA 1 - Migration 2/15
     *
     * Creates attribute_types table for defining types of variant attributes.
     *
     * PURPOSE:
     * - Define available attribute types (Size, Color, Material, etc.)
     * - Control UI display type (dropdown, radio, color picker, buttons)
     * - Enable/disable specific attribute types
     *
     * EXAMPLES:
     * - name: "Size", code: "size", display_type: "dropdown"
     * - name: "Color", code: "color", display_type: "color"
     * - name: "Material", code: "material", display_type: "radio"
     *
     * RELATIONSHIPS:
     * - has many VariantAttributes
     */
    public function up(): void
    {
        Schema::create('attribute_types', function (Blueprint $table) {
            $table->id();

            // Attribute type definition
            $table->string('name', 100); // "Size", "Color", "Material"
            $table->string('code', 50)->unique(); // "size", "color", "material"

            // UI display configuration
            $table->enum('display_type', ['dropdown', 'radio', 'color', 'button'])
                  ->default('dropdown');

            // Ordering and status
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes for performance
            $table->index('code', 'idx_attr_type_code');
            $table->index('is_active', 'idx_attr_type_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_types');
    }
};
