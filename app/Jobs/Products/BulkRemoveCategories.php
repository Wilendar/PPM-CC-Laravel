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
 * Bulk Remove Categories Job
 *
 * Removes categories from multiple products in background queue
 *
 * Features:
 * - Multi-store isolation (default categories only, shop_id=NULL)
 * - Auto-reassign primary category if removed
 * - Progress tracking integration
 * - Error handling with continue-on-error strategy
 * - Null safety for deleted products
 *
 * Business Logic:
 * - ONLY operates on default categories (shop_id=NULL pivot)
 * - Uses detach with specific category IDs
 * - Auto-reassigns primary to first remaining category if removed
 * - Per-product error tracking without job failure
 *
 * ETAP_07 FAZA 3D: Bulk Category Operations - Queue Jobs
 *
 * @package App\Jobs\Products
 */
class BulkRemoveCategories implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Product IDs to process
     */
    public array $productIds;

    /**
     * Category IDs to remove
     */
    public array $categoryIds;

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
     * @param array $productIds Product IDs to remove categories from
     * @param array $categoryIds Category IDs to remove
     * @param string $jobId Job ID for progress tracking
     */
    public function __construct(
        array $productIds,
        array $categoryIds,
        string $jobId
    ) {
        $this->productIds = $productIds;
        $this->categoryIds = $categoryIds;
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
        Log::info('BulkRemoveCategories job started', [
            'job_id' => $this->jobId,
            'product_count' => count($this->productIds),
            'category_count' => count($this->categoryIds),
        ]);

        try {
            // === 1. INITIALIZE PROGRESS TRACKING ===
            $progressService->startJob(
                $this->jobId,
                'bulk_remove_categories',
                count($this->productIds)
            );

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

                        Log::warning('Product not found in BulkRemoveCategories', [
                            'job_id' => $this->jobId,
                            'product_id' => $productId,
                        ]);

                        continue; // Skip to next product
                    }

                    DB::transaction(function () use ($product) {
                        // === 3. GET CURRENT PRIMARY CATEGORY (before detach) ===
                        $currentPrimary = DB::table('product_categories')
                            ->where('product_id', $product->id)
                            ->whereNull('shop_id')
                            ->where('is_primary', true)
                            ->value('category_id');

                        // === 4. DETACH SPECIFIED CATEGORIES (default only) ===
                        DB::table('product_categories')
                            ->where('product_id', $product->id)
                            ->whereNull('shop_id')
                            ->whereIn('category_id', $this->categoryIds)
                            ->delete();

                        Log::debug('Categories detached', [
                            'job_id' => $this->jobId,
                            'product_id' => $product->id,
                            'category_ids' => $this->categoryIds,
                        ]);

                        // === 5. AUTO-REASSIGN PRIMARY IF REMOVED ===
                        if ($currentPrimary && in_array($currentPrimary, $this->categoryIds)) {
                            // Primary category was removed - find new primary
                            $newPrimaryId = DB::table('product_categories')
                                ->where('product_id', $product->id)
                                ->whereNull('shop_id')
                                ->orderBy('sort_order', 'asc')
                                ->value('category_id');

                            if ($newPrimaryId) {
                                // Set first remaining category as primary
                                DB::table('product_categories')
                                    ->where('product_id', $product->id)
                                    ->where('category_id', $newPrimaryId)
                                    ->whereNull('shop_id')
                                    ->update(['is_primary' => true]);

                                Log::info('Primary category auto-reassigned', [
                                    'job_id' => $this->jobId,
                                    'product_id' => $product->id,
                                    'old_primary_id' => $currentPrimary,
                                    'new_primary_id' => $newPrimaryId,
                                ]);
                            } else {
                                // No remaining categories - product has no primary
                                Log::warning('Product has no remaining default categories after removal', [
                                    'job_id' => $this->jobId,
                                    'product_id' => $product->id,
                                ]);
                            }
                        }
                    });

                    $successCount++;

                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'product_id' => $productId,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to remove categories from product', [
                        'job_id' => $this->jobId,
                        'product_id' => $productId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                // === 6. UPDATE PROGRESS (every product) ===
                $progressService->updateProgress(
                    $this->jobId,
                    $index + 1,
                    $errorCount > 0 ? $errors : []
                );
            }

            // === 7. COMPLETE JOB ===
            $progressService->completeJob($this->jobId);

            Log::info('BulkRemoveCategories job completed', [
                'job_id' => $this->jobId,
                'total_products' => count($this->productIds),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'category_ids' => $this->categoryIds,
            ]);

        } catch (\Exception $e) {
            // === 8. FAIL JOB ON CRITICAL ERROR ===
            $progressService->failJob($this->jobId, $e->getMessage());

            Log::error('BulkRemoveCategories job failed critically', [
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
        Log::error('BulkRemoveCategories job failed permanently', [
            'job_id' => $this->jobId,
            'product_count' => count($this->productIds),
            'category_count' => count($this->categoryIds),
            'error' => $exception->getMessage(),
        ]);
    }
}
