<?php

namespace App\Jobs\PrestaShop;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductSyncStatus;
use App\Models\SyncLog;
use App\Services\PrestaShop\Sync\ProductSyncStrategy;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Carbon\Carbon;
use Throwable;

/**
 * Sync Product To PrestaShop Job
 *
 * Background job dla synchronizacji pojedynczego produktu PPM → PrestaShop
 *
 * Features:
 * - Unique jobs (prevents duplicate syncs)
 * - Exponential backoff retry strategy
 * - Priority support (high priority products first)
 * - Comprehensive error handling
 * - Integration with ProductSyncStrategy
 *
 * FAZA 1E: Queue Jobs - ETAP_07 PrestaShop Integration
 *
 * @package App\Jobs\PrestaShop
 */
class SyncProductToPrestaShop implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Product to sync
     */
    public Product $product;

    /**
     * Target PrestaShop shop
     */
    public PrestaShopShop $shop;

    /**
     * Number of times job may be attempted
     */
    public int $tries = 3;

    /**
     * Maximum seconds job can run before timing out
     */
    public int $timeout = 600; // 10 minutes (for large image uploads)

    /**
     * Unique job identifier (prevents duplicate syncs)
     */
    public function uniqueId(): string
    {
        return "product_{$this->product->id}_shop_{$this->shop->id}";
    }

    /**
     * How long unique lock should be maintained (seconds)
     */
    public int $uniqueFor = 3600; // 1 hour

    /**
     * Create new job instance
     */
    public function __construct(Product $product, PrestaShopShop $shop)
    {
        $this->product = $product;
        $this->shop = $shop;

        // Use default queue for CRON compatibility
        // Set queue based on priority
        // $priority = $this->getSyncPriority();
        // if ($priority <= 3) {
        //     $this->onQueue('prestashop_high');
        // } else {
        //     $this->onQueue('prestashop_sync');
        // }
    }

    /**
     * Execute the job
     */
    public function handle(
        ProductSyncStrategy $strategy,
        PrestaShopClientFactory $factory
    ): void {
        $startTime = microtime(true);

        Log::info('Product sync job started', [
            'job_id' => $this->job->getJobId(),
            'product_id' => $this->product->id,
            'product_sku' => $this->product->sku,
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Verify shop is active
            if (!$this->shop->is_active) {
                throw new \RuntimeException("Shop '{$this->shop->name}' is not active");
            }

            // Create API client
            $client = $factory->create($this->shop);

            // Execute sync through strategy
            $result = $strategy->syncToPrestaShop($this->product, $client, $this->shop);

            // Calculate execution time
            $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            // Log success
            Log::info('Product sync job completed successfully', [
                'job_id' => $this->job->getJobId(),
                'product_id' => $this->product->id,
                'shop_id' => $this->shop->id,
                'external_id' => $result['external_id'] ?? null,
                'operation' => $result['operation'] ?? 'unknown',
                'skipped' => $result['skipped'] ?? false,
                'execution_time_ms' => $executionTimeMs,
            ]);

        } catch (Throwable $e) {
            $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Product sync job failed', [
                'job_id' => $this->job->getJobId(),
                'product_id' => $this->product->id,
                'shop_id' => $this->shop->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'execution_time_ms' => $executionTimeMs,
            ]);

            // Re-throw to trigger Laravel retry mechanism
            throw $e;
        }
    }

    /**
     * Job failed permanently (after all retries)
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Product sync job failed permanently', [
            'product_id' => $this->product->id,
            'product_sku' => $this->product->sku,
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        // Update ProductSyncStatus to error
        $syncStatus = ProductSyncStatus::firstOrCreate(
            [
                'product_id' => $this->product->id,
                'shop_id' => $this->shop->id,
            ],
            [
                'sync_status' => ProductSyncStatus::STATUS_PENDING,
                'retry_count' => 0,
            ]
        );

        $syncStatus->update([
            'sync_status' => ProductSyncStatus::STATUS_ERROR,
            'error_message' => 'Job failed after ' . $this->attempts() . ' attempts: ' . $exception->getMessage(),
            'retry_count' => $this->attempts(),
        ]);

        // Create failure log
        SyncLog::create([
            'shop_id' => $this->shop->id,
            'product_id' => $this->product->id,
            'operation' => 'sync_product',
            'direction' => 'ppm_to_ps',
            'status' => 'error',
            'message' => 'Job failed permanently: ' . $exception->getMessage(),
            'created_at' => now(),
        ]);
    }

    /**
     * Time until job should be retried (exponential backoff)
     *
     * @return Carbon
     */
    public function retryUntil(): Carbon
    {
        return now()->addHours(24);
    }

    /**
     * Backoff delays between retries (seconds)
     *
     * @return array<int>
     */
    public function backoff(): array
    {
        return [30, 60, 300]; // 30s, 1min, 5min
    }

    /**
     * Get sync priority from ProductSyncStatus
     */
    private function getSyncPriority(): int
    {
        $syncStatus = ProductSyncStatus::where('product_id', $this->product->id)
            ->where('shop_id', $this->shop->id)
            ->first();

        return $syncStatus?->priority ?? ProductSyncStatus::PRIORITY_NORMAL;
    }
}
