<?php

namespace App\Jobs\PrestaShop;

use App\Models\AttributeType;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopAttributeSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

/**
 * Sync Attribute Group With PrestaShop Job
 *
 * Background job dla synchronizacji AttributeType → PrestaShop attribute_group
 *
 * Features:
 * - Unique jobs (prevents duplicate syncs)
 * - Exponential backoff retry strategy (30s, 1min, 5min)
 * - Comprehensive error handling
 * - Integration with PrestaShopAttributeSyncService
 * - Updates prestashop_attribute_group_mapping table
 *
 * ETAP_05b Phase 2.1: Queue Jobs - Variant System
 *
 * @package App\Jobs\PrestaShop
 */
class SyncAttributeGroupWithPrestaShop implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * AttributeType to sync
     */
    public AttributeType $attributeType;

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
    public int $timeout = 300; // 5 minutes

    /**
     * Unique job identifier (prevents duplicate syncs)
     */
    public function uniqueId(): string
    {
        return "attribute_group_{$this->attributeType->id}_shop_{$this->shop->id}";
    }

    /**
     * How long unique lock should be maintained (seconds)
     */
    public int $uniqueFor = 3600; // 1 hour

    /**
     * Create new job instance
     */
    public function __construct(AttributeType $attributeType, PrestaShopShop $shop)
    {
        $this->attributeType = $attributeType;
        $this->shop = $shop;
    }

    /**
     * Execute the job
     */
    public function handle(PrestaShopAttributeSyncService $syncService): void
    {
        $startTime = microtime(true);

        Log::info('Attribute group sync job started', [
            'job_id' => $this->job->getJobId(),
            'attribute_type_id' => $this->attributeType->id,
            'attribute_type_name' => $this->attributeType->name,
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Verify shop is active
            if (!$this->shop->is_active) {
                throw new \RuntimeException("Shop '{$this->shop->name}' is not active");
            }

            // Execute sync through service
            $result = $syncService->syncAttributeGroup($this->attributeType->id, $this->shop->id);

            // Calculate execution time
            $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            // Log success
            Log::info('Attribute group sync job completed successfully', [
                'job_id' => $this->job->getJobId(),
                'attribute_type_id' => $this->attributeType->id,
                'shop_id' => $this->shop->id,
                'status' => $result['status'],
                'ps_id' => $result['ps_id'] ?? null,
                'message' => $result['message'] ?? null,
                'execution_time_ms' => $executionTimeMs,
            ]);

        } catch (Throwable $e) {
            $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Attribute group sync job failed', [
                'job_id' => $this->job->getJobId(),
                'attribute_type_id' => $this->attributeType->id,
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
        Log::error('Attribute group sync job failed permanently', [
            'attribute_type_id' => $this->attributeType->id,
            'attribute_type_name' => $this->attributeType->name,
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        // Update mapping status to 'conflict'
        DB::table('prestashop_attribute_group_mapping')->updateOrInsert(
            [
                'attribute_type_id' => $this->attributeType->id,
                'prestashop_shop_id' => $this->shop->id,
            ],
            [
                'sync_status' => 'conflict',
                'sync_notes' => 'Job failed after ' . $this->attempts() . ' attempts: ' . $exception->getMessage(),
                'is_synced' => false,
                'last_synced_at' => now(),
                'updated_at' => now(),
            ]
        );
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
