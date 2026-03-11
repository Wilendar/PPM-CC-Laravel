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
                'pending_conflicts' => [], // 2025-10-13: Category conflict detection
            ];
        }

        $result = [
            'status' => $progress->status,
            'message' => $this->formatProgressMessage($progress),
            'current' => $progress->current_count,
            'total' => $progress->total_count,
            'percentage' => $progress->progress_percentage,
            'errors' => $progress->error_details ?? [], // Already cast to array in model
            'shop_name' => $progress->shop?->name ?? 'Unknown Shop',
            'job_id' => $progress->job_id, // For ErrorDetailsModal
            'job_type' => $progress->job_type, // ETAP_07c: For UI differentiation
            'job_type_label' => $progress->getJobTypeLabel(), // ETAP_07c: Human-readable label
            'user_name' => $progress->user?->name ?? null, // ETAP_07c: Who initiated
            'metadata' => $progress->metadata ?? [], // ETAP_07c: Rich context
            'action_button' => $progress->action_button, // ETAP_07c: UI action button
            'has_action_button' => $progress->hasActionButton(), // ETAP_07c: Quick check
            'started_at' => $progress->started_at?->toIso8601String(), // ETAP_07c FAZA 2: For accordion duration
            'pending_conflicts' => [], // Default: no conflicts
        ];

        // === CONFLICT DETECTION (2025-10-13) ===
        // When import completes, check for products needing resolution
        if ($progress->status === 'completed' && $progress->job_type === 'import' && $progress->shop_id) {
            $conflicts = \App\Models\ProductShopData::where('shop_id', $progress->shop_id)
                ->where('requires_resolution', true)
                ->orderBy('updated_at', 'desc')
                ->limit(10) // Limit to first 10 conflicts
                ->get(['product_id', 'shop_id', 'conflict_data', 'conflict_detected_at']);

            if ($conflicts->isNotEmpty()) {
                $result['pending_conflicts'] = $conflicts->map(function ($shopData) {
                    return [
                        'product_id' => $shopData->product_id,
                        'shop_id' => $shopData->shop_id,
                        'conflict_type' => $shopData->conflict_data['type'] ?? 'unknown',
                        'detected_at' => $shopData->conflict_detected_at,
                    ];
                })->toArray();

                Log::info('Pending conflicts detected for completed import', [
                    'progress_id' => $progressId,
                    'shop_id' => $progress->shop_id,
                    'conflict_count' => count($result['pending_conflicts']),
                ]);
            }
        }

        return $result;
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
        $jobType = $progress->job_type;

        // Job type specific messages (ETAP_07c)
        $jobTypeLabels = [
            'import' => 'Importowanie',
            'sync' => 'Synchronizacja',
            'export' => 'Eksportowanie',
            'category_delete' => 'Usuwanie kategorii',
            'category_analysis' => 'Analiza kategorii',
            'bulk_export' => 'Eksport masowy',
            'bulk_update' => 'Aktualizacja masowa',
            'stock_sync' => 'Synchronizacja stanow',
            'price_sync' => 'Synchronizacja cen',
        ];

        $typeLabel = $jobTypeLabels[$jobType] ?? 'Operacja';

        switch ($progress->status) {
            case 'running':
                if ($jobType === 'category_analysis') {
                    return "Analizuje kategorie... {$current}/{$total} produktow z {$shopName}";
                }
                return "{$typeLabel}... {$current}/{$total} z {$shopName}";

            case 'completed':
                return "Ukonczone! {$current}/{$total} z {$shopName}";

            case 'failed':
                return "Blad: {$typeLabel} z {$shopName}";

            case 'pending':
                if ($jobType === 'category_analysis') {
                    return "Przygotowanie analizy kategorii... {$shopName}";
                }
                return "Oczekiwanie... {$shopName}";

            case 'awaiting_user':
                $awaitingMessage = $progress->getMetadataValue('awaiting_message', '');
                if ($awaitingMessage) {
                    return $awaitingMessage;
                }
                if ($jobType === 'category_analysis') {
                    return "Analiza zakonczona - kliknij aby zobaczyc wyniki ({$shopName})";
                }
                return "Wymaga akcji uzytkownika ({$shopName})";

            default:
                return "Status nieznany";
        }
    }

    /**
     * Mark job as awaiting user action (ETAP_07c)
     *
     * Sets status to 'awaiting_user' and adds action button for user to click
     *
     * @param int $progressId JobProgress record ID
     * @param string $buttonType Action type (e.g., 'preview', 'confirm', 'retry')
     * @param string $buttonLabel Button label for UI
     * @param string $actionRoute Route/action identifier
     * @param array $actionParams Route parameters
     * @param string $message Optional message to display
     * @return bool Success status
     */
    public function markAwaitingUser(
        int $progressId,
        string $buttonType,
        string $buttonLabel,
        string $actionRoute,
        array $actionParams = [],
        string $message = ''
    ): bool {
        $progress = JobProgress::find($progressId);

        if (!$progress) {
            Log::error('JobProgress record not found for markAwaitingUser', [
                'progress_id' => $progressId,
            ]);
            return false;
        }

        // Set awaiting_user status
        $progress->markAwaitingUser($message);

        // Set action button
        $progress->setActionButton($buttonType, $buttonLabel, $actionRoute, $actionParams);

        Log::info('Job progress marked as awaiting_user with action button', [
            'progress_id' => $progressId,
            'job_id' => $progress->job_id,
            'job_type' => $progress->job_type,
            'button_type' => $buttonType,
            'button_label' => $buttonLabel,
            'action_route' => $actionRoute,
        ]);

        return true;
    }

    /**
     * Create category analysis job progress (ETAP_07c)
     *
     * @param string $jobId Pre-generated UUID for job
     * @param PrestaShopShop $shop Target shop
     * @param int $productCount Number of products to analyze
     * @param int|null $userId User who initiated the job
     * @param array $metadata Additional context (mode, options, etc.)
     * @return int Created JobProgress ID
     */
    public function createCategoryAnalysisProgress(
        string $jobId,
        PrestaShopShop $shop,
        int $productCount,
        ?int $userId = null,
        array $metadata = []
    ): int {
        $progress = JobProgress::create([
            'job_id' => $jobId,
            'job_type' => 'category_analysis',
            'shop_id' => $shop->id,
            'user_id' => $userId,
            'status' => 'pending',
            'current_count' => 0,
            'total_count' => $productCount,
            'error_count' => 0,
            'error_details' => [],
            'metadata' => array_merge($metadata, [
                'shop_name' => $shop->name,
                'initiated_at' => now()->toDateTimeString(),
            ]),
            'started_at' => now(),
        ]);

        Log::info('Category analysis job progress created', [
            'progress_id' => $progress->id,
            'job_id' => $jobId,
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
            'product_count' => $productCount,
            'user_id' => $userId,
        ]);

        return $progress->id;
    }

    /**
     * Update job metadata (ETAP_07c)
     *
     * @param int $progressId JobProgress record ID
     * @param array $newMetadata Metadata to merge
     * @return bool Success status
     */
    public function updateMetadata(int $progressId, array $newMetadata): bool
    {
        $progress = JobProgress::find($progressId);

        if (!$progress) {
            Log::error('JobProgress record not found for updateMetadata', [
                'progress_id' => $progressId,
            ]);
            return false;
        }

        return $progress->updateMetadata($newMetadata);
    }

    /**
     * Get jobs awaiting user action (ETAP_07c)
     *
     * @param int|null $userId Optional filter by user
     * @param int|null $shopId Optional filter by shop
     * @return Collection
     */
    public function getAwaitingUserJobs(?int $userId = null, ?int $shopId = null): Collection
    {
        $query = JobProgress::with(['shop:id,name', 'user:id,name'])
            ->awaitingUser()
            ->orderBy('updated_at', 'desc');

        if ($userId) {
            $query->forUser($userId);
        }

        if ($shopId) {
            $query->forShop($shopId);
        }

        return $query->get();
    }

    /**
     * Get jobs requiring attention (awaiting_user or with errors) (ETAP_07c)
     *
     * @param int|null $shopId Optional filter by shop
     * @return Collection
     */
    public function getJobsRequiringAttention(?int $shopId = null): Collection
    {
        $query = JobProgress::with(['shop:id,name', 'user:id,name'])
            ->requiringAttention()
            ->orderBy('updated_at', 'desc');

        if ($shopId) {
            $query->forShop($shopId);
        }

        return $query->limit(10)->get();
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
