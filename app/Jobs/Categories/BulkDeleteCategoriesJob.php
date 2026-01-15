<?php

namespace App\Jobs\Categories;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Services\JobProgressService;

/**
 * BulkDeleteCategoriesJob - Force delete categories with products and children
 *
 * ETAP_05 FAZA 3: Category Tree Management - Force Delete
 *
 * Features:
 * - Detaches products from categories before deletion
 * - Recursively deletes all descendant categories
 * - Progress tracking with JobProgressService
 * - Transaction rollback on failure
 * - Background processing via Laravel Queue
 *
 * Usage:
 * ```
 * BulkDeleteCategoriesJob::dispatch([1, 2, 3], true, $jobId);
 * ```
 *
 * @package App\Jobs\Categories
 * @version 1.0
 * @since ETAP_05_FAZA_3
 */
class BulkDeleteCategoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Category IDs to delete
     *
     * @var array
     */
    protected array $categoryIds;

    /**
     * Force delete (detach products and delete children)
     *
     * @var bool
     */
    protected bool $force;

    /**
     * Pre-generated job ID (UUID) for progress tracking
     *
     * @var string
     */
    protected string $jobId;

    /**
     * Number of tries for the job
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Timeout for the job (10 minutes)
     *
     * @var int
     */
    public int $timeout = 600;

    /**
     * Create a new job instance
     *
     * @param array $categoryIds Category IDs to delete
     * @param bool $force Force delete (detach products and delete children)
     * @param string $jobId Pre-generated UUID for progress tracking
     */
    public function __construct(array $categoryIds, bool $force, string $jobId)
    {
        $this->categoryIds = $categoryIds;
        $this->force = $force;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job
     *
     * @param JobProgressService $progressService
     * @return void
     */
    public function handle(JobProgressService $progressService): void
    {
        $startTime = microtime(true);
        $progressId = null;

        Log::info('BulkDeleteCategoriesJob STARTED', [
            'category_ids' => $this->categoryIds,
            'category_count' => count($this->categoryIds),
            'force' => $this->force,
            'job_id' => $this->jobId,
        ]);

        try {
            // Calculate total count (main categories + descendants)
            $totalCount = $this->calculateTotalCount();

            Log::info('Total count calculated', [
                'main_categories' => count($this->categoryIds),
                'total_with_descendants' => $totalCount,
            ]);

            // Start job progress tracking
            $progressId = $progressService->startPendingJob($this->jobId, $totalCount);

            if (!$progressId) {
                // Fallback: Create new progress if not found
                Log::warning('Pending progress not found, creating new', ['job_id' => $this->jobId]);
                $progressId = $progressService->createJobProgress(
                    $this->jobId,
                    null, // No shop context for category deletion
                    'category_delete',
                    $totalCount
                );
            }

            $deletedCount = 0;
            $productsDetached = 0;
            $mappingsDeleted = 0;

            DB::transaction(function () use ($progressService, $progressId, &$deletedCount, &$productsDetached, &$mappingsDeleted) {
                // STEP 1: Get all category IDs (main + descendants)
                $allCategoryIds = $this->getAllCategoryIds();

                Log::info('All category IDs collected', [
                    'main_categories' => count($this->categoryIds),
                    'total_categories' => count($allCategoryIds),
                    'all_ids' => $allCategoryIds,
                ]);

                // STEP 2: Detach products from categories
                $productsDetached = $this->detachProducts($allCategoryIds);

                Log::info('Products detached', [
                    'category_count' => count($allCategoryIds),
                    'products_detached' => $productsDetached,
                ]);

                // STEP 2.5: Delete shop mappings for categories
                $mappingsDeleted = $this->deleteShopMappings($allCategoryIds);

                Log::info('Shop mappings deleted', [
                    'category_count' => count($allCategoryIds),
                    'mappings_deleted' => $mappingsDeleted,
                ]);

                // Update progress
                $progressService->updateProgress($progressId, count($allCategoryIds), []);

                // STEP 3: Delete categories (children first, then parents)
                foreach ($this->categoryIds as $categoryId) {
                    $deletedInTree = $this->deleteCategoryTree($categoryId);
                    $deletedCount += $deletedInTree;

                    Log::info('Category tree deleted', [
                        'category_id' => $categoryId,
                        'deleted_in_tree' => $deletedInTree,
                        'total_deleted_so_far' => $deletedCount,
                    ]);

                    // Update progress
                    $progressService->updateProgress($progressId, $deletedCount, []);
                }

                Log::info('DB transaction about to COMMIT', [
                    'total_deleted' => $deletedCount,
                ]);
            });

            Log::info('DB transaction COMMITTED successfully');

            // Mark as completed
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);
            $progressService->markCompleted($progressId, [
                'deleted' => $deletedCount,
                'products_detached' => $productsDetached,
                'mappings_deleted' => $mappingsDeleted,
                'execution_time_ms' => $executionTime,
            ]);

            Log::info('BulkDeleteCategoriesJob COMPLETED', [
                'category_ids' => $this->categoryIds,
                'deleted_count' => $deletedCount,
                'products_detached' => $productsDetached,
                'mappings_deleted' => $mappingsDeleted,
                'execution_time_ms' => $executionTime,
                'progress_id' => $progressId,
            ]);

        } catch (\Exception $e) {
            // Mark as failed
            if ($progressId) {
                $progressService->markFailed($progressId, $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            Log::error('BulkDeleteCategoriesJob FAILED', [
                'category_ids' => $this->categoryIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'progress_id' => $progressId,
            ]);

            throw $e;
        }
    }

    /**
     * Calculate total count of categories to delete (including descendants)
     *
     * @return int
     */
    protected function calculateTotalCount(): int
    {
        $count = 0;

        foreach ($this->categoryIds as $categoryId) {
            $category = Category::find($categoryId);

            if ($category) {
                // Count this category + all descendants
                // NOTE: descendants is an Attribute accessor, not a relationship
                $count += 1 + $category->descendants->count();
            }
        }

        return $count;
    }

    /**
     * Get all category IDs (main + descendants)
     *
     * @return array
     */
    protected function getAllCategoryIds(): array
    {
        $allIds = [];

        foreach ($this->categoryIds as $categoryId) {
            $category = Category::find($categoryId);

            if ($category) {
                $allIds[] = $categoryId;
                // NOTE: descendants is an Attribute accessor, not a relationship
                $descendantIds = $category->descendants->pluck('id')->toArray();
                $allIds = array_merge($allIds, $descendantIds);
            }
        }

        return array_unique($allIds);
    }

    /**
     * Detach products from categories
     *
     * @param array $categoryIds
     * @return int Number of products detached
     */
    protected function detachProducts(array $categoryIds): int
    {
        if (empty($categoryIds)) {
            return 0;
        }

        // Detach products from product_categories pivot table
        $detachedCount = DB::table('product_categories')
            ->whereIn('category_id', $categoryIds)
            ->delete();

        return $detachedCount;
    }

    /**
     * Delete shop mappings for categories
     *
     * Remove category mappings from shop_mappings table to prevent orphaned records
     *
     * @param array $categoryIds PPM category IDs
     * @return int Number of mappings deleted
     */
    protected function deleteShopMappings(array $categoryIds): int
    {
        if (empty($categoryIds)) {
            return 0;
        }

        // Convert category IDs to strings (ppm_value is string column)
        $ppmValues = array_map('strval', $categoryIds);

        // Delete category mappings from shop_mappings table
        $deletedCount = DB::table('shop_mappings')
            ->where('mapping_type', 'category')
            ->whereIn('ppm_value', $ppmValues)
            ->delete();

        Log::info('BulkDeleteCategoriesJob: Shop mappings deleted', [
            'category_ids' => $categoryIds,
            'ppm_values' => $ppmValues,
            'deleted_count' => $deletedCount,
        ]);

        return $deletedCount;
    }

    /**
     * Delete category and all its descendants recursively
     *
     * @param int $categoryId
     * @return int Number of categories deleted
     */
    protected function deleteCategoryTree(int $categoryId): int
    {
        $category = Category::find($categoryId);

        if (!$category) {
            Log::warning('Category not found for deletion', [
                'category_id' => $categoryId,
            ]);
            return 0;
        }

        $deletedCount = 0;

        // Recursively delete children first
        $children = $category->children;
        foreach ($children as $child) {
            $deletedCount += $this->deleteCategoryTree($child->id);
        }

        // Delete the category permanently (forceDelete to remove from DB)
        try {
            $category->forceDelete();
            $deletedCount++;

            Log::info('Category deleted successfully', [
                'category_id' => $categoryId,
                'category_name' => $category->name,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete category', [
                'category_id' => $categoryId,
                'category_name' => $category->name,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $deletedCount;
    }

    /**
     * Handle job failure
     *
     * @param \Throwable $exception
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('BulkDeleteCategoriesJob failed permanently', [
            'category_ids' => $this->categoryIds,
            'force' => $this->force,
            'job_id' => $this->jobId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // TODO: Send failure notification to user
    }
}
