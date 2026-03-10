<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * FIX 2026-03-10: Update CHECK constraint on categories.level
     *
     * Original migration (2024_01_01_000002) set: CHECK (level >= 0 AND level <= 4)
     * Category::MAX_LEVEL is now 20 to support deep PrestaShop category trees.
     * MariaDB 10.11 enforces CHECK constraints, so this must be updated.
     */
    public function up(): void
    {
        // MariaDB: DROP old CHECK constraint, ADD new one
        // Constraint name from original migration: chk_max_level
        try {
            DB::statement('ALTER TABLE categories DROP CONSTRAINT chk_max_level');
        } catch (\Exception $e) {
            // Constraint may not exist (already dropped or never created)
        }

        DB::statement('ALTER TABLE categories ADD CONSTRAINT chk_max_level CHECK (level >= 0 AND level <= 20)');
    }

    /**
     * Reverse the migration - restore original constraint
     */
    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE categories DROP CONSTRAINT chk_max_level');
        } catch (\Exception $e) {
            // Constraint may not exist
        }

        DB::statement('ALTER TABLE categories ADD CONSTRAINT chk_max_level CHECK (level >= 0 AND level <= 4)');
    }
};
