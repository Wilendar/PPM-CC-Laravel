<?php

namespace App\Jobs;

use App\Exceptions\PrestaShopAPIException;
use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\SyncJob;
use App\Services\PrestaShop\ConflictResolver;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\PrestaShop\PrestaShopPriceImporter;
use App\Services\PrestaShop\PrestaShopStockImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Pull Products from PrestaShop Job
 *
 * Background job to pull current product data from PrestaShop → PPM
 * Runs every 6 hours via scheduler
 *
 * BUGFIX 2025-11-06: Separate pull direction (PrestaShop → PPM)
 * - Fetch current data from PrestaShop API
 * - Update product_shop_data with fresh data
 * - Set last_pulled_at timestamp
 * - Mark sync_status as 'synced'
 *
 * PROBLEM #4 - Task 16c: Price Import Integration (2025-11-07)
 * - Import prices from PrestaShop specific_prices → PPM product_prices
 * - Import stock from PrestaShop stock_availables → PPM product_stock
 *
 * PROBLEM #9.3 - Conflict Resolution System (2025-11-13)
 * - Use ConflictResolver to detect conflicts between PPM and PrestaShop data
 * - Apply conflict resolution strategy from SystemSetting 'sync.conflict_resolution'
 * - Store conflicts for manual resolution when 'manual' strategy is active
 *
 * @package App\Jobs
 */
class PullProductsFromPrestaShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * SyncJob ID for tracking
     *
     * FIX: Store only ID, not model instance (avoids SerializesModels issue)
     * When SyncJob is deleted by cleanup, job won't crash on unserialization
     *
     * @var int|null
     */
    protected ?int $syncJobId = null;

    /**
     * Batch size for processing products
     * ETAP_07 FAZA 9.2: Dynamic batch size from SystemSettings (2025-11-13)
     *
     * @var int
     */
    protected int $batchSize;

    /**
     * Number of times job may be attempted
     */
    public int $tries = 3;

    /**
     * Maximum seconds job can run before timing out
     * ETAP_07 FAZA 9.2: Dynamic timeout from SystemSettings (2025-11-13)
     */
    public int $timeout;

    /**
     * Create a new job instance.
     * ETAP_07 FAZA 9.2: Load dynamic settings (2025-11-13)
     *
     * @param PrestaShopShop $shop Shop to pull data from
     */
    public function __construct(
        public PrestaShopShop $shop
    ) {
        // Load batch size and timeout from system settings
        $this->batchSize = \App\Models\SystemSetting::get('sync.batch_size', 10);
        $this->timeout = \App\Models\SystemSetting::get('sync.timeout', 300);
        // Create SyncJob for tracking (FIX #1 - BUG #7)
        // Note: User ID captured here (web context), NULL in queue = SYSTEM
        $syncJob = SyncJob::create([
            'job_id' => \Str::uuid(),
            'job_type' => 'import_products',
            'job_name' => "Import Products from {$shop->name}",
            'source_type' => SyncJob::TYPE_PRESTASHOP,
            'source_id' => $shop->id,
            'target_type' => SyncJob::TYPE_PPM,
            'target_id' => null, // Multiple products
            'status' => SyncJob::STATUS_PENDING,
            'trigger_type' => SyncJob::TRIGGER_SCHEDULED, // Default to scheduled
            'user_id' => auth()->id() ?? 1, // Fallback to admin
            'queue_name' => 'default',
            'total_items' => 0, // Will be updated after fetching products
            'processed_items' => 0,
            'successful_items' => 0,
            'failed_items' => 0,
            'scheduled_at' => now(),
        ]);

        // Store only ID (not model instance) to avoid serialization issues
        $this->syncJobId = $syncJob->id;
    }

    /**
     * Get SyncJob instance (gracefully handles deleted jobs)
     *
     * @return SyncJob|null
     */
    protected function getSyncJob(): ?SyncJob
    {
        if (!$this->syncJobId) {
            return null;
        }

        try {
            return SyncJob::find($this->syncJobId);
        } catch (\Exception $e) {
            Log::warning('Failed to load SyncJob (may have been deleted by cleanup)', [
                'sync_job_id' => $this->syncJobId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = now();
        $syncJob = $this->getSyncJob();

        try {
            // Update status to running (FIX #1 - BUG #7)
            $syncJob?->start();

            Log::debug('PullProductsFromPrestaShop STARTED', [
                'shop_id' => $this->shop->id,
                'shop_name' => $this->shop->name,
                'sync_job_id' => $syncJob?->id,
            ]);

            $client = PrestaShopClientFactory::create($this->shop);
            $priceImporter = app(PrestaShopPriceImporter::class);
            $stockImporter = app(PrestaShopStockImporter::class);
            $conflictResolver = app(ConflictResolver::class);

            // Get all products linked to this shop
            $productsToSync = Product::whereHas('shopData', function($query) {
                $query->where('shop_id', $this->shop->id)
                      ->whereNotNull('prestashop_product_id');
            })->get();

            $total = $productsToSync->count();

            // Update total items (FIX #1 - BUG #7)
            $syncJob?->update(['total_items' => $total]);

            Log::debug('PullProductsFromPrestaShop PRODUCTS TO SYNC', [
                'shop_id' => $this->shop->id,
                'total_products' => $total,
            ]);

            $synced = 0;
            $errors = 0;
            $conflicts = 0;
            $pricesImported = 0;
            $stockImported = 0;

            foreach ($productsToSync as $index => $product) {
            try {
                $shopData = $product->shopData()
                    ->where('shop_id', $this->shop->id)
                    ->first();

                if (!$shopData || !$shopData->prestashop_product_id) {
                    continue;
                }

                Log::debug('Fetching product from PrestaShop', [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'prestashop_product_id' => $shopData->prestashop_product_id,
                    'shop_id' => $this->shop->id,
                ]);

                // Fetch from PrestaShop
                $psData = $client->getProduct($shopData->prestashop_product_id);

                if (isset($psData['product'])) {
                    $psData = $psData['product'];
                }

                // PROBLEM #9.3: RESOLVE CONFLICT BEFORE UPDATE (2025-11-13)
                $resolution = $conflictResolver->resolve($shopData, $psData);

                Log::debug('Conflict resolution result', [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'should_update' => $resolution['should_update'],
                    'reason' => $resolution['reason'],
                    'has_conflicts' => !empty($resolution['conflicts']),
                ]);

                if ($resolution['should_update']) {
                    // Update allowed - apply PrestaShop data
                    $shopData->update(array_merge($resolution['data'], [
                        'sync_status' => 'synced',
                        'has_conflicts' => false,
                        'conflict_log' => null,
                        'conflicts_detected_at' => null,
                    ]));

                    Log::info('Product updated from PrestaShop', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'reason' => $resolution['reason'],
                    ]);
                } else {
                    // Update blocked - store conflicts if detected
                    if ($resolution['conflicts']) {
                        $shopData->update([
                            'sync_status' => 'conflict',
                            'conflict_log' => $resolution['conflicts'],
                            'has_conflicts' => true,
                            'conflicts_detected_at' => now(),
                        ]);

                        $conflicts++;

                        Log::warning('Conflict detected - update blocked', [
                            'product_id' => $product->id,
                            'sku' => $product->sku,
                            'reason' => $resolution['reason'],
                            'conflicts_count' => count($resolution['conflicts']),
                        ]);
                    } else {
                        // No conflicts, just different strategy (e.g., ppm_wins)
                        Log::info('Update skipped by conflict resolution strategy', [
                            'product_id' => $product->id,
                            'sku' => $product->sku,
                            'reason' => $resolution['reason'],
                        ]);
                    }
                }

                // PROBLEM #4 - Task 16c: Import prices from PrestaShop
                try {
                    $importedPrices = $priceImporter->importPricesForProduct($product, $this->shop);
                    $pricesImported += count($importedPrices);

                    Log::debug('Prices imported for product', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'prices_count' => count($importedPrices),
                    ]);
                } catch (PrestaShopAPIException $priceError) {
                    // BUG #8 FIX #1: 404 = product deleted, re-throw to trigger unlinking
                    if ($priceError->isNotFound()) {
                        Log::debug('Product prices not found (404), will unlink product', [
                            'product_id' => $product->id,
                            'sku' => $product->sku,
                            'prestashop_product_id' => $shopData->prestashop_product_id,
                        ]);
                        throw $priceError; // Re-throw to outer catch
                    }

                    // Other PrestaShop API errors - log but continue
                    Log::warning('Failed to import prices for product (non-404)', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'error_code' => $priceError->getHttpStatusCode(),
                        'error' => $priceError->getMessage(),
                    ]);
                } catch (\Exception $priceError) {
                    // Generic errors - log and continue
                    Log::warning('Failed to import prices for product', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'error' => $priceError->getMessage(),
                    ]);
                }

                // PROBLEM #4 - Task 17b: Import stock from PrestaShop
                try {
                    $importedStock = $stockImporter->importStockForProduct($product, $this->shop);
                    $stockImported += count($importedStock);

                    Log::debug('Stock imported for product', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'stock_records_count' => count($importedStock),
                    ]);
                } catch (PrestaShopAPIException $stockError) {
                    // BUG #8 FIX #1: 404 already handled by getProduct() above, but log if happens here
                    if ($stockError->isNotFound()) {
                        Log::debug('Product stock not found (404)', [
                            'product_id' => $product->id,
                            'sku' => $product->sku,
                        ]);
                        throw $stockError; // Re-throw to outer catch
                    }

                    // Other PrestaShop API errors - log but continue
                    Log::warning('Failed to import stock for product (non-404)', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'error_code' => $stockError->getHttpStatusCode(),
                        'error' => $stockError->getMessage(),
                    ]);
                } catch (\Exception $stockError) {
                    // Generic errors - log and continue
                    Log::warning('Failed to import stock for product', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'error' => $stockError->getMessage(),
                    ]);
                }

                $synced++;

                // Update progress every 10 products (FIX #1 - BUG #7)
                if (($index + 1) % 10 === 0 || ($index + 1) === $total) {
                    $syncJob?->updateProgress(
                        processedItems: $index + 1,
                        successfulItems: $synced,
                        failedItems: $errors
                    );

                    Log::debug('PullProductsFromPrestaShop PROGRESS', [
                        'shop_id' => $this->shop->id,
                        'processed' => $index + 1,
                        'total' => $total,
                        'synced' => $synced,
                        'errors' => $errors,
                    ]);
                }

            } catch (PrestaShopAPIException $e) {
                // BUG #8 FIX #1: GRACEFUL 404 HANDLING
                if ($e->isNotFound()) {
                    Log::warning('Product not found in PrestaShop (404), unlinking', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'shop_id' => $this->shop->id,
                        'prestashop_product_id' => $shopData->prestashop_product_id,
                        'action' => 'unlinked',
                    ]);

                    // Clear PrestaShop link - allow re-sync in future
                    $shopData->update([
                        'prestashop_product_id' => null,
                        'sync_status' => 'not_synced',
                        'last_sync_error' => 'Product deleted from PrestaShop (404)',
                    ]);

                    $errors++;
                    continue; // Skip to next product
                }

                // Other PrestaShop API errors (rate limit, auth, server error)
                Log::error('PrestaShop API error during pull', [
                    'product_id' => $product->id,
                    'shop_id' => $this->shop->id,
                    'error_code' => $e->getHttpStatusCode(),
                    'error_category' => $e->getErrorCategory(),
                    'error' => $e->getMessage(),
                ]);
                $errors++;
                continue;

            } catch (\Exception $e) {
                // Generic errors (database, network, etc.)
                Log::error('Failed to pull product from PrestaShop', [
                    'product_id' => $product->id,
                    'shop_id' => $this->shop->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getFile() . ':' . $e->getLine(),
                ]);
                $errors++;
            }
        }

            // Mark as completed (FIX #1 - BUG #7)
            $duration = now()->diffInSeconds($startTime);

            $syncJob?->updateProgress(
                processedItems: $total,
                successfulItems: $synced,
                failedItems: $errors
            );

            $syncJob?->complete([
                'synced' => $synced,
                'conflicts' => $conflicts,
                'prices_imported' => $pricesImported,
                'stock_imported' => $stockImported,
                'errors' => $errors,
            ]);

            Log::debug('PullProductsFromPrestaShop COMPLETED', [
                'shop_id' => $this->shop->id,
                'sync_job_id' => $syncJob?->id,
                'total_items' => $total,
                'synced' => $synced,
                'conflicts' => $conflicts,
                'prices_imported' => $pricesImported,
                'stock_imported' => $stockImported,
                'errors' => $errors,
                'duration_seconds' => $duration,
            ]);

        } catch (\Exception $e) {
            // Mark as failed (FIX #1 - BUG #7)
            $duration = now()->diffInSeconds($startTime);

            $syncJob?->fail(
                errorMessage: $e->getMessage(),
                errorDetails: $e->getFile() . ':' . $e->getLine(),
                stackTrace: $e->getTraceAsString()
            );

            Log::error('PullProductsFromPrestaShop FAILED', [
                'shop_id' => $this->shop->id,
                'sync_job_id' => $syncJob?->id,
                'error' => $e->getMessage(),
                'duration_seconds' => $duration,
            ]);

            throw $e;
        }
    }

    /**
     * Job failed permanently (after all retries)
     *
     * FIX #1 - BUG #7: Update SyncJob status
     */
    public function failed(\Throwable $exception): void
    {
        $syncJob = $this->getSyncJob();

        if ($syncJob) {
            $syncJob->fail(
                errorMessage: $exception->getMessage(),
                errorDetails: 'Job failed after ' . $this->attempts() . ' attempts',
                stackTrace: $exception->getTraceAsString()
            );
        }

        Log::error('PullProductsFromPrestaShop failed permanently', [
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'sync_job_id' => $syncJob?->id,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);
    }
}
