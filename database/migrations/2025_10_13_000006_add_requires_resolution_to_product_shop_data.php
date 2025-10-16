<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ADD requires_resolution FLAG TO product_shop_data
     *
     * PROBLEM (2025-10-13): Two-phase conflict resolution wymagaflagi
     * aby oznaczyć produkty wymagające user decyzji.
     *
     * UŻYCIE:
     * - Import wykrywa konflikt kategorii → requires_resolution = true
     * - User rozwiązuje w CategoryConflictModal → requires_resolution = false
     * - Frontend może filtrować produkty wymagające uwagi
     *
     * ARCHITECTURE:
     * - Boolean flag: requires_resolution (default false)
     * - Index dla szybkiego query na pending conflicts
     *
     * @package App\Database\Migrations
     * @version 1.0
     * @since 2025-10-13 - Two-Phase Conflict Resolution
     */
    public function up(): void
    {
        Log::info('Migration START: Adding requires_resolution flag to product_shop_data');

        try {
            Schema::table('product_shop_data', function (Blueprint $table) {
                if (!Schema::hasColumn('product_shop_data', 'requires_resolution')) {
                    $table->boolean('requires_resolution')
                        ->default(false)
                        ->after('conflict_detected_at')
                        ->comment('TRUE = conflict awaiting user decision, FALSE = resolved or no conflict');

                    Log::info('Column added: requires_resolution');
                }
            });

            // Add index for quick filtering of pending conflicts
            $existingIndexes = collect(\DB::select("SHOW INDEX FROM product_shop_data"))
                ->pluck('Key_name')
                ->unique()
                ->toArray();

            Schema::table('product_shop_data', function (Blueprint $table) use ($existingIndexes) {
                if (!in_array('idx_requires_resolution', $existingIndexes)) {
                    $table->index(['requires_resolution'], 'idx_requires_resolution');
                    Log::info('Index added: idx_requires_resolution');
                }
            });

            Log::info('Migration COMPLETE: requires_resolution flag added successfully');

        } catch (\Exception $e) {
            Log::error('Migration FAILED: Error adding requires_resolution flag', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('Migration ROLLBACK START: Dropping requires_resolution flag');

        try {
            Schema::table('product_shop_data', function (Blueprint $table) {
                // Drop index first
                $table->dropIndex('idx_requires_resolution');
                Log::info('Index dropped: idx_requires_resolution');

                // Drop column
                $table->dropColumn('requires_resolution');
                Log::info('Column dropped: requires_resolution');
            });

            Log::info('Migration ROLLBACK COMPLETE');

        } catch (\Exception $e) {
            Log::error('Migration ROLLBACK FAILED: Error dropping requires_resolution flag', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
};
