<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopMapping;
use App\Models\Media;
use App\Models\PriceGroup;
use App\Models\Warehouse;
use App\Models\ImportBatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orphan Data Cleanup Service
 *
 * Detects and cleans up orphaned records that can cause FK constraint violations
 * or consume unnecessary storage.
 *
 * @package App\Services
 * @since 2025-12-15
 */
class OrphanDataCleanupService
{
    /**
     * Orphan type definitions with metadata
     */
    public const ORPHAN_TYPES = [
        'shop_mappings_categories' => [
            'label' => 'Shop Mappings (kategorie)',
            'description' => 'Mappingi wskazujace na nieistniejace kategorie PPM',
            'severity' => 'high',
            'icon' => 'folder',
        ],
        'shop_mappings_products' => [
            'label' => 'Shop Mappings (produkty)',
            'description' => 'Mappingi wskazujace na nieistniejace produkty PPM',
            'severity' => 'medium',
            'icon' => 'box',
        ],
        'media_products' => [
            'label' => 'Media (produkty)',
            'description' => 'Zdjecia przypisane do nieistniejacych produktow',
            'severity' => 'medium',
            'icon' => 'image',
        ],
        'media_variants' => [
            'label' => 'Media (warianty)',
            'description' => 'Zdjecia przypisane do nieistniejacych wariantow',
            'severity' => 'medium',
            'icon' => 'images',
        ],
        'product_categories' => [
            'label' => 'Przypisania kategorii',
            'description' => 'Produkty przypisane do nieistniejacych kategorii',
            'severity' => 'high',
            'icon' => 'link',
        ],
        'conflict_logs' => [
            'label' => 'Logi konfliktow',
            'description' => 'Logi konfliktow z nieistniejacych batchow importu',
            'severity' => 'low',
            'icon' => 'file-text',
        ],
    ];

    /**
     * Get orphan statistics for all types
     *
     * @return array<string, int>
     */
    public function getOrphanStats(): array
    {
        return [
            'shop_mappings_categories' => $this->countOrphanCategoryMappings(),
            'shop_mappings_products' => $this->countOrphanProductMappings(),
            'media_products' => $this->countOrphanProductMedia(),
            'media_variants' => $this->countOrphanVariantMedia(),
            'product_categories' => $this->countOrphanProductCategories(),
            'conflict_logs' => $this->countOrphanConflictLogs(),
        ];
    }

    /**
     * Get total orphan count across all types
     *
     * @return int
     */
    public function getTotalOrphanCount(): int
    {
        return array_sum($this->getOrphanStats());
    }

    // ========================================
    // COUNT METHODS
    // ========================================

    /**
     * Count orphan category mappings
     */
    public function countOrphanCategoryMappings(): int
    {
        return ShopMapping::where('mapping_type', ShopMapping::TYPE_CATEGORY)
            ->where('is_active', true)
            ->whereRaw('CAST(ppm_value AS UNSIGNED) NOT IN (SELECT id FROM categories)')
            ->count();
    }

    /**
     * Count orphan product mappings
     */
    public function countOrphanProductMappings(): int
    {
        return ShopMapping::where('mapping_type', 'product')
            ->where('is_active', true)
            ->whereRaw('CAST(ppm_value AS UNSIGNED) NOT IN (SELECT id FROM products WHERE deleted_at IS NULL)')
            ->count();
    }

    /**
     * Count orphan product media (polymorphic)
     */
    public function countOrphanProductMedia(): int
    {
        return Media::where('mediable_type', 'App\\Models\\Product')
            ->whereNotIn('mediable_id', Product::withTrashed()->select('id'))
            ->count();
    }

    /**
     * Count orphan variant media
     */
    public function countOrphanVariantMedia(): int
    {
        return Media::where('mediable_type', 'App\\Models\\ProductVariant')
            ->whereNotIn('mediable_id', ProductVariant::select('id'))
            ->count();
    }

    /**
     * Count orphan product-category assignments
     */
    public function countOrphanProductCategories(): int
    {
        return DB::table('product_categories')
            ->whereNotIn('category_id', Category::select('id'))
            ->count();
    }

