<?php

namespace App\Http\Livewire\Components;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\JobProgressService;
use Illuminate\Support\Facades\Log;

/**
 * ActiveOperationsBar Component - ETAP_07c FAZA 2
 *
 * Aggregates and displays all active job progress bars in a unified container.
 * Shows multiple concurrent operations with expand/collapse functionality.
 *
 * Features:
 * - Real-time polling for active jobs (wire:poll.5s)
 * - Automatic discovery of running/pending jobs
 * - Optional shop filtering
 * - Collapse all / expand all controls
 * - Badge showing count of active operations
 *
 * Usage:
 * <livewire:components.active-operations-bar />
 * <livewire:components.active-operations-bar :shopId="$shopId" />
 *
 * @package App\Http\Livewire\Components
 * @version 1.0
 * @since ETAP_07c - Rich Progress Bar
 */
class ActiveOperationsBar extends Component
{
    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    /** @var int|null Optional shop filter */
    public ?int $shopId = null;

    /** @var array Active job progress IDs */
    public array $activeJobIds = [];

    /** @var bool Whether the entire bar is collapsed */
    public bool $isCollapsed = false;

    /** @var bool Whether to show completed jobs temporarily */
    public bool $showCompleted = true;

    /*
    |--------------------------------------------------------------------------
    | COMPONENT LIFECYCLE
    |--------------------------------------------------------------------------
    */

    /**
     * Mount component with optional shop filter
     */
    public function mount(?int $shopId = null): void
    {
        $this->shopId = $shopId;
        $this->refreshActiveJobs();
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Refresh list of active jobs
     * Called by wire:poll.5s
     */
    public function refreshActiveJobs(): void
    {
        try {
            $service = app(JobProgressService::class);

            // FIX (2025-12-02): Job types to HIDE from ActiveOperationsBar
            // media_pull creates one job per product, cluttering the UI
            $hiddenJobTypes = ['media_pull'];

            // Get active jobs (pending, running, awaiting_user)
            $activeJobs = $service->getActiveJobs($this->shopId)
                ->filter(fn($job) => !in_array($job->job_type, $hiddenJobTypes));

            // Also get recently completed (last 2 minutes) if showCompleted
            $recentCompleted = [];
            if ($this->showCompleted) {
                $recentCompleted = \App\Models\JobProgress::query()
                    ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId))
                    ->whereNotIn('job_type', $hiddenJobTypes) // FIX: exclude hidden types
                    ->whereIn('status', ['completed', 'failed'])
                    ->where('completed_at', '>=', now()->subMinutes(2))
                    ->pluck('id')
                    ->toArray();
            }

            // Merge active + recent completed
            $this->activeJobIds = array_merge(
                $activeJobs->pluck('id')->toArray(),
                $recentCompleted
            );

            // Remove duplicates and sort
            $this->activeJobIds = array_unique($this->activeJobIds);
            sort($this->activeJobIds);

        } catch (\Exception $e) {
            Log::error('ActiveOperationsBar: Failed to refresh jobs', [
                'error' => $e->getMessage(),
                'shop_id' => $this->shopId,
            ]);
            $this->activeJobIds = [];
        }
    }

    /**
     * Toggle collapsed state
     */
    public function toggleCollapse(): void
    {
        $this->isCollapsed = !$this->isCollapsed;
    }

    /**
     * Hide a specific job from the bar
     */
    public function hideJob(int $jobId): void
    {
        $this->activeJobIds = array_filter(
            $this->activeJobIds,
            fn($id) => $id !== $jobId
        );
    }

    /**
     * Clear all completed jobs from view
     */
    public function clearCompleted(): void
    {
        $completedIds = \App\Models\JobProgress::whereIn('id', $this->activeJobIds)
            ->whereIn('status', ['completed', 'failed'])
            ->pluck('id')
            ->toArray();

        $this->activeJobIds = array_filter(
            $this->activeJobIds,
            fn($id) => !in_array($id, $completedIds)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get count of active operations
     */
    public function getActiveCountProperty(): int
    {
        return count($this->activeJobIds);
    }

    /**
     * Check if there are any active operations
     */
    public function getHasActiveOperationsProperty(): bool
    {
        return $this->activeCount > 0;
    }

    /**
     * Get running jobs count (not pending/completed)
     */
    public function getRunningCountProperty(): int
    {
        if (empty($this->activeJobIds)) {
            return 0;
        }

        return \App\Models\JobProgress::whereIn('id', $this->activeJobIds)
            ->where('status', 'running')
            ->count();
    }

    /**
     * Get awaiting user count
     */
    public function getAwaitingCountProperty(): int
    {
        if (empty($this->activeJobIds)) {
            return 0;
        }

        return \App\Models\JobProgress::whereIn('id', $this->activeJobIds)
            ->where('status', 'awaiting_user')
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | EVENT LISTENERS - ETAP_07c FAZA 3
    |--------------------------------------------------------------------------
    */

    /**
     * Listen for new job started - immediately add to list
     * Dispatched from ProductList or other components when creating jobs
     */
    #[On('job-started')]
    public function handleJobStarted(int $progressId): void
    {
        Log::debug('ActiveOperationsBar: job-started received', ['progress_id' => $progressId]);

        // Check if job belongs to our shop filter (if set)
        if ($this->shopId) {
            $job = \App\Models\JobProgress::find($progressId);
            if (!$job || $job->shop_id !== $this->shopId) {
                return;
            }
        }

        // Add to list if not already present
        if (!in_array($progressId, $this->activeJobIds)) {
            $this->activeJobIds[] = $progressId;
            sort($this->activeJobIds);
        }

        // Auto-expand when new job arrives
        $this->isCollapsed = false;
    }

    /**
     * Listen for progress completed - refresh list
     * Dispatched from JobProgressBar when job completes
     */
    #[On('progress-completed')]
    public function handleProgressCompleted(int $progressId): void
    {
        Log::debug('ActiveOperationsBar: progress-completed received', ['progress_id' => $progressId]);

        // Optionally auto-clear completed after delay is handled in template
        // Just refresh the list to get latest states
        $this->refreshActiveJobs();
    }

    /**
     * Listen for job hidden by user - remove from list
     * Dispatched when user clicks close button
     */
    #[On('job-hidden')]
    public function handleJobHidden(int $progressId): void
    {
        Log::debug('ActiveOperationsBar: job-hidden received', ['progress_id' => $progressId]);
        $this->hideJob($progressId);
    }

    /**
     * Listen for force refresh - triggered externally
     * Useful when components need to force update the operations bar
     */
    #[On('refresh-active-operations')]
    public function handleForceRefresh(): void
    {
        Log::debug('ActiveOperationsBar: force refresh triggered');
        $this->refreshActiveJobs();
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
        return view('livewire.components.active-operations-bar');
    }
}
