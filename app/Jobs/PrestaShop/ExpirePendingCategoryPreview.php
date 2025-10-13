<?php

namespace App\Jobs\PrestaShop;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\CategoryPreview;
use App\Models\JobProgress;

/**
 * ExpirePendingCategoryPreview Job
 *
 * ETAP_07 FAZA 3D: Category Import Preview System - Timeout Mechanism
 *
 * Purpose: Automatically expire pending category previews after X minutes
 *
 * Workflow:
 * 1. Wait X minutes (default: 15 min = CategoryPreview::EXPIRATION_HOURS * 60)
 * 2. Check if CategoryPreview still in 'pending' status
 * 3. If yes → Mark as 'expired' + Update JobProgress to 'failed'
 * 4. If no → Preview already approved/rejected (skip)
 *
 * Features:
 * - Delayed job execution (delay(15 minutes))
 * - Prevents infinite "Oczekiwanie..." stuck jobs
 * - Automatic cleanup dla abandoned previews
 * - Updates both CategoryPreview AND JobProgress
 *
 * Usage:
 * ```php
 * // Dispatch from AnalyzeMissingCategories after preview created
 * ExpirePendingCategoryPreview::dispatch($previewId)
 *     ->delay(now()->addMinutes(15));
 * ```
 *
 * @package App\Jobs\PrestaShop
 * @version 1.0
 * @since 2025-10-09
 */
class ExpirePendingCategoryPreview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * CategoryPreview ID to check
     *
     * @var int
     */
    protected int $previewId;

    /**
     * Number of tries for the job
     *
     * @var int
     */
    public int $tries = 1;

    /**
     * Timeout for the job (1 minute)
     *
     * @var int
     */
    public int $timeout = 60;

    /**
     * Create a new job instance
     *
     * @param int $previewId CategoryPreview ID
     */
    public function __construct(int $previewId)
    {
        $this->previewId = $previewId;
    }

    /**
     * Execute the job
     *
     * Check if preview still pending → expire if yes
     *
     * @return void
     */
    public function handle(): void
    {
        Log::info('ExpirePendingCategoryPreview job started', [
            'preview_id' => $this->previewId,
        ]);

        try {
            $preview = CategoryPreview::find($this->previewId);

            if (!$preview) {
                Log::warning('ExpirePendingCategoryPreview: Preview not found (probably deleted)', [
                    'preview_id' => $this->previewId,
                ]);
                return;
            }

            // Check if still pending
            if ($preview->status !== CategoryPreview::STATUS_PENDING) {
                Log::info('ExpirePendingCategoryPreview: Preview already processed (skip expire)', [
                    'preview_id' => $this->previewId,
                    'current_status' => $preview->status,
                ]);
                return; // User already approved/rejected
            }

            // Expire preview
            $preview->update(['status' => CategoryPreview::STATUS_EXPIRED]);

            Log::info('ExpirePendingCategoryPreview: Preview marked as expired', [
                'preview_id' => $this->previewId,
                'job_id' => $preview->job_id,
            ]);

            // Update JobProgress to 'failed' (timeout)
            $jobProgress = JobProgress::where('job_id', $preview->job_id)->first();

            if ($jobProgress && $jobProgress->status === 'pending') {
                $jobProgress->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                ]);

                Log::info('ExpirePendingCategoryPreview: JobProgress marked as failed (timeout)', [
                    'job_progress_id' => $jobProgress->id,
                    'job_id' => $preview->job_id,
                ]);
            }

            Log::info('ExpirePendingCategoryPreview completed successfully', [
                'preview_id' => $this->previewId,
            ]);

        } catch (\Exception $e) {
            Log::error('ExpirePendingCategoryPreview job failed', [
                'preview_id' => $this->previewId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure
     *
     * @param \Throwable $exception
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ExpirePendingCategoryPreview job failed permanently', [
            'preview_id' => $this->previewId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Note: Preview will be cleaned up by scheduled cleanup command
    }
}
