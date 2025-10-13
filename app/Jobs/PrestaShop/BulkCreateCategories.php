<?php

namespace App\Jobs\PrestaShop;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\CategoryPreview;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopImportService;
use App\Services\JobProgressService;

/**
 * BulkCreateCategories Job
 *
 * ETAP_07 FAZA 3D: Category Import Preview System - Jobs Layer
 *
 * Purpose: Create missing categories in PPM (preserving hierarchy)
 *
 * Workflow:
 * 1. Load CategoryPreview record
 * 2. Verify status === 'approved' && !isExpired()
 * 3. Filter categories by selectedCategoryIds (if provided)
 * 4. Sort by level_depth (CRITICAL: parents before children!)
 * 5. For each category:
 *    → Call PrestaShopImportService->importCategoryFromPrestaShop()
 *    → Create Category record
 *    → Create ShopMapping
 *    → Log success/errors
 * 6. Mark preview as 'completed'
 * 7. Dispatch BulkImportProducts job (original import)
 *
 * Features:
 * - Background queue processing
 * - Hierarchical category creation (parents first)
 * - Selective import (user can choose specific categories)
 * - Progress tracking via JobProgressService
 * - Comprehensive error handling
 * - Automatic product import trigger after categories created
 *
 * Usage:
 * ```php
 * BulkCreateCategories::dispatch($previewId, $selectedCategoryIds);
 * ```
 *
 * @package App\Jobs\PrestaShop
 * @version 1.0
 * @since ETAP_07 FAZA 3D
 */
