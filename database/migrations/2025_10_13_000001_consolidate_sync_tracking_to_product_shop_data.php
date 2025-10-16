<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * KONSOLIDACJA: product_sync_status → product_shop_data
     *
     * PROBLEM (2025-10-13): Duplikacja kolumn sync między dwiema tabelami:
     * - product_shop_data (FAZA 1.5, wrzesień 2025) - content overrides + basic sync
     * - product_sync_status (ETAP_07, październik 2025) - advanced sync tracking
     *
     * ROZWIĄZANIE: Przeniesienie wszystkich kolumn sync tracking do product_shop_data
     * i usunięcie product_sync_status (zostanie usunięta w osobnej migracji po refactoringu kodu).
     *
     * CEL: Single source of truth dla danych per sklep (content + sync tracking).
     *
     * CHANGES:
     * 1. DROP external_id (VARCHAR) - zastąpione przez prestashop_product_id (BIGINT)
     * 2. ADD prestashop_product_id (BIGINT UNSIGNED) - PrestaShop product ID
     * 3. ADD last_success_sync_at (TIMESTAMP) - ostatnia udana synchronizacja
     * 4. ADD sync_direction (ENUM) - kierunek synchronizacji
     * 5. ADD retry_count (TINYINT UNSIGNED) - liczba prób
     * 6. ADD max_retries (TINYINT UNSIGNED) - limit prób
     * 7. ADD priority (TINYINT UNSIGNED) - priorytet sync (1-10)
     * 8. ADD checksum (VARCHAR 64) - MD5 hash dla change detection
     * 9. RENAME sync_errors (JSON) → error_message (TEXT) - komunikat błędu
     * 10. ADD new indexes dla sync performance
     *
     * DATA MIGRATION:
     * - Migrate data from product_sync_status → product_shop_data (10 records)
     * - external_id (VARCHAR) będzie migrowane do prestashop_product_id (BIGINT)
     *
     * @package App\Database\Migrations
     * @version 1.0
     * @since 2025-10-13 - Table Consolidation
     */
    public function up(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            // STEP 1: Drop old external_id column (VARCHAR) - only if exists
            if (Schema::hasColumn('product_shop_data', 'external_id')) {
                $table->dropColumn('external_id');
            }

            // STEP 2: Add prestashop_product_id (BIGINT UNSIGNED) - główny klucz do PrestaShop
            if (!Schema::hasColumn('product_shop_data', 'prestashop_product_id')) {
                $table->unsignedBigInteger('prestashop_product_id')
                    ->nullable()
                    ->after('shop_id')
                    ->comment('PrestaShop product ID (integer) - migrated from external_id');
            }

            // STEP 3: Add last_success_sync_at - dodatkowy timestamp dla success tracking
            if (!Schema::hasColumn('product_shop_data', 'last_success_sync_at')) {
                $table->timestamp('last_success_sync_at')
                    ->nullable()
                    ->after('last_sync_at')
                    ->comment('Ostatnia udana synchronizacja (success only)');
            }

            // STEP 4: Add sync_direction - kierunek synchronizacji
            if (!Schema::hasColumn('product_shop_data', 'sync_direction')) {
                $table->enum('sync_direction', ['ppm_to_ps', 'ps_to_ppm', 'bidirectional'])
                    ->default('ppm_to_ps')
                    ->after('sync_status')
                    ->comment('Kierunek synchronizacji: PPM→PrestaShop, PrestaShop→PPM, lub dwukierunkowa');
            }

            // STEP 5: Add retry mechanism columns
            if (!Schema::hasColumn('product_shop_data', 'retry_count')) {
                $table->unsignedTinyInteger('retry_count')
                    ->default(0)
                    ->after('conflict_detected_at')
                    ->comment('Liczba prób ponowienia synchronizacji');
            }

            if (!Schema::hasColumn('product_shop_data', 'max_retries')) {
                $table->unsignedTinyInteger('max_retries')
                    ->default(3)
                    ->after('retry_count')
                    ->comment('Maksymalna liczba prób synchronizacji');
            }

            // STEP 6: Add priority system
            if (!Schema::hasColumn('product_shop_data', 'priority')) {
                $table->unsignedTinyInteger('priority')
                    ->default(5)
                    ->after('max_retries')
                    ->comment('Priorytet synchronizacji (1=najwyższy, 10=najniższy)');
            }

            // STEP 7: Add checksum dla change detection (oprócz last_sync_hash)
            if (!Schema::hasColumn('product_shop_data', 'checksum')) {
                $table->string('checksum', 64)
                    ->nullable()
                    ->after('last_sync_hash')
                    ->comment('MD5 hash danych produktu dla wykrywania zmian (advanced detection)');
            }

            // STEP 8: Rename sync_errors (JSON) → error_message (TEXT) dla consistency
            // Note: Laravel nie wspiera bezpośredniego rename z change typu, więc:
            // 1. Tworzymy nową kolumnę error_message
            // 2. Skopiujemy dane w metodzie up() via DB query
            // 3. Usuniemy starą kolumnę sync_errors
            if (!Schema::hasColumn('product_shop_data', 'error_message')) {
                $table->text('error_message')
                    ->nullable()
                    ->after('sync_direction')
                    ->comment('Komunikat błędu synchronizacji (TEXT format)');
            }
        });

        // STEP 9: Migrate sync_errors (JSON) → error_message (TEXT) - only if sync_errors exists
        if (Schema::hasColumn('product_shop_data', 'sync_errors')) {
            DB::table('product_shop_data')
                ->whereNotNull('sync_errors')
                ->orderBy('id')
                ->chunk(100, function ($records) {
                    foreach ($records as $record) {
                        $syncErrors = json_decode($record->sync_errors, true);
                        $errorMessage = null;

                        if (is_array($syncErrors) && !empty($syncErrors)) {
                            // If array, take first error message
                            $errorMessage = is_array($syncErrors[0]) && isset($syncErrors[0]['message'])
                                ? $syncErrors[0]['message']
                                : (is_string($syncErrors[0]) ? $syncErrors[0] : json_encode($syncErrors[0]));
                        } elseif (is_string($syncErrors)) {
                            $errorMessage = $syncErrors;
                        }

                        if ($errorMessage) {
                            DB::table('product_shop_data')
                                ->where('id', $record->id)
                                ->update(['error_message' => $errorMessage]);
                        }
                    }
                });

            // STEP 10: Drop old sync_errors column
            Schema::table('product_shop_data', function (Blueprint $table) {
                $table->dropColumn('sync_errors');
            });
        }

        // STEP 11: Migrate data from product_sync_status → product_shop_data
        $this->migrateDataFromSyncStatusTable();

        // STEP 12: Add strategic indexes dla sync performance
        // Check if indexes already exist using raw SQL (Laravel 11 compatible)
        $existingIndexes = collect(DB::select("SHOW INDEX FROM product_shop_data"))
            ->pluck('Key_name')
            ->unique()
            ->toArray();

        Schema::table('product_shop_data', function (Blueprint $table) use ($existingIndexes) {
            // Index na prestashop_product_id dla reverse lookups (PrestaShop → PPM)
            if (!in_array('idx_ps_product_id', $existingIndexes)) {
                $table->index(['prestashop_product_id'], 'idx_ps_product_id');
            }

            // Index na retry mechanism dla failed sync recovery
            if (!in_array('idx_retry_status', $existingIndexes)) {
                $table->index(['retry_count', 'max_retries'], 'idx_retry_status');
            }

            // Index na priority + sync_status dla priority queue processing
            if (!in_array('idx_priority_status', $existingIndexes)) {
                $table->index(['priority', 'sync_status'], 'idx_priority_status');
            }

            // Index na sync_direction dla directional queries
            if (!in_array('idx_sync_direction', $existingIndexes)) {
                $table->index(['sync_direction'], 'idx_sync_direction');
            }
        });
    }

    /**
     * Migrate data from product_sync_status → product_shop_data
     *
     * Only 10 records to migrate (checked 2025-10-13)
     */
    private function migrateDataFromSyncStatusTable(): void
    {
        // Check if product_sync_status table exists
        if (!Schema::hasTable('product_sync_status')) {
            return;
        }

        DB::table('product_sync_status')->orderBy('id')->chunk(100, function ($syncRecords) {
            foreach ($syncRecords as $sync) {
                // Find matching product_shop_data record
                $shopData = DB::table('product_shop_data')
                    ->where('product_id', $sync->product_id)
                    ->where('shop_id', $sync->shop_id)
                    ->first();

                if ($shopData) {
                    // Update existing record with sync tracking data
                    DB::table('product_shop_data')
                        ->where('id', $shopData->id)
                        ->update([
                            'prestashop_product_id' => $sync->prestashop_product_id,
                            'sync_status' => $sync->sync_status,
                            'last_sync_at' => $sync->last_sync_at,
                            'last_success_sync_at' => $sync->last_success_sync_at,
                            'sync_direction' => $sync->sync_direction,
                            'error_message' => $sync->error_message,
                            'conflict_data' => $sync->conflict_data,
                            'retry_count' => $sync->retry_count,
                            'max_retries' => $sync->max_retries,
                            'priority' => $sync->priority,
                            'checksum' => $sync->checksum,
                        ]);
                } else {
                    // This shouldn't happen, but log warning if orphaned sync_status record
                    \Log::warning('Orphaned product_sync_status record found during migration', [
                        'sync_status_id' => $sync->id,
                        'product_id' => $sync->product_id,
                        'shop_id' => $sync->shop_id,
                    ]);
                }
            }
        });

        \Log::info('Data migration from product_sync_status → product_shop_data completed');
    }

    /**
     * Reverse the migrations.
     *
     * NOTE: Reverting this migration is COMPLEX because:
     * 1. We dropped external_id column (data lost)
     * 2. We renamed sync_errors to error_message (structure changed)
     * 3. We merged data from two tables
     *
     * RECOMMENDATION: Create database backup BEFORE running this migration!
     */
    public function down(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            // STEP 1: Drop new indexes
            $table->dropIndex('idx_ps_product_id');
            $table->dropIndex('idx_retry_status');
            $table->dropIndex('idx_priority_status');
            $table->dropIndex('idx_sync_direction');

            // STEP 2: Drop new columns (reverse order)
            $table->dropColumn([
                'checksum',
                'priority',
                'max_retries',
                'retry_count',
                'sync_direction',
                'error_message',
                'last_success_sync_at',
                'prestashop_product_id',
            ]);

            // STEP 3: Re-add old columns (data will be lost!)
            $table->string('external_id', 100)
                ->nullable()
                ->comment('ID produktu w systemie PrestaShop (VARCHAR - old format)');

            $table->json('sync_errors')
                ->nullable()
                ->comment('Szczegóły błędów synchronizacji (JSON)');
        });

        // WARNING: Data from product_sync_status CANNOT be restored automatically!
        \Log::warning('Migration rollback: Data from product_sync_status table was NOT restored!');
    }
};
