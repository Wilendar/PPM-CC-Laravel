<?php

namespace App\Services;

use App\Models\JobProgress;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * JobProgressService
 *
 * Centralized service for managing job progress tracking
 *
 * FEATURES:
 * - Create job progress records with proper initialization
 * - Update progress in real-time (batch updates for efficiency)
 * - Error tracking with SKU-specific details
 * - Completion/failure status management
 * - Query active jobs for UI display
 *
 * USAGE:
 * ```php
 * // In queue job handle() method:
 * $service = app(JobProgressService::class);
 *
 * $progressId = $service->createJobProgress(
 *     $this->job->getJobId(),
 *     $shop,
 *     'import',
 *     100
 * );
 *
 * // Update every 5-10 products
 * $service->updateProgress($progressId, 50, [
 *     ['sku' => 'ABC123', 'error' => 'Product exists']
 * ]);
 *
 * // On completion
 * $service->markCompleted($progressId, [
 *     'imported' => 95,
 *     'skipped' => 5,
 * ]);
 * ```
 *
 * @package App\Services
 * @version 1.0
 * @since ETAP_07 - Real-Time Progress Tracking
 */
class JobProgressService
{
    /**
     * Create PENDING job progress tracking record BEFORE job dispatch
     *
     * This ensures progress bar appears IMMEDIATELY when user clicks import,
     * avoiding timing issues with wire:poll detection
     *
     * @param string $jobId Pre-generated UUID for job
     * @param PrestaShopShop $shop Target shop
     * @param string $jobType import|sync|export
     * @param int $totalCount Total items to process (estimate for pending status)
     * @return int Created JobProgress ID
     */
    public function createPendingJobProgress(
        string $jobId,
        PrestaShopShop $shop,
        string $jobType,
        int $totalCount = 0
    ): int {
        $progress = JobProgress::create([
            'job_id' => $jobId,
            'job_type' => $jobType,
            'shop_id' => $shop->id,
            'status' => 'pending',
            'current_count' => 0,
            'total_count' => $totalCount,
            'error_count' => 0,
            'error_details' => [],
            'started_at' => now(),
        ]);

        Log::info('PENDING job progress created (pre-dispatch)', [
            'progress_id' => $progress->id,
            'job_id' => $jobId,
            'job_type' => $jobType,
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
            'total_count' => $totalCount,
        ]);

        return $progress->id;
    }

    /**
     * Update pending progress to RUNNING status when job actually starts
     *
     * @param string $jobId Job UUID
     * @param int $actualTotalCount Actual total count after fetching products
     * @return int|null JobProgress ID if found, null if not exists
     */
    public function startPendingJob(string $jobId, int $actualTotalCount): ?int
    {
        $progress = JobProgress::where('job_id', $jobId)->first();

        if (!$progress) {
            Log::warning('startPendingJob: JobProgress not found', ['job_id' => $jobId]);
            return null;
        }

        $progress->update([
            'status' => 'running',
            'total_count' => $actualTotalCount,
        ]);

        Log::info('JobProgress updated: pending → running', [
            'progress_id' => $progress->id,
            'job_id' => $jobId,
            'total_count' => $actualTotalCount,
        ]);

        return $progress->id;
    }

    /**
     * Create new job progress record
     *
     * @param string $jobId Laravel queue job ID
     * @param PrestaShopShop|null $shop Target shop (nullable for non-shop operations like category delete)
     * @param string $jobType import|sync|export|category_delete
     * @param int $totalCount Total items to process
     * @return int Created JobProgress ID
     */
    public function createJobProgress(
        string $jobId,
        ?PrestaShopShop $shop,
        string $jobType,
        int $totalCount
    ): int {
        $progress = JobProgress::create([
            'job_id' => $jobId,
            'job_type' => $jobType,
            'shop_id' => $shop?->id, // Nullable for operations without shop context
            'status' => 'running',
            'current_count' => 0,
            'total_count' => $totalCount,
            'error_count' => 0,
            'error_details' => [],
            'started_at' => now(),
        ]);

        Log::info('Job progress tracking created', [
            'progress_id' => $progress->id,
            'job_id' => $jobId,
            'job_type' => $jobType,
            'shop_id' => $shop?->id,
            'shop_name' => $shop?->name ?? 'N/A (non-shop operation)',
            'total_count' => $totalCount,
        ]);

        return $progress->id;
    }

