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
            ];
            return;
        }

        try {
            $service = app(JobProgressService::class);
            $this->progress = $service->getProgress($this->jobId);

            // Check completion status
            if (isset($this->progress['status']) && in_array($this->progress['status'], ['completed', 'failed'])) {
                if (!$this->isCompleted) {
                    $this->isCompleted = true;

                    // Auto-hide after 60 seconds (1 minute)
                    $this->dispatch('progress-completed', progressId: $this->jobId);
                }
            }

            Log::debug('JobProgressBar: Progress fetched', [
                'job_id' => $this->jobId,
                'progress' => $this->progress,
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
            ];
        }
    }

    /**
     * Hide progress bar manually
     */
    public function hide(): void
    {
        $this->isVisible = false;
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
            return "Importowanie... {$current}/{$total} Produktów z {$shopName}";
        }

        if ($status === 'completed') {
            return "Ukończono! {$current}/{$total} Produktów z {$shopName}";
        }

        if ($status === 'failed') {
            return "Błąd importu z {$shopName}";
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
