<?php

namespace Database\Seeders;

use App\Models\AttributeType;
use Illuminate\Database\Seeder;

/**
 * AttributeTypeSeeder
 *
 * Seeds attribute_types table with default variant attribute types.
 *
 * ETAP_05b FAZA 2 - Updated for production-safe seeding
 *
 * PURPOSE:
 * - Provide initial set of attribute types for variant creation
 * - Enable immediate variant attribute assignment after migration
 * - Production-safe (uses updateOrCreate)
 *
 * ATTRIBUTE TYPES:
 * - Color: color picker (Red, Blue, Black, etc.)
 * - Size: dropdown (XS, S, M, L, XL, XXL)
 * - Material: dropdown (Steel, Aluminum, Carbon, etc.)
 */
class AttributeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Kolor',
                'code' => 'color',
                'display_type' => 'color',
                'position' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Rozmiar',
                'code' => 'size',
                'display_type' => 'dropdown',
                'position' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Materiał',
                'code' => 'material',
                'display_type' => 'dropdown',
                'position' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            AttributeType::updateOrCreate(
                ['code' => $type['code']], // Match by code
                $type
            );
        }

        $this->command->info('✅ AttributeTypes seeded successfully! (3 types)');
    }
}
