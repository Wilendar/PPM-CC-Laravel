<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05a FAZA 1 - Migration 10/15
     *
     * Creates vehicle_models table for storing vehicle catalog (motorcycles, cars, etc.).
     *
     * PURPOSE:
     * - Central catalog of all vehicle models
     * - Enable product-vehicle compatibility matching
     * - Support year ranges and engine specifications
     * - SKU-first architecture for vehicle models
     *
     * BUSINESS RULES:
     * - Each vehicle model MUST have unique SKU
     * - Year range: year_from <= year_to (validation in application layer)
     * - Engine capacity in cc (cubic centimeters)
     *
     * SKU PATTERN:
     * - VEH-{BRAND}-{MODEL}-{YEAR_FROM}
     * - Examples: VEH-HONDA-CBR600RR-2013, VEH-YAMAHA-R1-2015
     *
     * EXAMPLES:
     * - sku="VEH-HONDA-CBR600RR-2013", brand="Honda", model="CBR 600", variant="RR", year_from=2013, year_to=2020
     * - sku="VEH-YAMAHA-R1-2015", brand="Yamaha", model="YZF-R1", variant=null, year_from=2015, year_to=2019
     *
     * RELATIONSHIPS:
     * - has many VehicleCompatibility
     */
    public function up(): void
    {
        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();

            // SKU-first: vehicle model ma unique SKU
            $table->string('sku', 255)->unique();

            // Vehicle identification
            $table->string('brand', 100); // "Honda", "Yamaha", "Kawasaki"
            $table->string('model', 100); // "CBR 600", "YZF-R1", "Ninja 650"
            $table->string('variant', 100)->nullable(); // "RR", "Sport", "ABS"

            // Year range
            $table->year('year_from')->nullable();
            $table->year('year_to')->nullable();

            // Engine specifications
            $table->string('engine_code', 50)->nullable();
            $table->integer('engine_capacity')->nullable(); // cc

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes for performance
            $table->index('sku', 'idx_vehicle_sku'); // SKU-first
            $table->index(['brand', 'model'], 'idx_vehicle_brand_model');
            $table->index(['year_from', 'year_to'], 'idx_vehicle_years');
            $table->index('is_active', 'idx_vehicle_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_models');
    }
};
