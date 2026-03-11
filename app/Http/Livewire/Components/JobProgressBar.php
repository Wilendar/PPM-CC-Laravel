<?php

namespace App\Http\Livewire\Components;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\JobProgressService;
use Illuminate\Support\Facades\Log;

/**
 * JobProgressBar Component - Real-time job progress tracking
 *
 * Features:
 * - Real-time polling (wire:poll.3s)
 * - Animated progress bar with percentage
 * - Status indicators (running, completed, failed)
 * - Error count badge with click handler
 * - Auto-hide after completion
 * - Shop-specific filtering (optional)
 *
 * Usage:
 * <livewire:components.job-progress-bar :jobId="$jobId" />
 * <livewire:components.job-progress-bar :jobId="$jobId" :shopId="$shopId" />
 *
 * @package App\Http\Livewire\Components
 * @version 1.0
 * @since Real-Time Progress Tracking Feature
 */
class JobProgressBar extends Component
{
    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    public ?int $jobId = null;
    public ?int $shopId = null;

    // Progress state
    public array $progress = [];
    public bool $isVisible = true;
    public bool $isCompleted = false;

    // ETAP_07c: Track if user already took action (hide button, show processing state)
    public bool $userActionTaken = false;

    /*
    |--------------------------------------------------------------------------
    | COMPONENT LIFECYCLE
    |--------------------------------------------------------------------------
    */

    /**
     * Mount component with job progress record ID
     */
    public function mount(int $jobId, ?int $shopId = null): void
    {
        $this->jobId = (int) $jobId;
        $this->shopId = $shopId;

        // Initial progress load
        $this->fetchProgress();
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Fetch current progress from JobProgressService
     *
     * Called by wire:poll.3s for real-time updates
     */
    public function fetchProgress(): void
    {
        // Safety check - should never happen if mount() is called correctly
        if ($this->jobId === null) {
            Log::error('JobProgressBar: jobId is null in fetchProgress()');
            $this->progress = [
                'status' => 'error',
                'message' => 'Brak ID postępu zadania',
                'current' => 0,
                'total' => 100,
                'percentage' => 0,
                'errors' => [],
                'pending_conflicts' => [],
            ];
            return;
        }

        try {
            $service = app(JobProgressService::class);
            $this->progress = $service->getProgress($this->jobId);

            // Check completion status
            // ETAP_07c: awaiting_user is NOT completed - user action required!
            if (isset($this->progress['status']) && in_array($this->progress['status'], ['completed', 'failed'])) {
                if (!$this->isCompleted) {
                    $this->isCompleted = true;

                    // Auto-hide after 60 seconds (1 minute)
                    $this->dispatch('progress-completed', progressId: $this->jobId);
                }
            }

            // ETAP_07c: Check for awaiting_user status (requires user action)
            if (isset($this->progress['status']) && $this->progress['status'] === 'awaiting_user') {
                // Do NOT mark as completed - keep visible until user takes action
                Log::debug('JobProgressBar: awaiting_user status detected', [
                    'job_id' => $this->jobId,
                    'has_action_button' => $this->progress['has_action_button'] ?? false,
                ]);
            }

            // FIX (2025-12-02): Read user_action_taken from DB metadata for cross-user sync
            $userActionFromDb = $this->progress['metadata']['user_action_taken'] ?? false;
            if ($userActionFromDb && !$this->userActionTaken) {
                $this->userActionTaken = true;
                Log::debug('JobProgressBar: user_action_taken synced from DB', [
                    'job_id' => $this->jobId,
                ]);
            }

            Log::debug('JobProgressBar: Progress fetched', [
                'job_id' => $this->jobId,
                'userActionTaken' => $this->userActionTaken,
            ]);

        } catch (\Exception $e) {
            Log::error('JobProgressBar: Failed to fetch progress', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
            ]);

            $this->progress = [
                'status' => 'error',
                'message' => 'Nie udało się pobrać postępu zadania',
                'current' => 0,
                'total' => 100,
                'percentage' => 0,
                'errors' => [],
                'pending_conflicts' => [],
            ];
        }
    }

