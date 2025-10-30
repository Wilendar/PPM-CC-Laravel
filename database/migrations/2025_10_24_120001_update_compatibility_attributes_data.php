<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05d CONDITION 1 - Data Migration
     *
     * Updates existing compatibility_attributes records from English to Polish names.
     *
     * PURPOSE:
     * - Migrate "Original" → "Oryginał" with correct color (#10b981)
     * - Migrate "Replacement" → "Zamiennik" with correct color (#f59e0b)
     * - Remove "Performance" attribute (not used in ETAP_05d)
     * - Add "Model" attribute (auto-generated, is_auto_generated=true)
     *
     * BUSINESS RULES:
     * - Preserve existing compatibility_attribute_id references (foreign keys)
     * - code columns remain same ('original', 'replacement', 'model')
     *
     * RELATED:
     * - ETAP_05d SEKCJA 0.2: PrestaShop ps_feature* mapping
     * - 2025_10_24_120000_add_is_auto_generated_to_compatibility_attributes.php
     * - CompatibilityAttributeSeeder.php (updated version)
     */
    public function up(): void
    {
        $now = Carbon::now();

        // Update "Original" → "Oryginał"
        DB::table('compatibility_attributes')
            ->where('code', 'original')
            ->update([
                'name' => 'Oryginał',
                'color' => '#10b981', // green - original fit
                'is_auto_generated' => false,
                'updated_at' => $now,
            ]);

        // Update "Replacement" → "Zamiennik"
        DB::table('compatibility_attributes')
            ->where('code', 'replacement')
            ->update([
                'name' => 'Zamiennik',
                'color' => '#f59e0b', // orange - replacement
                'is_auto_generated' => false,
                'updated_at' => $now,
            ]);

        // Delete "Performance" (not used in ETAP_05d)
        DB::table('compatibility_attributes')
            ->where('code', 'performance')
            ->delete();

        // Insert "Model" (auto-generated)
        // Check if exists first (in case migration runs multiple times)
        $modelExists = DB::table('compatibility_attributes')
            ->where('code', 'model')
            ->exists();

        if (!$modelExists) {
            DB::table('compatibility_attributes')->insert([
                'name' => 'Model',
                'code' => 'model',
                'color' => '#3b82f6', // blue - auto-generated sum
                'position' => 3,
                'is_active' => true,
                'is_auto_generated' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $now = Carbon::now();

        // Revert "Oryginał" → "Original"
        DB::table('compatibility_attributes')
            ->where('code', 'original')
            ->update([
                'name' => 'Original',
                'color' => '#4ade80',
                'is_auto_generated' => false,
                'updated_at' => $now,
            ]);

        // Revert "Zamiennik" → "Replacement"
        DB::table('compatibility_attributes')
            ->where('code', 'replacement')
            ->update([
                'name' => 'Replacement',
                'color' => '#3b82f6',
                'is_auto_generated' => false,
                'updated_at' => $now,
            ]);

        // Restore "Performance"
        $performanceExists = DB::table('compatibility_attributes')
            ->where('code', 'performance')
            ->exists();

        if (!$performanceExists) {
            DB::table('compatibility_attributes')->insert([
                'name' => 'Performance',
                'code' => 'performance',
                'color' => '#f59e0b',
                'position' => 3,
                'is_active' => true,
                'is_auto_generated' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Delete "Model"
        DB::table('compatibility_attributes')
            ->where('code', 'model')
            ->delete();
    }
};
