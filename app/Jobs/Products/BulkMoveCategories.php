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
 * Bulk Move Categories Job
 *
 * Moves products from one category to another in background queue
 *
 * Features:
 * - Multi-store isolation (default categories only, shop_id=NULL)
 * - Two modes: 'replace' (move) or 'add_keep' (copy)
 * - Auto-update primary category on move
 * - Progress tracking integration
 * - Error handling with continue-on-error strategy
 * - Null safety for deleted products
 *
 * Business Logic:
 * - ONLY operates on default categories (shop_id=NULL pivot)
 * - 'replace': Removes FROM category, adds TO category
 * - 'add_keep': Adds TO category, keeps FROM category
 * - Skips products not in FROM category (intelligent filtering)
 * - Updates primary category if FROM was primary (replace mode only)
 *
 * ETAP_07 FAZA 3D: Bulk Category Operations - Queue Jobs
 *
 * @package App\Jobs\Products
 */
class BulkMoveCategories implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Product IDs to process
     */
    public array $productIds;

    /**
     * FROM category ID (source)
     */
    public int $fromCategoryId;

    /**
     * TO category ID (destination)
     */
    public int $toCategoryId;

    /**
     * Operation mode: 'replace' (move) or 'add_keep' (copy)
     */
    public string $mode;

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
     * @param array $productIds Product IDs to process
     * @param int $fromCategoryId Source category ID
     * @param int $toCategoryId Destination category ID
     * @param string $mode Operation mode: 'replace' or 'add_keep'
     * @param string $jobId Job ID for progress tracking
     */
    public function __construct(
        array $productIds,
        int $fromCategoryId,
        int $toCategoryId,
        string $mode,
        string $jobId
    ) {
        $this->productIds = $productIds;
        $this->fromCategoryId = $fromCategoryId;
        $this->toCategoryId = $toCategoryId;
        $this->mode = $mode;
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
        Log::info('BulkMoveCategories job started', [
            'job_id' => $this->jobId,
            'product_count' => count($this->productIds),
            'from_category_id' => $this->fromCategoryId,
            'to_category_id' => $this->toCategoryId,
            'mode' => $this->mode,
        ]);

        try {
            // === 1. VALIDATE MODE ===
            if (!in_array($this->mode, ['replace', 'add_keep'])) {
                throw new \InvalidArgumentException("Invalid mode: {$this->mode}. Must be 'replace' or 'add_keep'.");
            }

            // === 2. INITIALIZE PROGRESS TRACKING ===
            $progressService->startJob(
                $this->jobId,
                'bulk_move_categories',
                count($this->productIds)
            );

            $successCount = 0;
            $skippedCount = 0;
            $errorCount = 0;
            $errors = [];

            // === 3. PROCESS EACH PRODUCT ===
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

                        Log::warning('Product not found in BulkMoveCategories', [
                            'job_id' => $this->jobId,
                            'product_id' => $productId,
                        ]);

                        continue; // Skip to next product
                    }

                    DB::transaction(function () use ($product, &$successCount, &$skippedCount) {
                        // === 4. CHECK IF PRODUCT HAS FROM CATEGORY ===
                        $hasFrom = DB::table('product_categories')
                            ->where('product_id', $product->id)
                            ->where('category_id', $this->fromCategoryId)
                            ->whereNull('shop_id')
                            ->exists();

                        if (!$hasFrom) {
                            $skippedCount++;
                            Log::debug('Product does not have FROM category - skipping', [
                                'job_id' => $this->jobId,
                                'product_id' => $product->id,
                                'from_category_id' => $this->fromCategoryId,
                            ]);
                            return; // Skip this product
                        }

                        // === 5. ADD TO CATEGORY (if not already present) ===
                        $hasTo = DB::table('product_categories')
                            ->where('product_id', $product->id)
                            ->where('category_id', $this->toCategoryId)
                            ->whereNull('shop_id')
                            ->exists();

                        if (!$hasTo) {
                            DB::table('product_categories')->insert([
                                'product_id' => $product->id,
                                'category_id' => $this->toCategoryId,
                                'shop_id' => null,
                                'is_primary' => false, // Will be set later if needed
                                'sort_order' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            Log::debug('TO category added', [
                                'job_id' => $this->jobId,
                                'product_id' => $product->id,
                                'to_category_id' => $this->toCategoryId,
                            ]);
                        }

                        // === 6. REMOVE FROM CATEGORY (only in 'replace' mode) ===
                        if ($this->mode === 'replace') {
                            // Check if FROM was primary
                            $fromWasPrimary = DB::table('product_categories')
                                ->where('product_id', $product->id)
                                ->where('category_id', $this->fromCategoryId)
                                ->whereNull('shop_id')
                                ->value('is_primary');

                            // Delete FROM category
                            DB::table('product_categories')
                                ->where('product_id', $product->id)
                                ->where('category_id', $this->fromCategoryId)
                                ->whereNull('shop_id')
                                ->delete();

                            Log::debug('FROM category removed', [
                                'job_id' => $this->jobId,
                                'product_id' => $product->id,
                                'from_category_id' => $this->fromCategoryId,
                                'was_primary' => $fromWasPrimary,
                            ]);

                            // === 7. UPDATE PRIMARY IF NEEDED ===
                            if ($fromWasPrimary) {
                                // Set TO category as new primary
                                DB::table('product_categories')
                                    ->where('product_id', $product->id)
                                    ->whereNull('shop_id')
                                    ->update(['is_primary' => false]); // Clear all primary flags

                                DB::table('product_categories')
                                    ->where('product_id', $product->id)
                                    ->where('category_id', $this->toCategoryId)
                                    ->whereNull('shop_id')
                                    ->update(['is_primary' => true]);

                                Log::info('Primary category updated during move', [
                                    'job_id' => $this->jobId,
                                    'product_id' => $product->id,
                                    'old_primary' => $this->fromCategoryId,
                                    'new_primary' => $this->toCategoryId,
                                ]);
                            }
                        }

                        $successCount++;
                    });

                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'product_id' => $productId,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to move product categories', [
                        'job_id' => $this->jobId,
                        'product_id' => $productId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                // === 8. UPDATE PROGRESS (every product) ===
                $progressService->updateProgress(
                    $this->jobId,
                    $index + 1,
                    $errorCount > 0 ? $errors : []
                );
            }

            // === 9. COMPLETE JOB ===
            $progressService->completeJob($this->jobId);

            Log::info('BulkMoveCategories job completed', [
                'job_id' => $this->jobId,
                'total_products' => count($this->productIds),
                'success_count' => $successCount,
                'skipped_count' => $skippedCount,
                'error_count' => $errorCount,
                'from_category_id' => $this->fromCategoryId,
                'to_category_id' => $this->toCategoryId,
                'mode' => $this->mode,
            ]);

        } catch (\Exception $e) {
            // === 10. FAIL JOB ON CRITICAL ERROR ===
            $progressService->failJob($this->jobId, $e->getMessage());

            Log::error('BulkMoveCategories job failed critically', [
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
        Log::error('BulkMoveCategories job failed permanently', [
            'job_id' => $this->jobId,
            'product_count' => count($this->productIds),
            'from_category_id' => $this->fromCategoryId,
            'to_category_id' => $this->toCategoryId,
            'mode' => $this->mode,
            'error' => $exception->getMessage(),
        ]);
    }
}
