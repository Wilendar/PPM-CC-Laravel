<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05a FAZA 1 - Migration 9/15
     *
     * Creates product_features table - pivot table for product-feature relationships.
     *
     * PURPOSE:
     * - Link products to their feature values (e.g., Product "Honda Engine" has Power=150kW, Weight=25kg)
     * - Support both predefined values (via feature_value_id) and custom free-text values
     *
     * USAGE PATTERNS:
     * - For "select" type features: Use feature_value_id (links to predefined FeatureValue)
     * - For "text/number/bool" features: Use custom_value (free-text input)
     *
     * BUSINESS RULES:
     * - Each product can have ONLY ONE value per feature type (unique constraint)
     * - Cascade delete: if product deleted → product features deleted
     * - Cascade delete: if feature type deleted → product features deleted
     * - Null on delete: if feature value deleted → product feature remains with custom_value
     *
     * EXAMPLES:
     * - product_id=1, feature_type_id=1(Engine Type), feature_value_id=5(Diesel), custom_value=null
     * - product_id=1, feature_type_id=2(Power), feature_value_id=null, custom_value="150"
     * - product_id=2, feature_type_id=3(Waterproof), feature_value_id=null, custom_value="true"
     *
     * RELATIONSHIPS:
     * - belongs to Product (cascade delete)
     * - belongs to FeatureType (cascade delete)
     * - belongs to FeatureValue (nullable, null on delete)
     */
    public function up(): void
    {
        Schema::create('product_features', function (Blueprint $table) {
            $table->id();

            // Product relation (cascade delete)
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();

            // Feature type relation (cascade delete)
            $table->foreignId('feature_type_id')
                  ->constrained('feature_types')
                  ->cascadeOnDelete();

            // Feature value relation (nullable, null on delete)
            $table->foreignId('feature_value_id')
                  ->nullable()
                  ->constrained('feature_values')
                  ->nullOnDelete();

            // Custom value for free-text features
            $table->text('custom_value')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['product_id', 'feature_type_id'], 'idx_prod_feature_type');
            $table->index('feature_value_id', 'idx_prod_feature_value');

            // Unique constraint: ONE feature type per product
            $table->unique(['product_id', 'feature_type_id'], 'uniq_prod_feature_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_features');
    }
};
