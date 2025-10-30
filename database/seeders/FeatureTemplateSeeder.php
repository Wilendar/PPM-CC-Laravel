<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * FeatureTemplateSeeder
 *
 * Seeds feature_templates table with predefined vehicle feature templates.
 *
 * ETAP_05a FAZA 2 - Feature Templates Seeder
 *
 * PURPOSE:
 * - Provide predefined templates for common vehicle types
 * - Enable immediate bulk feature assignment after migration
 * - Simplify feature management with reusable template sets
 *
 * PREDEFINED TEMPLATES:
 * - ID 1: Pojazdy Elektryczne (VIN, Rok, Engine No., Przebieg, Typ silnika, Moc)
 * - ID 2: Pojazdy Spalinowe (+ Pojemność, Cylindry)
 *
 * TEMPLATE STRUCTURE:
 * Each template contains:
 * - name: Template display name
 * - description: Optional description
 * - features: JSON array of feature definitions
 *   [
 *     {"name": "VIN", "type": "text", "required": true, "default": ""},
 *     ...
 *   ]
 * - is_predefined: true for default templates (cannot be deleted)
 * - is_active: true by default
 */
class FeatureTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $templates = [
            [
                'id' => 1,
                'name' => 'Pojazdy Elektryczne',
                'description' => 'Szablon cech dla pojazdów elektrycznych (motocykle, skutery, rowery)',
                'features' => json_encode([
                    ['name' => 'VIN', 'type' => 'text', 'required' => true, 'default' => ''],
                    ['name' => 'Rok produkcji', 'type' => 'number', 'required' => true, 'default' => '2024'],
                    ['name' => 'Engine No.', 'type' => 'text', 'required' => false, 'default' => ''],
                    ['name' => 'Przebieg', 'type' => 'number', 'required' => false, 'default' => '0'],
                    ['name' => 'Typ silnika', 'type' => 'select', 'required' => true, 'default' => 'Elektryczny'],
                    ['name' => 'Moc (KM)', 'type' => 'number', 'required' => false, 'default' => ''],
                ]),
                'is_predefined' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Pojazdy Spalinowe',
                'description' => 'Szablon cech dla pojazdów spalinowych (motocykle, quady, pojazdy terenowe)',
                'features' => json_encode([
                    ['name' => 'VIN', 'type' => 'text', 'required' => true, 'default' => ''],
                    ['name' => 'Rok produkcji', 'type' => 'number', 'required' => true, 'default' => '2024'],
                    ['name' => 'Engine No.', 'type' => 'text', 'required' => true, 'default' => ''],
                    ['name' => 'Przebieg', 'type' => 'number', 'required' => false, 'default' => '0'],
                    ['name' => 'Typ silnika', 'type' => 'select', 'required' => true, 'default' => 'Spalinowy'],
                    ['name' => 'Moc (KM)', 'type' => 'number', 'required' => false, 'default' => ''],
                    ['name' => 'Pojemnosc (cm3)', 'type' => 'number', 'required' => true, 'default' => ''],
                    ['name' => 'Liczba cylindrow', 'type' => 'number', 'required' => false, 'default' => ''],
                ]),
                'is_predefined' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('feature_templates')->insert($templates);

        $this->command->info('✅ FeatureTemplateSeeder: Seeded 2 predefined templates (Electric, Combustion)');
    }
}
