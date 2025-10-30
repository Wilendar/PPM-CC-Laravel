<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05c FAZA 2.1 - Populate 'group' Column for Existing Features
     *
     * PURPOSE:
     * - Assign groups to 10 existing feature_types (seeded by FeatureTypeSeeder)
     * - Enable feature library grouping in VehicleFeatureManagement
     *
     * GROUP ASSIGNMENTS (based on architecture + current feature types):
     *
     * Silnik (Engine-related):
     * - engine_type: Engine Type (select)
     * - power: Power (number, kW)
     *
     * Wymiary (Dimensions):
     * - weight: Weight (number, kg)
     * - length: Length (number, mm)
     * - width: Width (number, mm)
     * - height: Height (number, mm)
     * - diameter: Diameter (number, mm)
     *
     * Cechy Produktu (Product Features - technical specs):
     * - thread_size: Thread Size (text)
     * - waterproof: Waterproof (bool)
     * - warranty_period: Warranty Period (number, months)
     *
     * NOTE: This migration is IDEMPOTENT - can be run multiple times safely
     *       (uses whereIn + update, not insert)
     *
     * RELATED FILES:
     * - 2025_10_24_120000_add_group_column_to_feature_types.php (adds column)
     * - database/seeders/FeatureTypeSeeder.php (creates feature types)
     */
    public function up(): void
    {
        // Define group mappings
        $groups = [
            'Silnik' => [
                'engine_type',
                'power',
            ],
            'Wymiary' => [
                'weight',
                'length',
                'width',
                'height',
                'diameter',
            ],
            'Cechy Produktu' => [
                'thread_size',
                'waterproof',
                'warranty_period',
            ],
        ];

        // Apply group assignments
        foreach ($groups as $group => $codes) {
            DB::table('feature_types')
                ->whereIn('code', $codes)
                ->update(['group' => $group]);
        }

        // Log results
        $updated = DB::table('feature_types')
            ->whereNotNull('group')
            ->count();

        // Note: Can't use $this->command in anonymous migration class
        // Log will be visible via migration output
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset all groups to NULL
        DB::table('feature_types')->update(['group' => null]);
    }
};
