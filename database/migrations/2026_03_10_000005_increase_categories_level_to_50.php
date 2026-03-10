<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FIX 2026-03-10: Increase categories.level CHECK constraint from 20 to 50
 *
 * Problem: PrestaShop multi-store category trees can exceed 20 levels
 * when mapped to PPM (which adds 2 extra levels: Baza + Wszystko).
 * Real-world B2B Test DEV shop had categories at depth >20.
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE categories DROP CONSTRAINT chk_max_level');
        } catch (\Exception $e) {
            // Constraint may not exist
        }

        DB::statement('ALTER TABLE categories ADD CONSTRAINT chk_max_level CHECK (level >= 0 AND level <= 50)');
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE categories DROP CONSTRAINT chk_max_level');
        } catch (\Exception $e) {
            // Constraint may not exist
        }

        DB::statement('ALTER TABLE categories ADD CONSTRAINT chk_max_level CHECK (level >= 0 AND level <= 20)');
    }
};
