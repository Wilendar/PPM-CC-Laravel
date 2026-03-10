<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Remove JSON CHECK constraints from category_preview
 *
 * Problem: MariaDB 10.11 automatically adds json_valid() CHECK constraints
 * when Laravel migrations use $table->json(). These constraints can reject
 * valid JSON with Polish characters or large structures (>100KB).
 *
 * Fix: Change columns from JSON to LONGTEXT (Eloquent $casts handles
 * serialization/deserialization). Already applied manually on production.
 * This migration ensures consistency.
 *
 * @package PPM-CC-Laravel
 * @since 2026-03-10
 */
return new class extends Migration
{
    public function up(): void
    {
        // MariaDB-specific: Remove CHECK constraints by changing column type
        // from JSON (which auto-adds json_valid() CHECK) to LONGTEXT
        $columns = [
            'category_tree_json' => 'LONGTEXT NOT NULL',
            'import_context_json' => 'LONGTEXT DEFAULT NULL',
            'user_selection_json' => 'LONGTEXT DEFAULT NULL',
        ];

        foreach ($columns as $column => $definition) {
            try {
                DB::statement("ALTER TABLE category_preview MODIFY COLUMN {$column} {$definition}");
                Log::info("Removed JSON CHECK constraint from category_preview.{$column}");
            } catch (\Exception $e) {
                // Column may already be LONGTEXT (applied manually)
                Log::warning("Could not modify category_preview.{$column}: " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        // Restore JSON type (will re-add CHECK constraints)
        $columns = [
            'category_tree_json' => 'JSON NOT NULL',
            'import_context_json' => 'JSON DEFAULT NULL',
            'user_selection_json' => 'JSON DEFAULT NULL',
        ];

        foreach ($columns as $column => $definition) {
            try {
                DB::statement("ALTER TABLE category_preview MODIFY COLUMN {$column} {$definition}");
            } catch (\Exception $e) {
                Log::warning("Could not restore JSON type for category_preview.{$column}: " . $e->getMessage());
            }
        }
    }
};
