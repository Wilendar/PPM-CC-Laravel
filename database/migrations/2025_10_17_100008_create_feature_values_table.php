<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05a FAZA 1 - Migration 8/15
     *
     * Creates feature_values table for storing predefined feature values.
     *
     * PURPOSE:
     * - Store predefined values for "select" type features (e.g., "Diesel", "Petrol", "Electric")
     * - Enable consistent value selection across products
     * - Support display formatting (e.g., "150" → "150 kW")
     *
     * USAGE PATTERN:
     * - For value_type="select": Use predefined FeatureValue records
     * - For value_type="text/number/bool": Use ProductFeature.custom_value (free-text)
     *
     * BUSINESS RULES:
     * - Cascade delete: if feature type deleted → feature values deleted
     * - Multiple values per feature type allowed (e.g., Engine Type: "Diesel", "Petrol", "Electric")
     *
     * EXAMPLES:
     * - feature_type_id=1(Engine Type), value="Diesel", display_value="Diesel Engine"
     * - feature_type_id=1(Engine Type), value="Petrol", display_value="Petrol Engine"
     * - feature_type_id=2(Power), value="150", display_value="150 kW"
     *
     * RELATIONSHIPS:
     * - belongs to FeatureType (cascade delete)
     * - has many ProductFeatures
     */
    public function up(): void
    {
        Schema::create('feature_values', function (Blueprint $table) {
            $table->id();

            // Feature type relation (cascade delete)
            $table->foreignId('feature_type_id')
                  ->constrained('feature_types')
                  ->cascadeOnDelete();

            // Value data
            $table->string('value', 255); // "Diesel", "150", "true"
            $table->string('display_value', 255)->nullable(); // "150 kW", "Yes"

            // Ordering
            $table->integer('position')->default(0);

            $table->timestamps();

            // Indexes for performance
            $table->index(['feature_type_id', 'value'], 'idx_feature_value_type');
            $table->index('position', 'idx_feature_value_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_values');
    }
};
