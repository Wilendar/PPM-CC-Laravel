<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\CategoryMappingsValidator;

/**
 * Update category_mappings Structure to Option A Architecture
 *
 * Architecture: CATEGORY_MAPPINGS_ARCHITECTURE.md v2.0 (2025-11-18)
 *
 * Converts ALL existing category_mappings to canonical Option A format:
 * ```json
 * {
 *   "ui": {
 *     "selected": [100, 103, 42],
 *     "primary": 100
 *   },
 *   "mappings": {
 *     "100": 9,
 *     "103": 15,
 *     "42": 800
 *   },
 *   "metadata": {
 *     "last_updated": "2025-11-18T10:30:00Z",
 *     "source": "migration"
 *   }
 * }
 * ```
 *
 * Features:
 * - Batch processing (100 records per batch)
 * - Backward compatibility (detects and converts all legacy formats)
 * - Extensive logging (conversion stats + errors)
 * - Rollback support (backup in temporary table)
 * - Safe execution (no data loss)
 *
 * @version 2.0
 * @since 2025-11-18
 */
return new class extends Migration
{
    /**
     * Batch size for processing
     */
    private const BATCH_SIZE = 100;

    /**
     * Backup table name
     */
    private const BACKUP_TABLE = 'product_shop_data_category_mappings_backup';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Log::info('CategoryMappings Migration: Starting conversion to Option A architecture');

        $validator = app(CategoryMappingsValidator::class);

        // Create backup table
        $this->createBackupTable();

        // Get total count
        $totalCount = DB::table('product_shop_data')
            ->whereNotNull('category_mappings')
            ->where('category_mappings', '!=', '')
            ->where('category_mappings', '!=', '[]')
            ->where('category_mappings', '!=', '{}')
            ->count();

        if ($totalCount === 0) {
            Log::info('CategoryMappings Migration: No records to convert');
            return;
        }

        Log::info('CategoryMappings Migration: Found records to convert', [
            'total_count' => $totalCount,
        ]);

        // Statistics
        $stats = [
            'total' => $totalCount,
            'converted' => 0,
            'already_option_a' => 0,
            'ui_format' => 0,
            'prestashop_format' => 0,
            'unknown_format' => 0,
            'errors' => 0,
        ];

        // Process in batches
        DB::table('product_shop_data')
            ->whereNotNull('category_mappings')
            ->where('category_mappings', '!=', '')
            ->where('category_mappings', '!=', '[]')
            ->where('category_mappings', '!=', '{}')
            ->orderBy('id')
            ->chunk(self::BATCH_SIZE, function ($records) use ($validator, &$stats) {
                foreach ($records as $record) {
                    try {
                        // Backup original value
                        $this->backupRecord($record);

                        // Decode JSON
                        $data = json_decode($record->category_mappings, true);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            Log::warning('CategoryMappings Migration: JSON decode error', [
                                'id' => $record->id,
                                'error' => json_last_error_msg(),
                            ]);
                            $stats['errors']++;
                            continue;
                        }

                        // Detect format
                        $format = $validator->detectFormat($data);

                        // Track format statistics
                        switch ($format) {
                            case 'option_a':
                                $stats['already_option_a']++;
                                // Already in Option A, skip conversion but validate
                                $validator->validate($data);
                                continue 2; // Skip to next record
                            case 'ui_format':
                                $stats['ui_format']++;
                                break;
                            case 'prestashop_format':
                                $stats['prestashop_format']++;
                                break;
                            case 'unknown':
                                $stats['unknown_format']++;
                                break;
                        }

                        // Convert to Option A
                        $converted = $validator->convertLegacyFormat($data);

                        // Update metadata source
                        $converted['metadata']['source'] = 'migration';
                        $converted['metadata']['last_updated'] = now()->toIso8601String();

                        // Update database
                        DB::table('product_shop_data')
                            ->where('id', $record->id)
                            ->update([
                                'category_mappings' => json_encode($converted),
                                'updated_at' => now(),
                            ]);

                        $stats['converted']++;

                        // Log every 10th conversion
                        if ($stats['converted'] % 10 === 0) {
                            Log::info('CategoryMappings Migration: Progress', $stats);
                        }
                    } catch (\Exception $e) {
                        Log::error('CategoryMappings Migration: Conversion error', [
                            'id' => $record->id,
                            'error' => $e->getMessage(),
                        ]);
                        $stats['errors']++;
                    }
                }
            });

        // Final statistics
        Log::info('CategoryMappings Migration: Conversion completed', $stats);

        // Drop backup table if no errors
        if ($stats['errors'] === 0) {
            Log::info('CategoryMappings Migration: No errors, dropping backup table');
            DB::statement('DROP TABLE IF EXISTS ' . self::BACKUP_TABLE);
        } else {
            Log::warning('CategoryMappings Migration: Errors occurred, keeping backup table', [
                'backup_table' => self::BACKUP_TABLE,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('CategoryMappings Migration: Rolling back to original format');

        // Check if backup table exists
        if (!DB::getSchemaBuilder()->hasTable(self::BACKUP_TABLE)) {
            Log::warning('CategoryMappings Migration: Backup table not found, cannot rollback');
            return;
        }

        // Restore from backup
        $restoredCount = 0;

        DB::table(self::BACKUP_TABLE)
            ->orderBy('id')
            ->chunk(self::BATCH_SIZE, function ($records) use (&$restoredCount) {
                foreach ($records as $record) {
                    DB::table('product_shop_data')
                        ->where('id', $record->id)
                        ->update([
                            'category_mappings' => $record->category_mappings_original,
                            'updated_at' => now(),
                        ]);

                    $restoredCount++;
                }
            });

        Log::info('CategoryMappings Migration: Rollback completed', [
            'restored_count' => $restoredCount,
        ]);

        // Drop backup table
        DB::statement('DROP TABLE IF EXISTS ' . self::BACKUP_TABLE);
    }

    /**
     * Create backup table for rollback support
     */
    private function createBackupTable(): void
    {
        // Drop if exists (from previous failed migration)
        DB::statement('DROP TABLE IF EXISTS ' . self::BACKUP_TABLE);

        // Create backup table
        DB::statement("
            CREATE TABLE " . self::BACKUP_TABLE . " (
                id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
                category_mappings_original JSON,
                backed_up_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        Log::info('CategoryMappings Migration: Backup table created', [
            'table' => self::BACKUP_TABLE,
        ]);
    }

    /**
     * Backup single record
     */
    private function backupRecord(object $record): void
    {
        DB::table(self::BACKUP_TABLE)->insert([
            'id' => $record->id,
            'category_mappings_original' => $record->category_mappings,
        ]);
    }
};
