<?php

namespace Database\Seeders;

use App\Models\AttributeType;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;

/**
 * AttributeValueSeeder
 *
 * Seeds attribute_values table with predefined values for each attribute type.
 *
 * ETAP_05b FAZA 2 - Database-backed attribute values
 *
 * PURPOSE:
 * - Provide initial set of attribute values for variant creation
 * - Enable dynamic CRUD for attribute values
 * - Production-safe (uses code matching, NOT hardcoded IDs)
 *
 * ATTRIBUTE VALUES:
 * - Color: Czerwony, Niebieski, Zielony, Czarny, Biały
 * - Size: XS, S, M, L, XL
 * - Material: Bawełna, Poliester, Skóra
 */
class AttributeValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get AttributeTypes by code (production-safe)
        $colorType = AttributeType::where('code', 'color')->first();
        $sizeType = AttributeType::where('code', 'size')->first();
        $materialType = AttributeType::where('code', 'material')->first();

        if (!$colorType || !$sizeType || !$materialType) {
            $this->command->error('❌ AttributeTypes not found! Run AttributeTypeSeeder first.');
            return;
        }

        // Color values
        $colorValues = [
            ['code' => 'red', 'label' => 'Czerwony', 'color_hex' => '#EF4444', 'position' => 1],
            ['code' => 'blue', 'label' => 'Niebieski', 'color_hex' => '#3B82F6', 'position' => 2],
            ['code' => 'green', 'label' => 'Zielony', 'color_hex' => '#10B981', 'position' => 3],
            ['code' => 'black', 'label' => 'Czarny', 'color_hex' => '#000000', 'position' => 4],
            ['code' => 'white', 'label' => 'Biały', 'color_hex' => '#FFFFFF', 'position' => 5],
        ];

        foreach ($colorValues as $value) {
            AttributeValue::updateOrCreate(
                ['attribute_type_id' => $colorType->id, 'code' => $value['code']],
                array_merge($value, ['attribute_type_id' => $colorType->id, 'is_active' => true])
            );
        }

        // Size values
        $sizeValues = [
            ['code' => 'xs', 'label' => 'XS', 'position' => 1],
            ['code' => 's', 'label' => 'S', 'position' => 2],
            ['code' => 'm', 'label' => 'M', 'position' => 3],
            ['code' => 'l', 'label' => 'L', 'position' => 4],
            ['code' => 'xl', 'label' => 'XL', 'position' => 5],
        ];

        foreach ($sizeValues as $value) {
            AttributeValue::updateOrCreate(
                ['attribute_type_id' => $sizeType->id, 'code' => $value['code']],
                array_merge($value, ['attribute_type_id' => $sizeType->id, 'is_active' => true])
            );
        }

        // Material values
        $materialValues = [
            ['code' => 'cotton', 'label' => 'Bawełna', 'position' => 1],
            ['code' => 'polyester', 'label' => 'Poliester', 'position' => 2],
            ['code' => 'leather', 'label' => 'Skóra', 'position' => 3],
        ];

        foreach ($materialValues as $value) {
            AttributeValue::updateOrCreate(
                ['attribute_type_id' => $materialType->id, 'code' => $value['code']],
                array_merge($value, ['attribute_type_id' => $materialType->id, 'is_active' => true])
            );
        }

        $this->command->info('✅ AttributeValues seeded successfully!');
        $this->command->info('   - Color: ' . count($colorValues) . ' values');
        $this->command->info('   - Size: ' . count($sizeValues) . ' values');
        $this->command->info('   - Material: ' . count($materialValues) . ' values');
    }
}
