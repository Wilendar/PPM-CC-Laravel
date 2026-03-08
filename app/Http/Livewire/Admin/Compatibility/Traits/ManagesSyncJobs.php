<?php

namespace App\Http\Livewire\Admin\Compatibility\Traits;

use App\Models\JobProgress;
use App\Models\Product;
use App\Services\JobProgressService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Manages sync job dispatching, tracking, and status polling
 * for compatibility synchronization to PrestaShop.
 *
 * Requires the using class to provide:
 * - property: int|null $editingProductId
 * - method: saveCompatibility(): void
 * - method: getDefaultShopId(): ?int
 */
trait ManagesSyncJobs
{
    public array $syncJobIds = [];
    public array $syncJobStatuses = [];
    public ?int $syncCompletedAt = null;

    /**
     * Save compatibility and sync to PrestaShop
     */
    public function saveAndSync(): void
    {
        // First save the changes
        $this->saveCompatibility();

        // Reset job tracking arrays
        $this->syncJobIds = [];
        $this->syncJobStatuses = [];

        // Then trigger sync to PrestaShop (if product has shop associations)
        if ($this->editingProductId) {
            $product = Product::find($this->editingProductId);
            if ($product) {
                // Get all shops where product is published
                $shopData = $product->shopData()->with('shop')->get();
                $syncedShops = 0;
                $jobProgressService = app(JobProgressService::class);

                foreach ($shopData as $data) {
                    if ($data->shop && $data->is_published) {
                        // Generate unique job ID
                        $jobId = Str::uuid()->toString();

                        // Create PENDING job progress BEFORE dispatch
                        // NOTE: Using 'sync' type (not 'compat_sync') to match DB ENUM
                        $progressId = $jobProgressService->createPendingJobProgress(
                            $jobId,
                            $data->shop,
                            'sync',
                            1 // Single product sync
                        );

                        // Store job info for UI tracking
                        $this->syncJobIds[$data->shop->id] = $progressId;
                        $this->syncJobStatuses[$data->shop->id] = 'pending';

                        // Dispatch with pre-generated job ID for tracking
                        // Constructor: Product, Shop, userId, pendingMediaChanges, preGeneratedJobId
                        \App\Jobs\PrestaShop\SyncProductToPrestaShop::dispatch(
                            $product,
                            $data->shop,
                            auth()->id(), // userId
                            [], // pendingMediaChanges
                            $jobId // preGeneratedJobId
                        );
                        $syncedShops++;

                        Log::info('CompatibilityManagement: Dispatched sync job', [
                            'product_id' => $product->id,
                            'shop_id' => $data->shop->id,
                            'job_id' => $jobId,
                            'progress_id' => $progressId,
                        ]);
                    }
                }

                if ($syncedShops > 0) {
                    $this->dispatch('flash-message', message: "Dopasowania zapisane - synchronizacja z {$syncedShops} sklepami w toku...", type: 'info');
                } else {
                    $this->dispatch('flash-message', message: 'Dopasowania zapisane (produkt nie jest opublikowany w zadnym sklepie)', type: 'info');
                }
            }
        }
    }

    /**
     * Refresh sync job statuses (called by wire:poll)
     */
    public function refreshSyncStatus(): void
    {
        if (empty($this->syncJobIds)) {
            return;
        }

        $allCompleted = true;

        foreach ($this->syncJobIds as $shopId => $progressId) {
            $progress = JobProgress::find($progressId);

            if ($progress) {
                $this->syncJobStatuses[$shopId] = $progress->status;

                if (!in_array($progress->status, ['completed', 'failed'])) {
                    $allCompleted = false;
                }
            }
        }

        // If all jobs completed, mark completion time (don't clear immediately)
        if ($allCompleted && !empty($this->syncJobIds) && $this->syncCompletedAt === null) {
            $this->syncCompletedAt = time();

            // Show success message
            $completedCount = collect($this->syncJobStatuses)->filter(fn($s) => $s === 'completed')->count();
            $failedCount = collect($this->syncJobStatuses)->filter(fn($s) => $s === 'failed')->count();

            if ($failedCount > 0) {
                $this->dispatch('flash-message', message: "Synchronizacja zakonczona: {$completedCount} OK, {$failedCount} bledow", type: 'warning');
            } else {
                $this->dispatch('flash-message', message: "Synchronizacja zakonczona pomyslnie ({$completedCount} sklepow)", type: 'success');
            }
        }

        // Clear after 5 seconds of completion
        if ($this->syncCompletedAt !== null && (time() - $this->syncCompletedAt) >= 5) {
            $this->syncJobIds = [];
            $this->syncJobStatuses = [];
            $this->syncCompletedAt = null;
        }
    }

    /**
     * Check if any sync jobs are active or recently completed
     */
    public function hasSyncJobsActive(): bool
    {
        // Show badge if jobs are running OR completed within last 5 seconds
        return !empty($this->syncJobIds) || $this->syncCompletedAt !== null;
    }

    /**
     * Get overall sync status for display
     */
    public function getOverallSyncStatus(): string
    {
        if (empty($this->syncJobStatuses)) {
            return 'idle';
        }

        $statuses = array_values($this->syncJobStatuses);

        if (in_array('running', $statuses)) {
            return 'running';
        }
        if (in_array('pending', $statuses)) {
            return 'pending';
        }
        if (in_array('failed', $statuses)) {
            return 'failed';
        }

        return 'completed';
    }

    /**
     * Check if sync is currently in progress (pending or running)
     */
    public function isSyncInProgress(): bool
    {
        $status = $this->getOverallSyncStatus();
        return in_array($status, ['pending', 'running']);
    }
}