    /**
     * Update job progress with current count and errors
     *
     * PERFORMANCE: Use batch updates every 5-10 items instead of every item
     * to avoid database write bottlenecks
     *
     * @param int $progressId JobProgress record ID
     * @param int $currentCount Current processed items count
     * @param array $newErrors Optional array of errors: [['sku' => 'ABC', 'error' => 'msg'], ...]
     * @return bool Success status
     */
    public function updateProgress(int $progressId, int $currentCount, array $newErrors = []): bool
    {
        $progress = JobProgress::find($progressId);

        if (!$progress) {
            Log::error('JobProgress record not found for update', [
                'progress_id' => $progressId,
            ]);
            return false;
        }

        $success = $progress->updateProgress($currentCount, $newErrors);

        if (!empty($newErrors)) {
            Log::warning('Job progress updated with errors', [
                'progress_id' => $progressId,
                'job_id' => $progress->job_id,
                'current_count' => $currentCount,
                'total_count' => $progress->total_count,
                'new_errors_count' => count($newErrors),
                'total_errors' => $progress->error_count,
            ]);
        } else {
            Log::debug('Job progress updated', [
                'progress_id' => $progressId,
                'job_id' => $progress->job_id,
                'current_count' => $currentCount,
                'total_count' => $progress->total_count,
                'percentage' => $progress->progress_percentage,
            ]);
        }

        return $success;
    }

    /**
     * Mark job as completed with optional summary
     *
     * @param int $progressId JobProgress record ID
     * @param array $summary Optional completion summary (imported, skipped, etc.)
     * @return bool Success status
     */
    public function markCompleted(int $progressId, array $summary = []): bool
    {
        $progress = JobProgress::find($progressId);

        if (!$progress) {
            Log::error('JobProgress record not found for completion', [
                'progress_id' => $progressId,
            ]);
            return false;
        }

        $success = $progress->markCompleted($summary);

        Log::info('Job progress marked as completed', [
            'progress_id' => $progressId,
            'job_id' => $progress->job_id,
            'job_type' => $progress->job_type,
            'shop_id' => $progress->shop_id,
            'total_count' => $progress->total_count,
            'error_count' => $progress->error_count,
            'duration_seconds' => $progress->duration_seconds,
            'summary' => $summary,
        ]);

        return $success;
    }

    /**
     * Mark job as failed with error message
     *
     * @param int $progressId JobProgress record ID
     * @param string $errorMessage Error message
     * @param array $errorDetails Optional detailed error information
     * @return bool Success status
     */
    public function markFailed(int $progressId, string $errorMessage, array $errorDetails = []): bool
    {
        $progress = JobProgress::find($progressId);

        if (!$progress) {
            Log::error('JobProgress record not found for failure', [
                'progress_id' => $progressId,
            ]);
            return false;
        }

        $success = $progress->markFailed($errorMessage, $errorDetails);

        Log::error('Job progress marked as failed', [
            'progress_id' => $progressId,
            'job_id' => $progress->job_id,
            'job_type' => $progress->job_type,
            'shop_id' => $progress->shop_id,
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
        ]);

        return $success;
    }

    /**
     * Add single error to job progress
     *
     * USAGE: For tracking errors without updating current_count
     *
     * @param int $progressId JobProgress record ID
     * @param string $sku Product SKU or identifier
     * @param string $errorMessage Error message
     * @return bool Success status
     */
    public function addError(int $progressId, string $sku, string $errorMessage): bool
    {
        $progress = JobProgress::find($progressId);

        if (!$progress) {
            Log::error('JobProgress record not found for addError', [
                'progress_id' => $progressId,
            ]);
            return false;
        }

        return $progress->addError($sku, $errorMessage);
    }

    /**
     * Get all active jobs (running or pending)
     *
     * USAGE: For UI display of current operations
     *
     * @param int|null $shopId Optional filter by shop
     * @return Collection
     */
    public function getActiveJobs(?int $shopId = null): Collection
    {
        $query = JobProgress::with('shop:id,name')
            ->active()
            ->orderBy('started_at', 'desc');

        if ($shopId) {
            $query->forShop($shopId);
        }

        return $query->get();
    }

