<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05a FAZA 1 - Migration 3/15
     *
     * Creates variant_attributes table - pivot table for variant-attribute relationships.
     *
     * PURPOSE:
     * - Link variants to their attribute values (e.g., variant "Red XL Jacket" has Color=Red, Size=XL)
     * - Store both display value and normalized code for matching
     *
     * BUSINESS RULES:
     * - Each variant can have ONLY ONE value per attribute type (unique constraint)
     * - Cascade delete: if variant deleted → attributes deleted
     * - Cascade delete: if attribute type deleted → variant attributes deleted
     *
     * EXAMPLES:
     * - variant_id=1, attribute_type_id=1(Size), value="XL", value_code="xl"
     * - variant_id=1, attribute_type_id=2(Color), value="Red", value_code="red"
     *
     * RELATIONSHIPS:
     * - belongs to ProductVariant (cascade delete)
     * - belongs to AttributeType (cascade delete)
     */
    public function up(): void
    {
        Schema::create('variant_attributes', function (Blueprint $table) {
            $table->id();

            // Relations (both cascade delete)
            $table->foreignId('variant_id')
                  ->constrained('product_variants')
                  ->cascadeOnDelete();

            $table->foreignId('attribute_type_id')
                  ->constrained('attribute_types')
                  ->cascadeOnDelete();

            // Attribute value (both display and normalized)
            $table->string('value', 255); // "XL", "Red", "Steel"
            $table->string('value_code', 100)->nullable(); // "xl", "red", "steel" (for matching)

            $table->timestamps();

            // Indexes for performance
            $table->index(['variant_id', 'attribute_type_id'], 'idx_variant_attr_type');
            $table->index('value_code', 'idx_variant_attr_value');

            // Unique constraint: ONE attribute type per variant
            $table->unique(['variant_id', 'attribute_type_id'], 'uniq_variant_attr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_attributes');
    }
};
