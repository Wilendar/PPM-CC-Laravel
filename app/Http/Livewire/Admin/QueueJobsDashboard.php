<?php

namespace App\Http\Livewire\Admin;

use Livewire\Component;
use App\Services\QueueJobsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class QueueJobsDashboard extends Component
{
    public $filter = 'all'; // all, pending, processing, failed, stuck
    public $selectedQueue = 'all';

    protected $queueService;

    /**
     * Boot method for service injection (Livewire 3.x pattern)
     */
    public function boot(QueueJobsService $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * Render dashboard with jobs and stats
     */
    public function render()
    {
        $jobs = $this->getFilteredJobs();

        return view('livewire.admin.queue-jobs-dashboard', [
            'jobs' => $jobs,
            'stats' => $this->getStats(),
        ]);
    }

    /**
     * Get jobs based on current filter
     */
    private function getFilteredJobs()
    {
        return match($this->filter) {
            'all' => $this->queueService->getActiveJobs(),
            'pending' => $this->queueService->getActiveJobs()->where('status', 'pending'),
            'processing' => $this->queueService->getActiveJobs()->where('status', 'processing'),
            'failed' => $this->queueService->getFailedJobs(),
            'stuck' => $this->queueService->getStuckJobs(),
            default => $this->queueService->getActiveJobs(),
        };
    }

    /**
     * Calculate statistics for dashboard cards
     */
    private function getStats()
    {
        $active = $this->queueService->getActiveJobs();
        $failed = $this->queueService->getFailedJobs();
        $stuck = $this->queueService->getStuckJobs();

        return [
            'pending' => $active->where('status', 'pending')->count(),
            'processing' => $active->where('status', 'processing')->count(),
            'failed' => $failed->count(),
            'stuck' => $stuck->count(),
        ];
    }

    /**
     * Retry single failed job
     */
    public function retryJob($uuid)
    {
        try {
            $this->queueService->retryFailedJob($uuid);
            session()->flash('message', 'Job został dodany ponownie do kolejki');
        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas ponowienia job: ' . $e->getMessage());
        }
    }

    /**
     * Cancel pending job
     */
    public function cancelJob($id)
    {
        try {
            $this->queueService->cancelPendingJob($id);
            session()->flash('message', 'Job został anulowany');
        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas anulowania job: ' . $e->getMessage());
        }
    }

    /**
     * Delete failed job from database
     */
    public function deleteFailedJob($uuid)
    {
        try {
            $this->queueService->deleteFailedJob($uuid);
            session()->flash('message', 'Failed job został usunięty');
        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas usuwania failed job: ' . $e->getMessage());
        }
    }

    /**
     * Retry all failed jobs (bulk action)
     */
    public function retryAllFailed()
    {
        try {
            Artisan::call('queue:retry', ['id' => ['all']]);
            session()->flash('message', 'Wszystkie failed jobs zostały dodane ponownie');
        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas ponowienia wszystkich jobs: ' . $e->getMessage());
        }
    }

    /**
     * Clear all failed jobs (bulk action)
     */
    public function clearAllFailed()
    {
        try {
            DB::table('failed_jobs')->truncate();
            session()->flash('message', 'Wszystkie failed jobs zostały usunięte');
        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas czyszczenia failed jobs: ' . $e->getMessage());
        }
    }
}
