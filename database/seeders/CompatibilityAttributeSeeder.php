<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * CompatibilityAttributeSeeder
 *
 * Seeds compatibility_attributes table with default compatibility types.
 *
 * ETAP_05d CONDITION 1 - Seeder Update (Polish names)
 * Previous: ETAP_05a FAZA 1 - Seeder 3/5
 *
 * PURPOSE:
 * - Provide initial set of compatibility attributes for product-vehicle matching
 * - Enable immediate compatibility assignment after migration
 * - Support three-tier system: Oryginał, Zamiennik, Model (auto-generated)
 *
 * COMPATIBILITY ATTRIBUTES (Updated 2025-10-24):
 * - Oryginał: Green badge (#10b981) - OEM parts (original fit)
 * - Zamiennik: Orange badge (#f59e0b) - Aftermarket equivalent parts (replacement)
 * - Model: Blue badge (#3b82f6) - Auto-generated sum of Oryginał + Zamiennik (read-only)
 *
 * CHANGES FROM PREVIOUS VERSION:
 * - Names: English → Polish (Original → Oryginał, Replacement → Zamiennik)
 * - Removed: Performance attribute (not used in ETAP_05d)
 * - Added: Model attribute (auto-generated, is_auto_generated=true)
 * - Colors: Updated per ETAP_05d plan (#10b981 green, #f59e0b orange, #3b82f6 blue)
 */
class CompatibilityAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $compatibilityAttributes = [
            [
                'name' => 'Oryginał',
                'code' => 'original',
                'color' => '#10b981', // green - original fit (OEM parts)
                'position' => 1,
                'is_active' => true,
                'is_auto_generated' => false, // Manually assignable
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Zamiennik',
                'code' => 'replacement',
                'color' => '#f59e0b', // orange - replacement (aftermarket parts)
                'position' => 2,
                'is_active' => true,
                'is_auto_generated' => false, // Manually assignable
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Model',
                'code' => 'model',
                'color' => '#3b82f6', // blue - auto-generated sum (Oryginał + Zamiennik)
                'position' => 3,
                'is_active' => true,
                'is_auto_generated' => true, // Computed, read-only
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('compatibility_attributes')->insert($compatibilityAttributes);

        $this->command->info('✅ CompatibilityAttributeSeeder: Seeded 3 compatibility attributes (Oryginał, Zamiennik, Model)');
    }
}