    /**
     * Count orphan conflict logs
     */
    public function countOrphanConflictLogs(): int
    {
        // Check if table exists first
        if (!DB::getSchemaBuilder()->hasTable('conflict_logs')) {
            return 0;
        }

        return DB::table('conflict_logs')
            ->whereNotNull('import_batch_id')
            ->whereNotIn('import_batch_id', ImportBatch::select('id'))
            ->count();
    }

    // ========================================
    // GET DETAILS METHODS
    // ========================================

    /**
     * Get orphan category mappings with details
     */
    public function getOrphanCategoryMappings(int $limit = 100): Collection
    {
        return ShopMapping::where('mapping_type', ShopMapping::TYPE_CATEGORY)
            ->where('is_active', true)
            ->whereRaw('CAST(ppm_value AS UNSIGNED) NOT IN (SELECT id FROM categories)')
            ->with('shop:id,name')
            ->limit($limit)
            ->get();
    }

    /**
     * Get orphan product mappings with details
     */
    public function getOrphanProductMappings(int $limit = 100): Collection
    {
        return ShopMapping::where('mapping_type', 'product')
            ->where('is_active', true)
            ->whereRaw('CAST(ppm_value AS UNSIGNED) NOT IN (SELECT id FROM products WHERE deleted_at IS NULL)')
            ->with('shop:id,name')
            ->limit($limit)
            ->get();
    }

    /**
     * Get orphan product media with details
     */
    public function getOrphanProductMedia(int $limit = 100): Collection
    {
        return Media::where('mediable_type', 'App\\Models\\Product')
            ->whereNotIn('mediable_id', Product::withTrashed()->select('id'))
            ->limit($limit)
            ->get();
    }

    /**
     * Get orphan variant media with details
     */
    public function getOrphanVariantMedia(int $limit = 100): Collection
    {
        return Media::where('mediable_type', 'App\\Models\\ProductVariant')
            ->whereNotIn('mediable_id', ProductVariant::select('id'))
            ->limit($limit)
            ->get();
    }

    /**
     * Get orphan product-category assignments
     */
    public function getOrphanProductCategories(int $limit = 100): Collection
    {
        return collect(
            DB::table('product_categories')
                ->whereNotIn('category_id', Category::select('id'))
                ->limit($limit)
                ->get()
        );
    }

    /**
     * Get orphan conflict logs
     */
    public function getOrphanConflictLogs(int $limit = 100): Collection
    {
        if (!DB::getSchemaBuilder()->hasTable('conflict_logs')) {
            return collect();
        }

        return collect(
            DB::table('conflict_logs')
                ->whereNotNull('import_batch_id')
                ->whereNotIn('import_batch_id', ImportBatch::select('id'))
                ->limit($limit)
                ->get()
        );
    }

    // ========================================
    // CLEANUP METHODS
    // ========================================

