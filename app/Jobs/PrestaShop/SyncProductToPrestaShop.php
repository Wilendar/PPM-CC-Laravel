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
use App\Models\ProductShopData;
use App\Models\SyncLog;
use App\Models\SyncJob;
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
     * User ID who triggered the sync (2025-11-07)
     * NULL = SYSTEM (scheduled/automated sync)
     */
    public ?int $userId = null;

    /**
     * Pending media changes from session (2025-12-02)
     * Format: ['mediaId:shopId' => 'sync'|'unsync', ...]
     * Session is not available in queue context, so we pass it explicitly
     */
    public array $pendingMediaChanges = [];

    /**
     * Pre-generated job progress ID for tracking (2025-12-09)
     * Used by CompatibilityManagement to track sync status
     */
    public ?string $preGeneratedJobId = null;

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
     * ETAP_07 FAZA 9.2: Load dynamic settings (2025-11-13)
     * ETAP_07d (2025-12-02): Added pendingMediaChanges for media sync
     * ETAP_05d (2025-12-09): Added preGeneratedJobId for compat sync tracking
     *
     * @param Product $product Product to sync
     * @param PrestaShopShop $shop Target shop
     * @param int|null $userId User who triggered sync (NULL = SYSTEM)
     * @param array $pendingMediaChanges Pending media changes from session
     * @param string|null $preGeneratedJobId Pre-generated job ID for tracking
     */
    public function __construct(
        Product $product,
        PrestaShopShop $shop,
        ?int $userId = null,
        array $pendingMediaChanges = [],
        ?string $preGeneratedJobId = null
    ) {
        $this->product = $product;
        $this->shop = $shop;
        $this->userId = $userId;
        $this->pendingMediaChanges = $pendingMediaChanges;
        $this->preGeneratedJobId = $preGeneratedJobId;

        // Load timeout from system settings
        $this->timeout = \App\Models\SystemSetting::get('sync.timeout', 300);

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
        $startMemory = memory_get_peak_usage(true);

        // Create SyncJob record (OPTION 1 FIX - 2025-11-07)
        // USER_ID FIX (2025-11-07): Use $this->userId instead of auth()->id()
        // auth()->id() returns NULL in queue context!
        $syncJob = SyncJob::create([
            'job_id' => \Str::uuid(),
            'job_type' => SyncJob::JOB_PRODUCT_SYNC,
            'job_name' => "Sync Product #{$this->product->id} to {$this->shop->name}",
            'source_type' => SyncJob::TYPE_PPM,
            'source_id' => $this->product->id,
            'target_type' => SyncJob::TYPE_PRESTASHOP,
            'target_id' => $this->shop->id,
            'status' => SyncJob::STATUS_PENDING,
            'trigger_type' => SyncJob::TRIGGER_EVENT, // Auto-dispatched after save
            'user_id' => $this->userId, // Captured in web context, NULL = SYSTEM
            'queue_name' => $this->queue ?? 'default',
            'queue_job_id' => $this->job->getJobId(), // CRITICAL LINK!
            'queue_attempts' => $this->attempts(),
            'total_items' => 1,
            'processed_items' => 0,
            'successful_items' => 0,
            'failed_items' => 0,
            'scheduled_at' => now(),
        ]);

        // Start job tracking
        $syncJob->start();

        // FIX 2026-02-05: Clear "early sync flag" from cache now that SyncJob exists
        // This flag was set in ProductForm before dispatch() to enable immediate "syncing" icon
        $cacheKey = "product_sync_pending:{$this->product->id}";
        $earlySyncFlags = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
        $earlySyncFlags = array_filter($earlySyncFlags, function ($flag) {
            return !($flag['type'] === 'shop' && (int) $flag['target_id'] === $this->shop->id);
        });
        if (empty($earlySyncFlags)) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
        } else {
            \Illuminate\Support\Facades\Cache::put($cacheKey, array_values($earlySyncFlags), 60);
        }

        // Update pre-generated JobProgress if exists (CompatibilityManagement tracking)
        if ($this->preGeneratedJobId) {
            $this->updateJobProgress('running');
        }

        Log::info('Product sync job started', [
            'job_id' => $this->job->getJobId(),
            'sync_job_id' => $syncJob->id,
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
            // ETAP_07d (2025-12-02): Pass pendingMediaChanges for media sync
            $result = $strategy->syncToPrestaShop($this->product, $client, $this->shop, $this->pendingMediaChanges);

            // Calculate performance metrics
            $duration = microtime(true) - $startTime;
            $memoryPeakMb = (memory_get_peak_usage(true) - $startMemory) / 1024 / 1024;

            // Update SyncJob with success
            $syncJob->updateProgress(1, 1, 0);
            $syncJob->updatePerformanceMetrics(
                memoryPeakMb: (int) round($memoryPeakMb),
                cpuTimeSeconds: $duration,
                apiCallsMade: 1 // One API call per product sync
            );

            // Pass full $result to complete() including synced_data and changed_fields (2025-11-07)
            $syncJob->complete($result);

            // FIX 2026-02-05: Invalidate ProductStatusAggregator cache so ProductList shows updated status immediately
            // Without this, the 5-minute cache TTL would keep showing "syncing" status after job completion
            app(\App\Services\Product\ProductStatusAggregator::class)->invalidateCache($this->product->id);

            // Update pre-generated JobProgress if exists (CompatibilityManagement tracking)
            if ($this->preGeneratedJobId) {
                $this->updateJobProgress('completed');
            }

            Log::info('Product sync job completed successfully', [
                'job_id' => $this->job->getJobId(),
                'sync_job_id' => $syncJob->id,
                'product_id' => $this->product->id,
                'shop_id' => $this->shop->id,
                'external_id' => $result['external_id'] ?? null,
                'operation' => $result['operation'] ?? 'unknown',
                'skipped' => $result['skipped'] ?? false,
                'duration_seconds' => round($duration, 2),
                'memory_peak_mb' => round($memoryPeakMb, 2),
            ]);

        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;
            $memoryPeakMb = (memory_get_peak_usage(true) - $startMemory) / 1024 / 1024;

            // Update SyncJob with failure
            $syncJob->updateProgress(1, 0, 1);
            $syncJob->updatePerformanceMetrics(
                memoryPeakMb: (int) round($memoryPeakMb),
                cpuTimeSeconds: $duration
            );
            $syncJob->fail(
                errorMessage: $e->getMessage(),
                errorDetails: $e->getFile() . ':' . $e->getLine(),
                stackTrace: $e->getTraceAsString()
            );

            // FIX 2026-02-05: Invalidate ProductStatusAggregator cache so ProductList shows updated status immediately
            app(\App\Services\Product\ProductStatusAggregator::class)->invalidateCache($this->product->id);

            // Update pre-generated JobProgress if exists (CompatibilityManagement tracking)
            if ($this->preGeneratedJobId) {
                $this->updateJobProgress('failed');
            }

            Log::error('Product sync job failed', [
                'job_id' => $this->job->getJobId(),
                'sync_job_id' => $syncJob->id,
                'product_id' => $this->product->id,
                'shop_id' => $this->shop->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'duration_seconds' => round($duration, 2),
            ]);

            // Re-throw to trigger Laravel retry mechanism
            throw $e;
        }
    }

    /**
     * Update pre-generated JobProgress record (for CompatibilityManagement tracking)
     */
    protected function updateJobProgress(string $status): void
    {
        if (!$this->preGeneratedJobId) {
            return;
        }

        try {
            \App\Models\JobProgress::where('job_id', $this->preGeneratedJobId)
                ->update([
                    'status' => $status,
                    'current_count' => $status === 'completed' ? 1 : 0,
                    'completed_at' => in_array($status, ['completed', 'failed']) ? now() : null,
                ]);
        } catch (\Exception $e) {
            Log::warning('Failed to update JobProgress', [
                'job_id' => $this->preGeneratedJobId,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
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

        // Update ProductShopData to error (CONSOLIDATED 2025-10-13)
        $shopData = ProductShopData::firstOrCreate(
            [
                'product_id' => $this->product->id,
                'shop_id' => $this->shop->id,
            ],
            [
                'sync_status' => ProductShopData::STATUS_PENDING,
                'retry_count' => 0,
            ]
        );

        $shopData->update([
            'sync_status' => ProductShopData::STATUS_ERROR,
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
     * Get sync priority from ProductShopData (CONSOLIDATED 2025-10-13)
     *
     * Updated to use ProductShopData instead of deprecated ProductSyncStatus
     */
    private function getSyncPriority(): int
    {
        $shopData = ProductShopData::where('product_id', $this->product->id)
            ->where('shop_id', $this->shop->id)
            ->first();

        return $shopData?->priority ?? ProductShopData::PRIORITY_NORMAL;
    }
}
