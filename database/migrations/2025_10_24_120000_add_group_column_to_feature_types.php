<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05c FAZA 2.1 - Add 'group' Column to feature_types
     *
     * PURPOSE:
     * - Enable feature library grouping in VehicleFeatureManagement component
     * - Replace HARDCODED feature library with dynamic database-driven library
     * - Support user-extensible groups (Podstawowe, Silnik, Wymiary, etc.)
     *
     * COLUMN SPECIFICATION:
     * - Name: 'group'
     * - Type: VARCHAR(100)
     * - Nullable: YES (existing rows don't have values yet - will be populated by separate migration)
     * - Position: AFTER 'value_type'
     * - Index: idx_feature_group for performance
     *
     * GROUPS (from architecture 09_WARIANTY_CECHY.md):
     * - Podstawowe: VIN, Rok produkcji, Engine No., Przebieg
     * - Silnik: Typ silnika, Moc (KM), Pojemnosc (cm3), Liczba cylindrow
     * - Wymiary: Dlugosc, Szerokosc, Wysokosc, Masa
     *
     * RELATED COMPONENTS:
     * - VehicleFeatureManagement (Livewire component - will use FeatureType::groupBy('group'))
     * - FeatureType model (add 'group' to $fillable)
     *
     * DEPLOYMENT:
     * 1. Run this migration (adds column + index)
     * 2. Run update migration (populates groups for existing features)
     */
    public function up(): void
    {
        Schema::table('feature_types', function (Blueprint $table) {
            // Add 'group' column AFTER 'value_type'
            $table->string('group', 100)->nullable()->after('value_type');

            // Add index for performance (queries will use groupBy('group'))
            $table->index('group', 'idx_feature_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feature_types', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex('idx_feature_group');

            // Drop column
            $table->dropColumn('group');
        });
    }
};
