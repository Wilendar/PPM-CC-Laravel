<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * CompatibilitySourceSeeder
 *
 * Seeds compatibility_sources table with default data sources.
 *
 * ETAP_05a FAZA 1 - Seeder 4/5
 *
 * PURPOSE:
 * - Provide initial set of compatibility sources for tracking data origin
 * - Enable immediate source assignment after migration
 *
 * COMPATIBILITY SOURCES:
 * - Manufacturer: Verified trust level (highest reliability - OEM data)
 * - TecDoc: High trust level (industry standard catalog)
 * - Manual Entry: Medium trust level (needs verification)
 */
class CompatibilitySourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $compatibilitySources = [
            [
                'name' => 'Manufacturer',
                'code' => 'manufacturer',
                'trust_level' => 'verified',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'TecDoc',
                'code' => 'tecdoc',
                'trust_level' => 'high',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Manual Entry',
                'code' => 'manual',
                'trust_level' => 'medium',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('compatibility_sources')->insert($compatibilitySources);

        $this->command->info('✅ CompatibilitySourceSeeder: Seeded 3 compatibility sources');
    }
}
