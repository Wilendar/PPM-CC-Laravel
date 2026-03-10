<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FIX 2026-03-10: Revert categories.level CHECK constraint from 50 back to 20
 *
 * Root cause: PS B2B Test DEV has 122 duplicate "Wszystko" categories
 * creating fake depth >30. This is data corruption, not a valid deep tree.
 * MAX_LEVEL=20 was correct - it properly rejected corrupted data.
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

        DB::statement('ALTER TABLE categories ADD CONSTRAINT chk_max_level CHECK (level >= 0 AND level <= 20)');
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE categories DROP CONSTRAINT chk_max_level');
        } catch (\Exception $e) {
            // Constraint may not exist
        }

        DB::statement('ALTER TABLE categories ADD CONSTRAINT chk_max_level CHECK (level >= 0 AND level <= 50)');
    }
};
