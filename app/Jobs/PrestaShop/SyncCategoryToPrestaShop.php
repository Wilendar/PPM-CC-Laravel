<?php

namespace App\Jobs\PrestaShop;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Category;
use App\Models\PrestaShopShop;
use App\Models\ShopMapping;
use App\Models\SyncLog;
use App\Services\PrestaShop\Sync\CategorySyncStrategy;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Carbon\Carbon;
use Throwable;

/**
 * Sync Category To PrestaShop Job
 *
 * Background job dla synchronizacji kategorii PPM → PrestaShop
 *
 * Features:
 * - Hierarchical sync (ensures parent exists first)
 * - Unique jobs (prevents duplicate syncs)
 * - Exponential backoff retry strategy
 * - Integration with CategorySyncStrategy
 *
 * FAZA 1E: Queue Jobs - ETAP_07 PrestaShop Integration
 *
 * @package App\Jobs\PrestaShop
 */
class SyncCategoryToPrestaShop implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Category to sync
     */
    public Category $category;

    /**
     * Target PrestaShop shop
     */
    public PrestaShopShop $shop;

    /**
     * Number of times job may be attempted
     */
    public int $tries = 3;

    /**
     * Maximum seconds job can run
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Unique job identifier
     */
    public function uniqueId(): string
    {
        return "category_{$this->category->id}_shop_{$this->shop->id}";
    }

    /**
     * How long unique lock should be maintained
     */
    public int $uniqueFor = 3600; // 1 hour

    /**
     * Create new job instance
     */
    public function __construct(Category $category, PrestaShopShop $shop)
    {
        $this->category = $category;
        $this->shop = $shop;
        // Use default queue for CRON compatibility
        // $this->onQueue('prestashop_sync');
    }

    /**
     * Execute the job
     */
    public function handle(
        CategorySyncStrategy $strategy,
        PrestaShopClientFactory $factory
    ): void {
        $startTime = microtime(true);

        Log::info('Category sync job started', [
            'job_id' => $this->job->getJobId(),
            'category_id' => $this->category->id,
            'category_name' => $this->category->name,
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Verify shop is active
            if (!$this->shop->is_active) {
                throw new \RuntimeException("Shop '{$this->shop->name}' is not active");
            }

            // Ensure parent category exists first (hierarchical sync)
            if ($this->category->parent_id) {
                $this->ensureParentExists();
            }

            // Create API client
            $client = $factory->create($this->shop);

            // Execute sync through strategy
            $result = $strategy->syncToPrestaShop($this->category, $client, $this->shop);

            // Calculate execution time
            $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            // Create success log
            SyncLog::create([
                'shop_id' => $this->shop->id,
                'product_id' => null,
                'operation' => 'sync_category',
                'direction' => 'ppm_to_ps',
                'status' => 'success',
                'message' => "Category {$result['operation']}d successfully",
                'execution_time_ms' => $executionTimeMs,
                'created_at' => now(),
            ]);

            Log::info('Category sync job completed successfully', [
                'job_id' => $this->job->getJobId(),
                'category_id' => $this->category->id,
                'shop_id' => $this->shop->id,
                'external_id' => $result['external_id'] ?? null,
                'operation' => $result['operation'] ?? 'unknown',
                'execution_time_ms' => $executionTimeMs,
            ]);

        } catch (Throwable $e) {
            $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Category sync job failed', [
                'job_id' => $this->job->getJobId(),
                'category_id' => $this->category->id,
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
     * Ensure parent category exists in PrestaShop (recursive)
     */
    private function ensureParentExists(): void
    {
        if (!$this->category->parent_id) {
            return;
        }

        $parent = $this->category->parent;
        if (!$parent) {
            Log::warning('Parent category not found', [
                'category_id' => $this->category->id,
                'parent_id' => $this->category->parent_id,
            ]);
            return;
        }

        // Check if parent already mapped
        $parentMapping = ShopMapping::where('shop_id', $this->shop->id)
            ->where('mapping_type', 'category')
            ->where('ppm_value', (string) $parent->id)
            ->first();

        if ($parentMapping) {
            // Parent exists, nothing to do
            return;
        }

        // Parent doesn't exist, dispatch parent sync job first
        Log::info('Dispatching parent category sync', [
            'category_id' => $this->category->id,
            'parent_id' => $parent->id,
            'parent_name' => $parent->name,
            'shop_id' => $this->shop->id,
        ]);

        // Dispatch parent sync job synchronously (wait for completion)
        SyncCategoryToPrestaShop::dispatchSync($parent, $this->shop);
    }

    /**
     * Job failed permanently (after all retries)
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Category sync job failed permanently', [
            'category_id' => $this->category->id,
            'category_name' => $this->category->name,
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        // Create failure log
        SyncLog::create([
            'shop_id' => $this->shop->id,
            'product_id' => null,
            'operation' => 'sync_category',
            'direction' => 'ppm_to_ps',
            'status' => 'error',
            'message' => 'Job failed permanently: ' . $exception->getMessage(),
            'created_at' => now(),
        ]);
    }

    /**
     * Time until job should be retried
     */
    public function retryUntil(): Carbon
    {
        return now()->addHours(24);
    }

    /**
     * Backoff delays between retries (seconds)
     */
    public function backoff(): array
    {
        return [30, 60, 300]; // 30s, 1min, 5min
    }
}