class BulkCreateCategories implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * CategoryPreview record ID
     *
     * @var int
     */
    protected int $previewId;

    /**
     * User-selected category IDs (optional - if empty, import all)
     *
     * @var array
     */
    protected array $selectedCategoryIds;

    /**
     * Original import options dla re-dispatch BulkImportProducts
     *
     * @var array
     */
    protected array $originalImportOptions;

    /**
     * Number of tries for the job
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Timeout for the job (15 minutes)
     *
     * @var int
     */
    public int $timeout = 900;

    /**
     * Create a new job instance
     *
     * @param int $previewId CategoryPreview record ID
     * @param array $selectedCategoryIds PrestaShop category IDs (empty = all)
     * @param array $originalImportOptions Original import mode and options
     */
    public function __construct(
        int $previewId,
        array $selectedCategoryIds = [],
        array $originalImportOptions = []
    ) {
        $this->previewId = $previewId;
        $this->selectedCategoryIds = $selectedCategoryIds;
        $this->originalImportOptions = $originalImportOptions;
    }

    /**
     * Execute the job
     *
     * @param PrestaShopImportService $importService
     * @param JobProgressService $progressService
     * @return void
     */
    public function handle(
        PrestaShopImportService $importService,
        JobProgressService $progressService
    ): void {
        $startTime = microtime(true);

        Log::info('BulkCreateCategories job started', [
            'preview_id' => $this->previewId,
            'selected_count' => count($this->selectedCategoryIds),
            'selected_ids' => $this->selectedCategoryIds,
        ]);

        try {
            // STEP 1: Load CategoryPreview record
            $preview = CategoryPreview::findOrFail($this->previewId);

            Log::info('CategoryPreview loaded', [
                'preview_id' => $preview->id,
                'job_id' => $preview->job_id,
                'shop_id' => $preview->shop_id,
                'status' => $preview->status,
                'total_categories' => $preview->total_categories,
            ]);

            // STEP 2: Validate preview status
            if ($preview->status !== CategoryPreview::STATUS_APPROVED) {
                throw new \Exception("Preview not approved (status: {$preview->status})");
            }

            if ($preview->isExpired()) {
                throw new \Exception('Preview has expired');
            }

            $shop = $preview->shop;

            // STEP 3: Get categories to import
            $categoriesToImport = $this->getCategoriesToImport($preview);

            $total = count($categoriesToImport);

            Log::info('Categories prepared for import', [
                'total_count' => $total,
                'shop_id' => $shop->id,
            ]);

            // STEP 4: Update job progress to running
            $progressId = null;
            if ($preview->job_id) {
                $progressId = $progressService->startPendingJob($preview->job_id, $total);
            }

            // STEP 5: Import each category (non-recursive - already sorted by level_depth)
            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($categoriesToImport as $index => $categoryData) {
                try {
                    $prestashopCategoryId = $categoryData['prestashop_id'];

                    // Update progress every 3 categories
                    if ($index % 3 === 0 && $progressId) {
                        $progressService->updateProgress($progressId, $index + 1, $errors);
                        $errors = []; // Reset errors after batch update
                    }

                    // Import category using PrestaShopImportService
                    // NOTE: non-recursive mode because we already sorted by level_depth
                    $category = $importService->importCategoryFromPrestaShop(
                        $prestashopCategoryId,
                        $shop,
                        false // non-recursive (already sorted!)
                    );

                    $imported++;

                    Log::info('Category imported successfully', [
                        'prestashop_id' => $prestashopCategoryId,
                        'ppm_id' => $category->id,
                        'name' => $category->name,
                    ]);

                } catch (\Exception $e) {
                    $skipped++;

                    $errors[] = [
                        'prestashop_id' => $categoryData['prestashop_id'],
                        'name' => $categoryData['name'],
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to import category', [
                        'prestashop_id' => $categoryData['prestashop_id'],
                        'name' => $categoryData['name'],
                        'error' => $e->getMessage(),
                    ]);

                    // Continue with next category (don't fail entire job)
                }
            }

            // STEP 6: Final progress update
            if ($progressId) {
                $progressService->updateProgress($progressId, $total, $errors);
            }

            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            // STEP 7: Mark preview as completed (or failed if errors)
            if ($imported > 0) {
                // Consider it success even if some categories failed
                // (as long as at least one succeeded)
                DB::table('category_preview')
                    ->where('id', $preview->id)
                    ->update(['status' => CategoryPreview::STATUS_APPROVED]); // Keep approved status

                Log::info('BulkCreateCategories completed', [
                    'preview_id' => $preview->id,
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'errors' => count($errors),
                    'execution_time_ms' => $executionTime,
                ]);
            } else {
                // All categories failed - mark as failed
                throw new \Exception('Failed to import any categories');
            }

            // STEP 8: Dispatch BulkImportProducts (original import)
            if (!empty($this->originalImportOptions)) {
                $this->dispatchProductImport($preview);
            }

            // STEP 9: Mark job progress as completed
            if ($progressId) {
                $progressService->markCompleted($progressId, [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'execution_time_ms' => $executionTime,
                ]);
            }

        } catch (\Exception $e) {
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            Log::error('BulkCreateCategories job failed', [
                'preview_id' => $this->previewId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'execution_time_ms' => $executionTime,
            ]);

            // Mark job progress as failed
            // Find JobProgress by job_id (UUID) to get progress ID (int)
            if (isset($preview) && $preview->job_id) {
                $progress = \App\Models\JobProgress::where('job_id', $preview->job_id)->first();
                if ($progress) {
                    $progressService->markFailed($progress->id, $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            throw $e;
        }
    }

    /**
     * Get categories to import from preview
     *
     * Filters by user selection and flattens tree structure
     *
     * @param CategoryPreview $preview
     * @return array Flat array of categories sorted by level_depth
     */
    protected function getCategoriesToImport(CategoryPreview $preview): array
    {
        $tree = $preview->getCategoryTree();

        // Flatten tree structure
        $flatCategories = $this->flattenTree($tree);

        // Filter by selected IDs if provided
        if (!empty($this->selectedCategoryIds)) {
            $flatCategories = array_filter($flatCategories, function ($category) {
                return in_array($category['prestashop_id'], $this->selectedCategoryIds);
            });
        }

        // Sort by level_depth (parents first - CRITICAL!)
        usort($flatCategories, function ($a, $b) {
            return ($a['level_depth'] ?? 0) <=> ($b['level_depth'] ?? 0);
        });

        return $flatCategories;
    }

    /**
     * Flatten category tree recursively
     *
     * @param array $tree
     * @return array
     */
    protected function flattenTree(array $tree): array
    {
        $flattened = [];

        foreach ($tree as $node) {
            $children = $node['children'] ?? [];
            unset($node['children']);

            $flattened[] = $node;

            if (!empty($children)) {
                $flattened = array_merge($flattened, $this->flattenTree($children));
            }
        }

        return $flattened;
    }

    /**
     * Dispatch BulkImportProducts job after categories created
     *
     * @param CategoryPreview $preview
     * @return void
     */
    protected function dispatchProductImport(CategoryPreview $preview): void
    {
        $shop = $preview->shop;
        $jobId = $preview->job_id;

        // 🔧 FIX: Flatten nested options structure + prevent infinite loop
        // originalImportOptions has: ['mode' => 'category', 'options' => ['category_id' => 12, ...]]
        // BulkImportProducts expects: $mode = 'category', $options = ['category_id' => 12, ...]
        $mode = $this->originalImportOptions['mode'] ?? 'individual';
        $options = array_merge(
            $this->originalImportOptions['options'] ?? [],
            ['skip_category_analysis' => true]  // 🔧 FIX: Categories already created!
        );

        BulkImportProducts::dispatch(
            $shop,
            $mode,
            $options,  // ✅ FIXED: Pass flattened options with skip flag
            $jobId
        );

        Log::info('BulkImportProducts dispatched after category creation', [
            'shop_id' => $shop->id,
            'job_id' => $jobId,
            'mode' => $mode,
            'options' => $options,
        ]);
    }

    /**
     * Handle job failure
     *
     * @param \Throwable $exception
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('BulkCreateCategories job failed permanently', [
            'preview_id' => $this->previewId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark preview as failed
        try {
            $preview = CategoryPreview::find($this->previewId);
            if ($preview) {
                $preview->update(['status' => CategoryPreview::STATUS_EXPIRED]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update preview status', [
                'preview_id' => $this->previewId,
                'error' => $e->getMessage(),
            ]);
        }

        // TODO: Send failure notification to user
    }
}
