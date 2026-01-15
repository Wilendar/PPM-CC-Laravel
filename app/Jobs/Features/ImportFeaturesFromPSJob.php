<?php

namespace App\Jobs\Features;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\PrestaShopShop;
use App\Services\JobProgressService;
use App\Services\PrestaShop\PrestaShop8Client;
use App\Services\PrestaShop\PrestaShopFeatureSyncService;
use App\Services\PrestaShop\Transformers\FeatureTransformer;

/**
 * ImportFeaturesFromPSJob
 *
 * ETAP_07e FAZA 4.3 - Import features from PrestaShop to PPM
 *
 * Features:
 * - Imports all features from PrestaShop shop
 * - Creates FeatureType and PrestashopFeatureMapping records
 * - Optional overwrite mode for existing features
 * - Progress tracking with JobProgressService
 *
 * @package App\Jobs\Features
 * @version 1.0
 * @since 2025-12-03
 */
class ImportFeaturesFromPSJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Target PrestaShop shop ID
     */
    public int $shopId;

    /**
     * Job progress ID for tracking
     */
    public ?int $jobProgressId;

    /**
     * User ID who triggered the job
     */
    public ?int $userId;

    /**
     * Whether to overwrite existing PPM features
     */
    public bool $overwriteExisting;

    /**
     * Number of times job may be attempted
     */
    public int $tries = 3;

    /**
     * Maximum seconds job can run
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Create new job instance
     *
     * @param int $shopId Target shop ID
     * @param int|null $jobProgressId Progress tracking ID
     * @param int|null $userId User who triggered (null = system)
     * @param bool $overwriteExisting Update existing features
     */
    public function __construct(
        int $shopId,
        ?int $jobProgressId = null,
        ?int $userId = null,
        bool $overwriteExisting = false
    ) {
        $this->shopId = $shopId;
        $this->jobProgressId = $jobProgressId;
        $this->userId = $userId;
        $this->overwriteExisting = $overwriteExisting;
    }

    /**
     * Execute the job
     *
     * @return void
     */
    public function handle(JobProgressService $progressService): void
    {
        $shop = PrestaShopShop::find($this->shopId);
        if (!$shop) {
            Log::error('[FEATURE IMPORT JOB] Shop not found', ['shop_id' => $this->shopId]);
            $this->markFailed($progressService, 'Shop not found');
            return;
        }

        Log::info('[FEATURE IMPORT JOB] Starting', [
            'shop_id' => $this->shopId,
            'shop_name' => $shop->name,
            'overwrite' => $this->overwriteExisting,
            'job_progress_id' => $this->jobProgressId,
        ]);

        // Mark job as running
        if ($this->jobProgressId) {
            $progressService->updateStatus($this->jobProgressId, 'running');
        }

        // Initialize services
        try {
            $client = new PrestaShop8Client($shop);
            $transformer = new FeatureTransformer();
            $syncService = new PrestaShopFeatureSyncService($client, $transformer);
        } catch (\Exception $e) {
            Log::error('[FEATURE IMPORT JOB] Failed to initialize services', [
                'error' => $e->getMessage(),
            ]);
            $this->markFailed($progressService, 'Service initialization failed: ' . $e->getMessage());
            return;
        }

        // Update progress - starting import
        if ($this->jobProgressId) {
            $progressService->updateProgress($this->jobProgressId, 0, []);
        }

        // Execute import
        try {
            $result = $syncService->importFeaturesFromPrestaShop($shop, $this->overwriteExisting);

            // Mark job complete
            if ($this->jobProgressId) {
                $progressService->markCompleted($this->jobProgressId, [
                    'imported' => $result['imported'],
                    'updated' => $result['updated'],
                    'skipped' => $result['skipped'],
                    'error_count' => count($result['errors']),
                ]);
            }

            Log::info('[FEATURE IMPORT JOB] Completed', [
                'shop_id' => $this->shopId,
                'imported' => $result['imported'],
                'updated' => $result['updated'],
                'skipped' => $result['skipped'],
                'errors' => count($result['errors']),
            ]);

        } catch (\Exception $e) {
            Log::error('[FEATURE IMPORT JOB] Import failed', [
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
            ]);
            $this->markFailed($progressService, $e->getMessage());
        }
    }

    /**
     * Mark job as failed
     *
     * @param JobProgressService $progressService
     * @param string $reason
     */
    protected function markFailed(JobProgressService $progressService, string $reason): void
    {
        if ($this->jobProgressId) {
            $progressService->markFailed($this->jobProgressId, $reason);
        }
    }

    /**
     * Handle job failure
     *
     * @param \Throwable $exception
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[FEATURE IMPORT JOB] Job failed', [
            'shop_id' => $this->shopId,
            'error' => $exception->getMessage(),
        ]);

        if ($this->jobProgressId) {
            $progressService = app(JobProgressService::class);
            $progressService->markFailed($this->jobProgressId, $exception->getMessage());
        }
    }
}
