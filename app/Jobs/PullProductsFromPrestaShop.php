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
use App\Services\SyncNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
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
class PullProductsFromPrestaShop implements ShouldQueue, ShouldBeUnique
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
     * Unique job lock duration in seconds
     *
     * JOB DEDUPLICATION: Lock per shop for 1 hour (scheduler frequency)
     * This prevents duplicate jobs for the same shop when scheduler runs
     * while previous job is still processing.
     *
     * Changed 2026-01-19: From 6h to 1h due to date_upd optimization
     * (unchanged products are skipped, so hourly sync is now efficient)
     *
     * @var int
     */
    public int $uniqueFor = 3600; // 1 hour

    /**
     * Get unique job identifier
     *
     * JOB DEDUPLICATION: Unique per shop, not per job instance
     * If job for shop_id=5 is already in queue/processing,
     * new dispatch for same shop will be ignored.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return "pull_products_shop_{$this->shop->id}";
    }

    /**
     * Create a new job instance.
     * ETAP_07 FAZA 9.2: Load dynamic settings (2025-11-13)
     * FIX 2025-12-22: Accept existing SyncJob to avoid duplicate job creation
     *
     * @param PrestaShopShop $shop Shop to pull data from
     * @param SyncJob|null $existingSyncJob Optional existing SyncJob (for SYNC NOW)
     */
    public function __construct(
        public PrestaShopShop $shop,
        ?SyncJob $existingSyncJob = null
    ) {
        // Load batch size and timeout from system settings
        $this->batchSize = \App\Models\SystemSetting::get('sync.batch_size', 10);
        $this->timeout = \App\Models\SystemSetting::get('sync.timeout', 300);

        // FIX 2025-12-22: Use existing SyncJob if provided (SYNC NOW scenario)
        // Otherwise create new one (scheduler/manual dispatch scenario)
        if ($existingSyncJob) {
            // SYNC NOW: Use existing pending job - don't create duplicate!
            $this->syncJobId = $existingSyncJob->id;

            Log::debug('PullProductsFromPrestaShop using existing SyncJob', [
                'sync_job_id' => $existingSyncJob->id,
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
            ]);
        } else {
            // Scheduler/manual: Create new SyncJob for tracking
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
            $skipped = 0; // 2026-01-19: date_upd optimization - unchanged products skipped

            // ============================================================
            // OPTIMIZATION 2026-01-19: Pre-fetch date_upd for change detection
            // ============================================================
            // Instead of fetching full product data for ALL products,
            // first fetch lightweight date_upd for all linked products.
            // If product hasn't changed in PrestaShop since last pull, skip it.
            // This significantly reduces API calls and database writes.

            $prestashopIds = $productsToSync->map(function ($product) {
                $shopData = $product->shopData()
                    ->where('shop_id', $this->shop->id)
                    ->first();
                return $shopData?->prestashop_product_id;
            })->filter()->unique()->values()->toArray();

            $dateUpdMap = [];
            if (!empty($prestashopIds)) {
                try {
                    $dateUpdMap = $client->getProductsDateUpd($prestashopIds);
                    Log::debug('PullProductsFromPrestaShop date_upd pre-fetch completed', [
                        'shop_id' => $this->shop->id,
                        'requested' => count($prestashopIds),
                        'returned' => count($dateUpdMap),
                    ]);
                } catch (\Exception $e) {
                    // If date_upd fetch fails, fall back to syncing all products
                    Log::warning('PullProductsFromPrestaShop date_upd fetch failed, will sync all', [
                        'shop_id' => $this->shop->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // ENHANCEMENT 2025-12-22: Extended tracking for validation purposes
            $fieldsUpdated = [
                'name' => 0,
                'slug' => 0,
                'short_description' => 0,
                'long_description' => 0,
                'meta_title' => 0,
                'meta_description' => 0,
                'weight' => 0,
                'height' => 0,
                'width' => 0,
                'length' => 0,
                'ean' => 0,
                'sku' => 0,
                'manufacturer' => 0,
                'is_active' => 0,
            ];
            $productsProcessed = [];

            foreach ($productsToSync as $index => $product) {
            try {
                $shopData = $product->shopData()
                    ->where('shop_id', $this->shop->id)
                    ->first();

                if (!$shopData || !$shopData->prestashop_product_id) {
                    continue;
                }

                // ============================================================
                // OPTIMIZATION 2026-01-19: Skip unchanged products
                // ============================================================
                // Check if product changed in PrestaShop since last pull
                // If date_upd is same as cached prestashop_updated_at, skip full fetch
                $psDateUpd = $dateUpdMap[$shopData->prestashop_product_id] ?? null;

                if ($psDateUpd && !$shopData->needsRePull($psDateUpd)) {
                    $skipped++;
                    Log::debug('Product skipped - unchanged in PrestaShop', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'prestashop_product_id' => $shopData->prestashop_product_id,
                        'cached_date_upd' => $shopData->prestashop_updated_at?->toDateTimeString(),
                        'ps_date_upd' => $psDateUpd,
                    ]);
                    continue;
                }

                Log::debug('Fetching product from PrestaShop', [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'prestashop_product_id' => $shopData->prestashop_product_id,
                    'shop_id' => $this->shop->id,
                    'reason' => $psDateUpd ? 'changed_since_last_pull' : 'no_date_upd_cache',
                ]);

                // Fetch from PrestaShop
                $psData = $client->getProduct($shopData->prestashop_product_id);

                if (isset($psData['product'])) {
                    $psData = $psData['product'];
                }

                // ENHANCEMENT 2025-12-22: ALWAYS store full PrestaShop data for validation
                // Validator needs ALL data to calculate % compatibility PPM vs PrestaShop
                $fullPsData = $conflictResolver->normalizeFullProductData($psData);

                // PROBLEM #9.3: RESOLVE CONFLICT FOR SYNC STATUS (2025-11-13)
                $resolution = $conflictResolver->resolve($shopData, $psData);

                Log::debug('Conflict resolution result', [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'should_update' => $resolution['should_update'],
                    'reason' => $resolution['reason'],
                    'has_conflicts' => !empty($resolution['conflicts']),
                ]);

                // ALWAYS store pulled PrestaShop data for validation purposes
                // Conflict resolution only affects sync_status, NOT the data itself
                $updateData = array_merge($fullPsData, [
                    'last_pulled_at' => now(),
                ]);

                if ($resolution['should_update']) {
                    // No conflicts - mark as synced
                    $updateData['sync_status'] = 'synced';
                    $updateData['has_conflicts'] = false;
                    $updateData['conflict_log'] = null;
                    $updateData['conflicts_detected_at'] = null;

                    $shopData->update($updateData);

                    Log::info('Product pulled from PrestaShop (synced)', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'reason' => $resolution['reason'],
                        'fields_updated' => array_keys($fullPsData),
                    ]);
                } else {
                    // Conflicts detected - store data but mark conflict status
                    if ($resolution['conflicts']) {
                        $updateData['sync_status'] = 'conflict';
                        $updateData['conflict_log'] = $resolution['conflicts'];
                        $updateData['has_conflicts'] = true;
                        $updateData['conflicts_detected_at'] = now();

                        $conflicts++;

                        Log::warning('Product pulled from PrestaShop (conflict detected)', [
                            'product_id' => $product->id,
                            'sku' => $product->sku,
                            'reason' => $resolution['reason'],
                            'conflicts_count' => count($resolution['conflicts']),
                            'fields_updated' => array_keys($fullPsData),
                        ]);
                    } else {
                        // No conflicts, strategy prevents sync (e.g., ppm_wins)
                        // Still store data for validation
                        Log::info('Product pulled from PrestaShop (strategy: ' . $resolution['reason'] . ')', [
                            'product_id' => $product->id,
                            'sku' => $product->sku,
                            'fields_updated' => array_keys($fullPsData),
                        ]);
                    }

                    $shopData->update($updateData);
                }

                // Track field updates for extended result_summary
                foreach ($fullPsData as $field => $value) {
                    if ($value !== null && isset($fieldsUpdated[$field])) {
                        $fieldsUpdated[$field]++;
                    }
                }

                // Track processed product details
                $productsProcessed[] = [
                    'sku' => $product->sku,
                    'prestashop_id' => $shopData->prestashop_product_id,
                    'status' => $resolution['should_update'] ? 'synced' : ($resolution['conflicts'] ? 'conflict' : 'strategy_skip'),
                    'fields_count' => count(array_filter($fullPsData, fn($v) => $v !== null)),
                ];

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

            // ENHANCEMENT 2025-12-22: Extended result_summary for validation
            // OPTIMIZATION 2026-01-19: Added skipped counter for date_upd optimization
            $syncJob?->complete([
                'synced' => $synced,
                'skipped' => $skipped,  // 2026-01-19: Unchanged products (date_upd optimization)
                'conflicts' => $conflicts,
                'prices_imported' => $pricesImported,
                'stock_imported' => $stockImported,
                'errors' => $errors,
                // Extended data for cykliczny job
                'fields_updated' => $fieldsUpdated,
                'total_fields_updated' => array_sum($fieldsUpdated),
                'products_details' => array_slice($productsProcessed, 0, 50), // Limit to 50 for storage
                'job_type' => 'pull_for_validation',
            ]);

            Log::info('PullProductsFromPrestaShop COMPLETED', [
                'shop_id' => $this->shop->id,
                'shop_name' => $this->shop->name,
                'sync_job_id' => $syncJob?->id,
                'total_items' => $total,
                'synced' => $synced,
                'skipped' => $skipped,  // 2026-01-19: date_upd optimization
                'conflicts' => $conflicts,
                'prices_imported' => $pricesImported,
                'stock_imported' => $stockImported,
                'errors' => $errors,
                'duration_seconds' => $duration,
                // ENHANCEMENT 2025-12-22: Extended statistics
                'total_fields_updated' => array_sum($fieldsUpdated),
                'fields_breakdown' => $fieldsUpdated,
            ]);

            // ENHANCEMENT 2.2.1.2.3: Send success notification
            if ($syncJob) {
                try {
                    app(SyncNotificationService::class)->sendSyncNotification($syncJob, 'success');
                } catch (\Exception $notifyError) {
                    Log::warning('Failed to send sync success notification', [
                        'error' => $notifyError->getMessage(),
                    ]);
                }
            }

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
     * ENHANCEMENT 2.2.1.2.3: Send failure notification
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

            // ENHANCEMENT 2.2.1.2.3: Send failure notification
            try {
                $event = $this->attempts() >= $this->tries ? 'retry_exhausted' : 'failure';
                app(SyncNotificationService::class)->sendSyncNotification($syncJob, $event);
            } catch (\Exception $notifyError) {
                Log::warning('Failed to send sync failure notification', [
                    'error' => $notifyError->getMessage(),
                ]);
            }
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
