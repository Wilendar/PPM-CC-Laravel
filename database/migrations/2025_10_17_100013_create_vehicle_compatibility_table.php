<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05a FAZA 1 - Migration 13/15
     *
     * Creates vehicle_compatibility table - pivot table for product-vehicle relationships.
     *
     * PURPOSE:
     * - Link products (parts) to compatible vehicles
     * - Track compatibility metadata (type, source, verification status)
     * - Enable SKU-first lookup (SKU columns added by separate enhancement migration)
     *
     * BUSINESS RULES:
     * - Each product can have ONLY ONE compatibility record per vehicle (unique constraint)
     * - Cascade delete: if product deleted → compatibility deleted
     * - Cascade delete: if vehicle deleted → compatibility deleted
     * - Null on delete: if attribute/source deleted → compatibility remains (data preserved)
     *
     * SKU-FIRST PATTERN:
     * - part_sku, vehicle_sku columns added by migration: 2025_10_17_000001_add_sku_columns_to_vehicle_compatibility.php
     * - This migration creates base table WITHOUT SKU columns
     * - Enhancement migration adds SKU columns AFTER table creation
     *
     * EXAMPLES:
     * - product_id=123(Brake Pad), vehicle_model_id=456(Honda CBR 600 2013-2020), compatibility_attribute=Original
     * - product_id=789(Air Filter), vehicle_model_id=456(Honda CBR 600 2013-2020), compatibility_attribute=Replacement
     *
     * RELATIONSHIPS:
     * - belongs to Product (cascade delete)
     * - belongs to VehicleModel (cascade delete)
     * - belongs to CompatibilityAttribute (nullable, null on delete)
     * - belongs to CompatibilitySource (cascade delete)
     * - verified by User (nullable, null on delete)
     */
    public function up(): void
    {
        Schema::create('vehicle_compatibility', function (Blueprint $table) {
            $table->id();

            // Product relation (cascade delete)
            // NOTE: part_sku column added by 2025_10_17_000001_add_sku_columns_to_vehicle_compatibility.php
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();

            // Vehicle relation (cascade delete)
            // NOTE: vehicle_sku column added by 2025_10_17_000001_add_sku_columns_to_vehicle_compatibility.php
            $table->foreignId('vehicle_model_id')
                  ->constrained('vehicle_models')
                  ->cascadeOnDelete();

            // Compatibility metadata (nullable - preserve data if attribute/source deleted)
            $table->foreignId('compatibility_attribute_id')
                  ->nullable()
                  ->constrained('compatibility_attributes')
                  ->nullOnDelete();

            $table->foreignId('compatibility_source_id')
                  ->constrained('compatibility_sources')
                  ->cascadeOnDelete();

            // Additional info
            $table->text('notes')->nullable();

            // Verification tracking
            $table->boolean('verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            // Indexes for performance
            // NOTE: SKU indexes added by 2025_10_17_000001_add_sku_columns_to_vehicle_compatibility.php
            $table->index(['product_id', 'vehicle_model_id'], 'idx_compat_product_vehicle');
            $table->index('compatibility_attribute_id', 'idx_compat_attr');
            $table->index('verified', 'idx_compat_verified');

            // Unique constraint: ONE compatibility per product-vehicle pair
            $table->unique(['product_id', 'vehicle_model_id'], 'uniq_compat_product_vehicle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_compatibility');
    }
};
