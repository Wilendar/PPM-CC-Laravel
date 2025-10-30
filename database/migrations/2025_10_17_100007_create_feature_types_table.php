<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05a FAZA 1 - Migration 7/15
     *
     * Creates feature_types table for defining types of product features (parametry techniczne).
     *
     * PURPOSE:
     * - Define available feature types (Engine Type, Power, Weight, etc.)
     * - Control value type (text, number, bool, select) for validation
     * - Support units (kW, kg, mm, etc.) for numeric features
     * - Enable/disable specific feature types
     *
     * DIFFERENCE FROM ATTRIBUTES:
     * - Attributes = variant differentiation (Size, Color) → MULTIPLE variants per product
     * - Features = product specifications (Power, Weight) → ONE value per product
     *
     * EXAMPLES:
     * - name: "Engine Type", code: "engine_type", value_type: "select", unit: null
     * - name: "Power", code: "power", value_type: "number", unit: "kW"
     * - name: "Weight", code: "weight", value_type: "number", unit: "kg"
     * - name: "Waterproof", code: "waterproof", value_type: "bool", unit: null
     *
     * RELATIONSHIPS:
     * - has many FeatureValues
     * - has many ProductFeatures (through FeatureValues)
     */
    public function up(): void
    {
        Schema::create('feature_types', function (Blueprint $table) {
            $table->id();

            // Feature type definition
            $table->string('name', 100); // "Engine Type", "Power", "Weight"
            $table->string('code', 50)->unique(); // "engine_type", "power", "weight"

            // Value configuration
            $table->enum('value_type', ['text', 'number', 'bool', 'select'])
                  ->default('text');
            $table->string('unit', 20)->nullable(); // "kW", "kg", "mm"

            // Ordering and status
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes for performance
            $table->index('code', 'idx_feature_type_code');
            $table->index('is_active', 'idx_feature_type_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_types');
    }
};