    /**
     * Cleanup orphan data by type
     *
     * @param string $type Orphan type from ORPHAN_TYPES
     * @param bool $dryRun If true, only count without deleting
     * @return array{type: string, dry_run: bool, deleted: int, errors: array}
     */
    public function cleanup(string $type, bool $dryRun = false): array
    {
        $stats = [
            'type' => $type,
            'dry_run' => $dryRun,
            'deleted' => 0,
            'errors' => [],
        ];

        try {
            DB::transaction(function () use ($type, $dryRun, &$stats) {
                $stats['deleted'] = match ($type) {
                    'shop_mappings_categories' => $this->cleanupOrphanCategoryMappings($dryRun),
                    'shop_mappings_products' => $this->cleanupOrphanProductMappings($dryRun),
                    'media_products' => $this->cleanupOrphanProductMedia($dryRun),
                    'media_variants' => $this->cleanupOrphanVariantMedia($dryRun),
                    'product_categories' => $this->cleanupOrphanProductCategories($dryRun),
                    'conflict_logs' => $this->cleanupOrphanConflictLogs($dryRun),
                    default => throw new \InvalidArgumentException("Unknown orphan type: {$type}"),
                };
            });

            if (!$dryRun && $stats['deleted'] > 0) {
                Log::info('OrphanDataCleanupService: Cleanup completed', [
                    'type' => $type,
                    'deleted' => $stats['deleted'],
                ]);
            }
        } catch (\Exception $e) {
            $stats['errors'][] = $e->getMessage();
            Log::error('OrphanDataCleanupService::cleanup failed', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $stats;
    }

    /**
     * Cleanup all orphan data types
     *
     * @param bool $dryRun If true, only count without deleting
     * @return array<string, array>
     */
    public function cleanupAll(bool $dryRun = false): array
    {
        $results = [];
        foreach (array_keys(self::ORPHAN_TYPES) as $type) {
            $results[$type] = $this->cleanup($type, $dryRun);
        }
        return $results;
    }

    // ========================================
    // PRIVATE CLEANUP IMPLEMENTATIONS
    // ========================================

    private function cleanupOrphanCategoryMappings(bool $dryRun): int
    {
        $query = ShopMapping::where('mapping_type', ShopMapping::TYPE_CATEGORY)
            ->where('is_active', true)
            ->whereRaw('CAST(ppm_value AS UNSIGNED) NOT IN (SELECT id FROM categories)');

        $count = $query->count();

        if (!$dryRun && $count > 0) {
            // Deactivate instead of hard delete for audit trail
            $query->update(['is_active' => false]);
            Log::info('Cleaned up orphan category mappings', ['count' => $count]);
        }

        return $count;
    }

    private function cleanupOrphanProductMappings(bool $dryRun): int
    {
        $query = ShopMapping::where('mapping_type', 'product')
            ->where('is_active', true)
            ->whereRaw('CAST(ppm_value AS UNSIGNED) NOT IN (SELECT id FROM products WHERE deleted_at IS NULL)');

        $count = $query->count();

        if (!$dryRun && $count > 0) {
            $query->update(['is_active' => false]);
            Log::info('Cleaned up orphan product mappings', ['count' => $count]);
        }

        return $count;
    }

    private function cleanupOrphanProductMedia(bool $dryRun): int
    {
        $query = Media::where('mediable_type', 'App\\Models\\Product')
            ->whereNotIn('mediable_id', Product::withTrashed()->select('id'));

        $count = $query->count();

        if (!$dryRun && $count > 0) {
            // Soft delete media records
            $query->delete();
            Log::info('Cleaned up orphan product media', ['count' => $count]);
        }

        return $count;
    }

    private function cleanupOrphanVariantMedia(bool $dryRun): int
    {
        $query = Media::where('mediable_type', 'App\\Models\\ProductVariant')
            ->whereNotIn('mediable_id', ProductVariant::select('id'));

        $count = $query->count();

        if (!$dryRun && $count > 0) {
            $query->delete();
            Log::info('Cleaned up orphan variant media', ['count' => $count]);
        }

        return $count;
    }

    private function cleanupOrphanProductCategories(bool $dryRun): int
    {
        $count = DB::table('product_categories')
            ->whereNotIn('category_id', Category::select('id'))
            ->count();

        if (!$dryRun && $count > 0) {
            DB::table('product_categories')
                ->whereNotIn('category_id', Category::select('id'))
                ->delete();
            Log::info('Cleaned up orphan product-category assignments', ['count' => $count]);
        }

        return $count;
    }

    private function cleanupOrphanConflictLogs(bool $dryRun): int
    {
        if (!DB::getSchemaBuilder()->hasTable('conflict_logs')) {
            return 0;
        }

        $count = DB::table('conflict_logs')
            ->whereNotNull('import_batch_id')
            ->whereNotIn('import_batch_id', ImportBatch::select('id'))
            ->count();

        if (!$dryRun && $count > 0) {
            DB::table('conflict_logs')
                ->whereNotNull('import_batch_id')
                ->whereNotIn('import_batch_id', ImportBatch::select('id'))
                ->delete();
            Log::info('Cleaned up orphan conflict logs', ['count' => $count]);
        }

        return $count;
    }
}
