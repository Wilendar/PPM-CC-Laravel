<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05a FAZA 2 - Migration for Feature Templates
     *
     * Creates feature_templates table for storing reusable vehicle feature templates.
     *
     * PURPOSE:
     * - Store predefined templates (Pojazdy Elektryczne, Pojazdy Spalinowe)
     * - Allow custom user-created templates
     * - Enable bulk assignment of features to products
     * - Simplify feature management with reusable sets
     *
     * TEMPLATE STRUCTURE:
     * - name: "Pojazdy Elektryczne", "Pojazdy Spalinowe", etc.
     * - features: JSON array of feature definitions
     *   [
     *     {"name": "VIN", "type": "text", "required": true, "default": ""},
     *     {"name": "Rok produkcji", "type": "number", "required": true, "default": "2024"},
     *     ...
     *   ]
     *
     * PREDEFINED TEMPLATES:
     * - ID 1: Pojazdy Elektryczne (VIN, Rok, Engine No., Przebieg, Typ silnika, Moc)
     * - ID 2: Pojazdy Spalinowe (VIN, Rok, Engine No., Przebieg, Typ silnika, Moc, Pojemność, Cylindry)
     *
     * RELATIONSHIPS:
     * - Independent table (used by VehicleFeatureManagement for bulk operations)
     * - Features stored as JSON (no FK to feature_types initially)
     * - Templates can be applied to products via FeatureManager service
     *
     * USAGE:
     * - VehicleFeatureManagement Livewire component
     * - Bulk assign modal (apply template to products)
     * - Template editor modal (create/edit custom templates)
     */
    public function up(): void
    {
        Schema::create('feature_templates', function (Blueprint $table) {
            $table->id();

            // Template definition
            $table->string('name', 100); // "Pojazdy Elektryczne", "Custom Template 1"
            $table->text('description')->nullable(); // Optional description

            // Features configuration (JSON)
            // Structure: [{"name": "VIN", "type": "text", "required": true, "default": ""}, ...]
            $table->json('features');

            // Template type
            $table->boolean('is_predefined')->default(false); // true for ID 1, 2

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes for performance
            $table->index('is_predefined', 'idx_template_predefined');
            $table->index('is_active', 'idx_template_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_templates');
    }
};