    /**
     * Get recent jobs (last 24 hours)
     *
     * USAGE: For job history display in UI
     *
     * @param int|null $shopId Optional filter by shop
     * @param string|null $jobType Optional filter by type (import/sync/export)
     * @return Collection
     */
    public function getRecentJobs(?int $shopId = null, ?string $jobType = null): Collection
    {
        $query = JobProgress::with('shop:id,name')
            ->recent()
            ->orderBy('created_at', 'desc');

        if ($shopId) {
            $query->forShop($shopId);
        }

        if ($jobType) {
            $query->ofType($jobType);
        }

        return $query->limit(20)->get();
    }

    /**
     * Get job progress by job ID
     *
     * @param string $jobId Laravel queue job ID
     * @return JobProgress|null
     */
    public function getProgressByJobId(string $jobId): ?JobProgress
    {
        return JobProgress::where('job_id', $jobId)
            ->with('shop:id,name')
            ->first();
    }

    /**
     * Get job progress summary for UI
     *
     * @param int $progressId JobProgress record ID
     * @return array|null
     */
    public function getProgressSummary(int $progressId): ?array
    {
        $progress = JobProgress::with('shop:id,name')->find($progressId);

        if (!$progress) {
            return null;
        }

        return $progress->getSummary();
    }

    /**
     * Get job progress data for UI (formatted for JobProgressBar component)
     *
     * @param int $progressId JobProgress record ID
     * @return array
     */
    public function getProgress(int $progressId): array
    {
        $progress = JobProgress::with('shop:id,name')->find($progressId);

        if (!$progress) {
            return [
                'status' => 'unknown',
                'message' => 'Nie znaleziono zadania',
                'current' => 0,
                'total' => 0,
                'percentage' => 0,
                'errors' => [],
                'shop_name' => 'Unknown',
            ];
        }

        // Format for JobProgressBar component
        return [
            'status' => $progress->status,
            'message' => $this->formatProgressMessage($progress),
            'current' => $progress->current_count,
            'total' => $progress->total_count,
            'percentage' => $progress->progress_percentage,
            'errors' => $progress->error_details ?? [], // Already cast to array in model
            'shop_name' => $progress->shop?->name ?? 'Unknown Shop',
            'job_id' => $progress->job_id, // For ErrorDetailsModal
        ];
    }

    /**
     * Format progress message for UI
     *
     * @param JobProgress $progress
     * @return string
     */
    private function formatProgressMessage(JobProgress $progress): string
    {
        $shopName = $progress->shop?->name ?? 'Unknown Shop';
        $current = $progress->current_count;
        $total = $progress->total_count;

        switch ($progress->status) {
            case 'running':
                return "Importowanie... {$current}/{$total} Produktów z {$shopName}";
            case 'completed':
                return "Ukończono! {$current}/{$total} Produktów z {$shopName}";
            case 'failed':
                return "Błąd importu z {$shopName}";
            case 'pending':
                return "Oczekiwanie... {$shopName}";
            default:
                return "Status nieznany";
        }
    }

    /**
     * Clean up old completed jobs (older than 7 days)
     *
     * USAGE: Call from scheduled command for maintenance
     *
     * @param int $daysOld Days threshold (default 7)
     * @return int Number of deleted records
     */
    public function cleanupOldJobs(int $daysOld = 7): int
    {
        $count = JobProgress::where('status', 'completed')
            ->where('completed_at', '<', now()->subDays($daysOld))
            ->delete();

        Log::info('Old job progress records cleaned up', [
            'days_threshold' => $daysOld,
            'deleted_count' => $count,
        ]);

        return $count;
    }

    /**
     * Get statistics for shop
     *
     * USAGE: For dashboard widgets showing shop sync statistics
     *
     * @param int $shopId Shop ID
     * @param int $days Days to include (default 7)
     * @return array Statistics
     */
    public function getShopStatistics(int $shopId, int $days = 7): array
    {
        $jobs = JobProgress::forShop($shopId)
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        return [
            'total_jobs' => $jobs->count(),
            'completed_jobs' => $jobs->where('status', 'completed')->count(),
            'failed_jobs' => $jobs->where('status', 'failed')->count(),
            'active_jobs' => $jobs->whereIn('status', ['pending', 'running'])->count(),
            'total_items_processed' => $jobs->sum('current_count'),
            'total_errors' => $jobs->sum('error_count'),
            'average_duration_seconds' => $jobs->where('status', 'completed')->avg('duration_seconds'),
        ];
    }
}
