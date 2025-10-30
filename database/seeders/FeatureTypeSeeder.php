<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * FeatureTypeSeeder
 *
 * Seeds feature_types table with default product feature types.
 *
 * ETAP_05a FAZA 1 - Seeder 2/5
 *
 * PURPOSE:
 * - Provide initial set of feature types for product specification
 * - Enable immediate product feature assignment after migration
 *
 * FEATURE TYPES:
 * - Engine Type: select (Diesel, Petrol, Electric, Hybrid)
 * - Power: number with unit kW
 * - Weight: number with unit kg
 * - Length: number with unit mm
 * - Width: number with unit mm
 * - Height: number with unit mm
 * - Diameter: number with unit mm
 * - Thread Size: text
 * - Waterproof: bool
 * - Warranty Period: number with unit months
 */
class FeatureTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $featureTypes = [
            [
                'name' => 'Engine Type',
                'code' => 'engine_type',
                'value_type' => 'select',
                'unit' => null,
                'position' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Power',
                'code' => 'power',
                'value_type' => 'number',
                'unit' => 'kW',
                'position' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Weight',
                'code' => 'weight',
                'value_type' => 'number',
                'unit' => 'kg',
                'position' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Length',
                'code' => 'length',
                'value_type' => 'number',
                'unit' => 'mm',
                'position' => 4,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Width',
                'code' => 'width',
                'value_type' => 'number',
                'unit' => 'mm',
                'position' => 5,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Height',
                'code' => 'height',
                'value_type' => 'number',
                'unit' => 'mm',
                'position' => 6,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Diameter',
                'code' => 'diameter',
                'value_type' => 'number',
                'unit' => 'mm',
                'position' => 7,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Thread Size',
                'code' => 'thread_size',
                'value_type' => 'text',
                'unit' => null,
                'position' => 8,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Waterproof',
                'code' => 'waterproof',
                'value_type' => 'bool',
                'unit' => null,
                'position' => 9,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Warranty Period',
                'code' => 'warranty_period',
                'value_type' => 'number',
                'unit' => 'months',
                'position' => 10,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('feature_types')->insert($featureTypes);

        $this->command->info('✅ FeatureTypeSeeder: Seeded 10 feature types');
    }
}