    /**
     * Hide progress bar manually (just visual hide, does NOT cancel job)
     * ETAP_07c FAZA 3: Dispatches job-hidden event for ActiveOperationsBar
     *
     * @deprecated Use cancelJob() instead if you want to actually stop the job
     */
    public function hide(): void
    {
        $this->isVisible = false;

        // Notify ActiveOperationsBar to remove from list
        $this->dispatch('job-hidden', progressId: $this->jobId);

        Log::debug('JobProgressBar: hidden by user (visual only)', ['job_id' => $this->jobId]);
    }

    /**
     * Cancel job and hide progress bar
     *
     * This method actually cancels the job in the database (marks as 'cancelled')
     * and hides the progress bar. Use this when user wants to abort the job.
     *
     * FIX (2025-12-10): Added proper job cancellation instead of just hiding
     */
    public function cancelJob(): void
    {
        if ($this->jobId === null) {
            Log::error('JobProgressBar: Cannot cancel - jobId is null');
            return;
        }

        try {
            $jobProgress = \App\Models\JobProgress::find($this->jobId);

            if ($jobProgress) {
                // Only cancel if job is still running/pending/awaiting_user
                if (in_array($jobProgress->status, ['running', 'pending', 'awaiting_user'])) {
                    $jobProgress->status = 'cancelled';
                    $jobProgress->completed_at = now();
                    $jobProgress->updateMetadata(['cancelled_by_user' => true, 'cancelled_at' => now()->toDateTimeString()]);
                    $jobProgress->save();

                    Log::info('JobProgressBar: Job CANCELLED by user', [
                        'progress_id' => $this->jobId,
                        'job_id' => $jobProgress->job_id,
                        'previous_status' => $jobProgress->getOriginal('status'),
                    ]);

                    // FIX (2025-12-10): Also cancel corresponding SyncJob record
                    // SyncJob uses same job_id (UUID) as JobProgress
                    $this->cancelSyncJobByJobId($jobProgress->job_id);
                } else {
                    Log::debug('JobProgressBar: Job already finished, just hiding', [
                        'progress_id' => $this->jobId,
                        'status' => $jobProgress->status,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('JobProgressBar: Failed to cancel job', [
                'progress_id' => $this->jobId,
                'error' => $e->getMessage(),
            ]);
        }

        // Hide the progress bar
        $this->isVisible = false;

        // Notify ActiveOperationsBar to remove from list
        $this->dispatch('job-hidden', progressId: $this->jobId);
        $this->dispatch('job-cancelled', progressId: $this->jobId);
    }

    /**
     * Cancel SyncJob record by job_id (UUID)
     *
     * FIX (2025-12-10): SyncJob table is used by /admin/shops/sync panel
     * and needs to be synchronized with JobProgress status
     */
    protected function cancelSyncJobByJobId(?string $jobId): void
    {
        if (empty($jobId)) {
            return;
        }

        try {
            $syncJob = \App\Models\SyncJob::where('job_id', $jobId)->first();

            if ($syncJob && in_array($syncJob->status, [
                \App\Models\SyncJob::STATUS_PENDING,
                \App\Models\SyncJob::STATUS_RUNNING,
            ])) {
                $syncJob->status = \App\Models\SyncJob::STATUS_CANCELLED;
                $syncJob->completed_at = now();
                $syncJob->error_message = 'Cancelled by user';
                $syncJob->save();

                Log::info('JobProgressBar: SyncJob also CANCELLED', [
                    'sync_job_id' => $syncJob->id,
                    'job_id' => $jobId,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('JobProgressBar: Failed to cancel SyncJob (non-critical)', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Show error details modal
     */
    public function showErrors(): void
    {
        if (!empty($this->progress['errors'])) {
            $this->dispatch('show-error-details', [
                'jobId' => $this->progress['job_id'] ?? '',
                'errors' => $this->progress['errors'],
                'shop_name' => $this->progress['shop_name'] ?? 'Unknown Shop',
            ]);
        }
    }

    /**
     * Resolve single conflict - open CategoryConflictModal
     * ONLY for single product imports
     */
    public function resolveConflict(): void
    {
        $conflicts = $this->progress['pending_conflicts'] ?? [];

        if (count($conflicts) === 1) {
            $conflict = $conflicts[0];

            Log::info('Manual conflict resolution triggered', [
                'product_id' => $conflict['product_id'],
                'shop_id' => $conflict['shop_id'],
                'conflict_type' => $conflict['conflict_type'],
            ]);

            // Dispatch event to show CategoryConflictModal
            $this->dispatch('showCategoryConflict',
                productId: $conflict['product_id'],
                shopId: $conflict['shop_id']
            );
        }
    }

    /**
     * ETAP_07c: Handle action button click
     *
     * Dispatches event to parent component based on action_button config
     */
    public function handleActionButton(): void
    {
        $actionButton = $this->progress['action_button'] ?? null;

        if (!$actionButton) {
            Log::warning('JobProgressBar: handleActionButton called but no action_button in progress');
            return;
        }

        $buttonType = $actionButton['type'] ?? 'unknown';
        $route = $actionButton['route'] ?? '';
        $params = $actionButton['params'] ?? [];

        Log::info('JobProgressBar: Action button clicked', [
            'job_id' => $this->jobId,
            'button_type' => $buttonType,
            'route' => $route,
            'params' => $params,
        ]);

        // Dispatch event based on button type
        switch ($buttonType) {
            case 'preview':
                // Open CategoryPreviewModal - dispatch correct event that modal listens to
                // ETAP_07c FIX: Use 'show-category-preview' (modal's #[On] listener)
                $this->dispatch('show-category-preview',
                    previewId: $params['preview_id'] ?? null
                );
                break;

            case 'retry':
                // Retry failed job
                $this->dispatch('retryJob', jobId: $this->jobId);
                break;

            case 'view_details':
                // View job details
                $this->dispatch('viewJobDetails', jobId: $this->jobId);
                break;

            default:
                // Generic action - dispatch with route name
                $this->dispatch($route, ...$params);
                break;
        }
    }

    /**
     * Download CSV with conflicted products SKUs
     * For bulk imports
     */
    public function downloadConflictsCsv()
    {
        $conflicts = $this->progress['pending_conflicts'] ?? [];

        if (empty($conflicts)) {
            return;
        }

        // Get products with SKUs
        $productIds = array_column($conflicts, 'product_id');
        $products = \App\Models\Product::whereIn('id', $productIds)
            ->get(['id', 'sku', 'name']);

        // Build CSV data
        $csvData = "SKU,Product Name,Product ID,Shop ID,Conflict Type,Detected At\n";

        foreach ($conflicts as $conflict) {
            $product = $products->firstWhere('id', $conflict['product_id']);

            if ($product) {
                $csvData .= sprintf(
                    '"%s","%s",%d,%d,%s,%s' . "\n",
                    $product->sku,
                    $product->name,
                    $conflict['product_id'],
                    $conflict['shop_id'],
                    $conflict['conflict_type'],
                    $conflict['detected_at'] ?? 'N/A'
                );
            }
        }

        Log::info('Conflicts CSV download triggered', [
            'shop_id' => $this->shopId,
            'conflicts_count' => count($conflicts),
        ]);

        // Return download response (Livewire 3.x method)
        return response()->streamDownload(function () use ($csvData) {
            echo $csvData;
        }, 'conflicts_' . now()->format('Y-m-d_His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get progress percentage (0-100)
     */
    public function getPercentageProperty(): int
    {
        return $this->progress['percentage'] ?? 0;
    }

    /**
     * Get current status
     */
    public function getStatusProperty(): string
    {
        return $this->progress['status'] ?? 'pending';
    }

    /**
     * Get status message
     */
    public function getMessageProperty(): string
    {
        $status = $this->status;
        $current = $this->progress['current'] ?? 0;
        $total = $this->progress['total'] ?? 0;
        $shopName = $this->progress['shop_name'] ?? 'Unknown Shop';

        if ($status === 'running') {
            return "Importowanie... {$current}/{$total} Produktow z {$shopName}";
        }

        if ($status === 'completed') {
            return "Ukonczone! {$current}/{$total} Produktow z {$shopName}";
        }

        if ($status === 'failed') {
            return "Blad importu z {$shopName}";
        }

        // ETAP_07c FIX: Handle awaiting_user status
        if ($status === 'awaiting_user') {
            // If user already took action, show processing message
            if ($this->userActionTaken) {
                return "Przetwarzanie wybranych kategorii - {$total} produktow z {$shopName}";
            }

            $actionLabel = $this->progress['action_button']['label'] ?? 'Wymaga akcji';
            return "{$actionLabel} - {$total} produktow z {$shopName}";
        }

        return "Oczekiwanie...";
    }

    /**
     * Get error count
     */
    public function getErrorCountProperty(): int
    {
        return count($this->progress['errors'] ?? []);
    }

    /**
     * Check if should auto-hide
     */
    public function getShouldHideProperty(): bool
    {
        return $this->isCompleted && !$this->isVisible;
    }

    /**
     * Get conflicts count
     */
    public function getConflictCountProperty(): int
    {
        return count($this->progress['pending_conflicts'] ?? []);
    }

    /**
     * Check if has single conflict (button enabled)
     */
    public function getHasSingleConflictProperty(): bool
    {
        return $this->conflictCount === 1;
    }

    /**
     * Check if has bulk conflicts (CSV download)
     */
    public function getHasBulkConflictsProperty(): bool
    {
        return $this->conflictCount > 1;
    }

    /*
    |--------------------------------------------------------------------------
    | ETAP_07c: ACTION BUTTON COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * ETAP_07c: Check if job is awaiting user action
     */
    public function getIsAwaitingUserProperty(): bool
    {
        return ($this->progress['status'] ?? '') === 'awaiting_user';
    }

    /**
     * ETAP_07c: Check if has action button
     */
    public function getHasActionButtonProperty(): bool
    {
        return $this->progress['has_action_button'] ?? false;
    }

    /**
     * ETAP_07c: Get action button config
     */
    public function getActionButtonProperty(): ?array
    {
        return $this->progress['action_button'] ?? null;
    }

    /**
     * ETAP_07c: Get action button label
     */
    public function getActionButtonLabelProperty(): string
    {
        return $this->actionButton['label'] ?? 'Akcja';
    }

    /**
     * ETAP_07c: Check if user already took action (should hide button)
     */
    public function getIsUserActionTakenProperty(): bool
    {
        return $this->userActionTaken;
    }

    /**
     * ETAP_07c: Get job type label (human readable)
     */
    public function getJobTypeLabelProperty(): string
    {
        return $this->progress['job_type_label'] ?? ucfirst($this->progress['job_type'] ?? 'Job');
    }

    /**
     * ETAP_07c: Get user who initiated the job
     */
    public function getInitiatedByProperty(): ?string
    {
        return $this->progress['user_name'] ?? $this->progress['metadata']['initiated_by'] ?? null;
    }

    /**
     * ETAP_07c: Get current phase label from metadata
     */
    public function getPhaseLabelProperty(): ?string
    {
        return $this->progress['metadata']['phase_label'] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | ETAP_07c FAZA 2: ACCORDION COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * ETAP_07c FAZA 2: Get job type string
     */
    public function getJobTypeProperty(): string
    {
        return $this->progress['job_type'] ?? 'unknown';
    }

    /**
     * ETAP_07c FAZA 2: Get formatted duration since job started
     */
    public function getDurationProperty(): ?string
    {
        $startedAt = $this->progress['started_at'] ?? null;

        if (!$startedAt) {
            return null;
        }

        try {
            $started = \Carbon\Carbon::parse($startedAt);
            $now = now();
            $diff = $started->diff($now);

            if ($diff->h > 0) {
                return sprintf('%dh %dm', $diff->h, $diff->i);
            }
            if ($diff->i > 0) {
                return sprintf('%dm %ds', $diff->i, $diff->s);
            }
            return sprintf('%ds', $diff->s);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * ETAP_07c FAZA 2: Get shop name
     */
    public function getShopNameProperty(): ?string
    {
        return $this->progress['shop_name'] ?? null;
    }

    /**
     * ETAP_07c FAZA 2: Get shortened job ID (first 8 chars of UUID)
     */
    public function getJobIdShortProperty(): string
    {
        $jobId = $this->progress['job_id'] ?? '';
        return strlen($jobId) > 8 ? substr($jobId, 0, 8) . '...' : $jobId;
    }

    /**
     * ETAP_07c FAZA 2: Get current count
     */
    public function getCurrentCountProperty(): int
    {
        return $this->progress['current'] ?? 0;
    }

    /**
     * ETAP_07c FAZA 2: Get total count
     */
    public function getTotalCountProperty(): int
    {
        return $this->progress['total'] ?? 0;
    }

    /**
     * ETAP_07c FAZA 2: Get formatted started_at timestamp
     */
    public function getStartedAtFormattedProperty(): ?string
    {
        $startedAt = $this->progress['started_at'] ?? null;

        if (!$startedAt) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($startedAt)->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * ETAP_07c FAZA 2: Get sample product SKUs from metadata
     *
     * Metadata structure: { "sample_skus": ["SKU001", "SKU002", ...] }
     */
    public function getProductsSampleProperty(): array
    {
        return $this->progress['metadata']['sample_skus'] ?? [];
    }

    /**
     * ETAP_07c FAZA 2: Get metadata details for accordion display
     *
     * Filters metadata to show only user-friendly key-value pairs
     */
    public function getMetadataDetailsProperty(): array
    {
        $metadata = $this->progress['metadata'] ?? [];
        $details = [];

        // Map of internal keys to display labels
        $displayMap = [
            'mode' => 'Tryb',
            'import_mode' => 'Tryb importu',
            'export_mode' => 'Tryb eksportu',
            'filter' => 'Filtr',
            'category_filter' => 'Kategoria',
            'stock_filter' => 'Stan magazynowy',
            'price_filter' => 'Cena',
            'batch_size' => 'Rozmiar paczki',
            'priority' => 'Priorytet',
            'source' => 'Zrodlo',
        ];

        foreach ($displayMap as $key => $label) {
            if (isset($metadata[$key]) && $metadata[$key] !== null && $metadata[$key] !== '') {
                $value = $metadata[$key];

                // Format boolean values
                if (is_bool($value)) {
                    $value = $value ? 'Tak' : 'Nie';
                }

                $details[$label] = $value;
            }
        }

        return $details;
    }

    /*
    |--------------------------------------------------------------------------
    | EVENT LISTENERS
    |--------------------------------------------------------------------------
    */

    /**
     * Listen for external progress updates
     */
    #[On('job-progress-updated.{jobId}')]
    public function handleProgressUpdate(): void
    {
        $this->fetchProgress();
    }

    /**
     * ETAP_07c: Listen for user action completed event
     *
     * Dispatched by CategoryPreviewModal after user approves/rejects categories.
     * Hides the action button and shows "Przetwarzanie..." state.
     *
     * FIX (2025-12-02): Now saves to database so ALL users see updated state
     *
     * @param string $jobId UUID of the job
     */
    #[On('user-action-completed')]
    public function handleUserActionCompleted(string $jobId): void
    {
        // Check if this event is for this progress bar (match by UUID)
        $currentJobId = $this->progress['job_id'] ?? null;

        if ($currentJobId === $jobId) {
            // FIX (2025-12-02): Save to DB so ALL users see the same state
            $jobProgress = \App\Models\JobProgress::find($this->jobId);
            if ($jobProgress) {
                $jobProgress->markUserActionTaken();
            }

            $this->userActionTaken = true;

            Log::info('JobProgressBar: User action completed - saved to DB', [
                'progress_id' => $this->jobId,
                'job_id' => $jobId,
                'userActionTaken' => true,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | COMPONENT RENDER
    |--------------------------------------------------------------------------
    */

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.components.job-progress-bar');
    }
}
