<?php

declare(strict_types=1);

namespace App\Jobs\VisualEditor;

use App\Models\ProductDescription;
use App\Services\VisualEditor\CssSyncOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to sync product CSS to PrestaShop via FTP.
 *
 * ETAP_07h v2.0 CSS-FIRST Architecture:
 * - Syncs css_rules from ProductDescription to uve-custom.css
 * - Uses lock mechanism (prevents concurrent sync)
 * - Backup and rollback on error
 * - FULL REPLACE strategy with markers
 *
 * @package App\Jobs\VisualEditor
 */
class SyncProductCssJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying.
     */
    public int $backoff = 10;

    /**
     * Number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Product description ID.
     */
    protected int $descriptionId;

    /**
     * Force re-fetch CSS from server.
     */
    protected bool $forceFetch;

    /**
     * Create a new job instance.
     */
    public function __construct(int $descriptionId, bool $forceFetch = false)
    {
        $this->descriptionId = $descriptionId;
        $this->forceFetch = $forceFetch;

        // Use prestashop queue for FTP operations
        $this->onQueue('prestashop');
    }

    /**
     * Execute the job.
     */
    public function handle(CssSyncOrchestrator $orchestrator): void
    {
        $description = ProductDescription::find($this->descriptionId);

        if (!$description) {
            Log::warning('SyncProductCssJob: Description not found', [
                'description_id' => $this->descriptionId,
            ]);
            return;
        }

        // Check if css_mode is 'pending' - means FTP not configured, skip sync
        if ($description->css_mode === 'pending') {
            Log::info('SyncProductCssJob: Skipping - css_mode is pending (FTP not configured)', [
                'description_id' => $this->descriptionId,
                'product_id' => $description->product_id,
                'shop_id' => $description->shop_id,
            ]);
            return;
        }

        // Check if there are any CSS rules to sync
        if (empty($description->css_rules)) {
            Log::info('SyncProductCssJob: Skipping - no CSS rules to sync', [
                'description_id' => $this->descriptionId,
                'product_id' => $description->product_id,
            ]);
            return;
        }

        Log::info('SyncProductCssJob: Starting CSS sync', [
            'description_id' => $this->descriptionId,
            'product_id' => $description->product_id,
            'shop_id' => $description->shop_id,
            'rules_count' => count($description->css_rules),
        ]);

        $result = $orchestrator->syncProductDescription($description, $this->forceFetch);

        if ($result['status'] === CssSyncOrchestrator::STATUS_SUCCESS) {
            Log::info('SyncProductCssJob: CSS sync completed successfully', [
                'description_id' => $this->descriptionId,
                'product_id' => $description->product_id,
                'generated_size' => $result['details']['generated_size'] ?? 0,
            ]);
        } elseif ($result['status'] === CssSyncOrchestrator::STATUS_SKIPPED) {
            Log::info('SyncProductCssJob: CSS sync skipped', [
                'description_id' => $this->descriptionId,
                'reason' => $result['message'] ?? 'Unknown',
            ]);
        } else {
            Log::error('SyncProductCssJob: CSS sync failed', [
                'description_id' => $this->descriptionId,
                'product_id' => $description->product_id,
                'error' => $result['error'] ?? $result['message'] ?? 'Unknown error',
            ]);

            // Throw exception to trigger retry
            throw new \RuntimeException('CSS sync failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncProductCssJob: Job failed permanently', [
            'description_id' => $this->descriptionId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Get unique job ID for preventing duplicates.
     */
    public function uniqueId(): string
    {
        return 'sync_css_' . $this->descriptionId;
    }

    /**
     * Determine number of seconds before unique lock is released.
     */
    public function uniqueFor(): int
    {
        return 300; // 5 minutes
    }
}
