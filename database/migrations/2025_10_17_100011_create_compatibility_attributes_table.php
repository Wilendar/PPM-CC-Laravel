<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05a FAZA 1 - Migration 11/15
     *
     * Creates compatibility_attributes table for defining compatibility types.
     *
     * PURPOSE:
     * - Define types of product-vehicle compatibility (Original, Replacement, Performance, etc.)
     * - Support UI badges with custom colors
     * - Enable filtering by compatibility type
     *
     * BUSINESS RULES:
     * - Each compatibility attribute has unique code
     * - Color in HEX format (#4ade80) for badge styling
     *
     * EXAMPLES:
     * - name: "Original", code: "original", color: "#4ade80" (green)
     * - name: "Replacement", code: "replacement", color: "#3b82f6" (blue)
     * - name: "Performance", code: "performance", color: "#f59e0b" (amber)
     * - name: "Racing", code: "racing", color: "#ef4444" (red)
     *
     * RELATIONSHIPS:
     * - has many VehicleCompatibility
     */
    public function up(): void
    {
        Schema::create('compatibility_attributes', function (Blueprint $table) {
            $table->id();

            // Attribute definition
            $table->string('name', 100); // "Original", "Replacement", "Performance"
            $table->string('code', 50)->unique(); // "original", "replacement", "performance"

            // UI styling
            $table->string('color', 7)->nullable(); // "#4ade80" (HEX color for badges)

            // Ordering and status
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes for performance
            $table->index('code', 'idx_compat_attr_code');
            $table->index('is_active', 'idx_compat_attr_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compatibility_attributes');
    }
};
