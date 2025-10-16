<?php

namespace App\Jobs\Products;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Services\JobProgressService;

/**
 * Bulk Assign Categories Job
 *
 * Assigns categories to multiple products in background queue
 *
 * Features:
 * - Multi-store isolation (default categories only, shop_id=NULL)
 * - Primary category assignment (optional)
 * - Progress tracking integration
 * - Error handling with continue-on-error strategy
 * - Null safety for deleted products
 *
 * Business Logic:
 * - ONLY operates on default categories (shop_id=NULL pivot)
 * - Uses syncWithoutDetaching to preserve existing categories
 * - Primary category validation (must be in assigned categories)
 * - Per-product error tracking without job failure
 *
 * ETAP_07 FAZA 3D: Bulk Category Operations - Queue Jobs
 *
 * @package App\Jobs\Products
 */
class BulkAssignCategories implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Product IDs to process
     */
    public array $productIds;

    /**
     * Category IDs to assign
     */
    public array $categoryIds;

    /**
     * Primary category ID (optional)
     */
    public ?int $primaryCategoryId;

    /**
     * Job ID for progress tracking
     */
    public string $jobId;

    /**
     * Number of times job may be attempted
     */
    public int $tries = 3;

    /**
     * Maximum seconds job can run
     */
    public int $timeout = 600; // 10 minutes

    /**
     * Create new job instance
     *
     * @param array $productIds Product IDs to assign categories to
     * @param array $categoryIds Category IDs to assign
     * @param int|null $primaryCategoryId Primary category ID (must be in categoryIds)
     * @param string $jobId Job ID for progress tracking
     */
    public function __construct(
        array $productIds,
        array $categoryIds,
        ?int $primaryCategoryId,
        string $jobId
    ) {
        $this->productIds = $productIds;
        $this->categoryIds = $categoryIds;
        $this->primaryCategoryId = $primaryCategoryId;
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
        Log::info('BulkAssignCategories job started', [
            'job_id' => $this->jobId,
            'product_count' => count($this->productIds),
            'category_count' => count($this->categoryIds),
            'primary_category_id' => $this->primaryCategoryId,
        ]);

        try {
            // === 1. INITIALIZE PROGRESS TRACKING ===
            $progressService->startJob(
                $this->jobId,
                'bulk_assign_categories',
                count($this->productIds)
            );

            // Validate primary category is in categoryIds
            if ($this->primaryCategoryId && !in_array($this->primaryCategoryId, $this->categoryIds)) {
                Log::warning('Primary category not in assigned categories - ignoring', [
                    'job_id' => $this->jobId,
                    'primary_category_id' => $this->primaryCategoryId,
                    'category_ids' => $this->categoryIds,
                ]);
                $this->primaryCategoryId = null; // Ignore invalid primary
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            // === 2. PROCESS EACH PRODUCT ===
            foreach ($this->productIds as $index => $productId) {
                try {
                    // Null safety: Product may be deleted during job execution
                    $product = Product::find($productId);

                    if (!$product) {
                        $errorCount++;
                        $errors[] = [
                            'product_id' => $productId,
                            'error' => 'Product not found (deleted)',
                        ];

                        Log::warning('Product not found in BulkAssignCategories', [
                            'job_id' => $this->jobId,
                            'product_id' => $productId,
                        ]);

                        continue; // Skip to next product
                    }

                    // === 3. ATTACH CATEGORIES (DEFAULT ONLY, shop_id=NULL) ===
                    // syncWithoutDetaching: Add new categories without removing existing
                    DB::transaction(function () use ($product) {
                        $product->categories()
                            ->syncWithoutDetaching($this->categoryIds);
                    });

                    // === 4. SET PRIMARY CATEGORY IF SPECIFIED ===
                    if ($this->primaryCategoryId) {
                        DB::transaction(function () use ($product) {
                            // Remove existing primary flag from all default categories
                            DB::table('product_categories')
                                ->where('product_id', $product->id)
                                ->whereNull('shop_id')
                                ->update(['is_primary' => false]);

                            // Set new primary category
                            DB::table('product_categories')
                                ->where('product_id', $product->id)
                                ->where('category_id', $this->primaryCategoryId)
                                ->whereNull('shop_id')
                                ->update(['is_primary' => true]);
                        });

                        Log::debug('Primary category set', [
                            'job_id' => $this->jobId,
                            'product_id' => $product->id,
                            'primary_category_id' => $this->primaryCategoryId,
                        ]);
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'product_id' => $productId,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to assign categories to product', [
                        'job_id' => $this->jobId,
                        'product_id' => $productId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                // === 5. UPDATE PROGRESS (every product) ===
                $progressService->updateProgress(
                    $this->jobId,
                    $index + 1,
                    $errorCount > 0 ? $errors : []
                );
            }

            // === 6. COMPLETE JOB ===
            $progressService->completeJob($this->jobId);

            Log::info('BulkAssignCategories job completed', [
                'job_id' => $this->jobId,
                'total_products' => count($this->productIds),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'category_ids' => $this->categoryIds,
                'primary_category_id' => $this->primaryCategoryId,
            ]);

        } catch (\Exception $e) {
            // === 7. FAIL JOB ON CRITICAL ERROR ===
            $progressService->failJob($this->jobId, $e->getMessage());

            Log::error('BulkAssignCategories job failed critically', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Job failed permanently
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('BulkAssignCategories job failed permanently', [
            'job_id' => $this->jobId,
            'product_count' => count($this->productIds),
            'category_count' => count($this->categoryIds),
            'error' => $exception->getMessage(),
        ]);
    }
}
