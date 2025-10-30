<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05a FAZA 1 - Migration 12/15
     *
     * Creates compatibility_sources table for tracking compatibility data sources.
     *
     * PURPOSE:
     * - Track where compatibility data comes from (Manufacturer, TecDoc, Manual Entry, etc.)
     * - Assign trust levels to different sources
     * - Enable filtering/sorting by source reliability
     *
     * TRUST LEVELS:
     * - "verified": Manufacturer official data (highest reliability)
     * - "high": TecDoc, OEM catalogs (very reliable)
     * - "medium": Third-party catalogs, distributors (generally reliable)
     * - "low": Manual entry without verification (needs review)
     *
     * EXAMPLES:
     * - name: "Manufacturer", code: "manufacturer", trust_level: "verified"
     * - name: "TecDoc", code: "tecdoc", trust_level: "high"
     * - name: "Manual Entry", code: "manual", trust_level: "medium"
     * - name: "Customer Report", code: "customer", trust_level: "low"
     *
     * RELATIONSHIPS:
     * - has many VehicleCompatibility
     */
    public function up(): void
    {
        Schema::create('compatibility_sources', function (Blueprint $table) {
            $table->id();

            // Source definition
            $table->string('name', 100); // "Manufacturer", "TecDoc", "Manual Entry"
            $table->string('code', 50)->unique(); // "manufacturer", "tecdoc", "manual"

            // Trust level
            $table->enum('trust_level', ['low', 'medium', 'high', 'verified'])
                  ->default('medium');

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes for performance
            $table->index('code', 'idx_compat_source_code');
            $table->index('trust_level', 'idx_compat_source_trust');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compatibility_sources');
    }
};
