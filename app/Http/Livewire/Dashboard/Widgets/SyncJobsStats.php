<?php

namespace App\Http\Livewire\Dashboard\Widgets;

use Livewire\Component;
use App\Models\SyncJob;
use Illuminate\Support\Facades\Cache;

/**
 * SyncJobsStats Widget (Admin-only)
 *
 * Displays real-time sync job statistics including running/pending/failed
 * counts, completion rates, average duration, and success rate.
 * Polls every 30 seconds for near-real-time updates.
 */
class SyncJobsStats extends Component
{
    public array $metrics = [];

    public function mount(): void
    {
        $this->loadMetrics();
    }

    /**
     * Polled every 30s via wire:poll.30s in blade.
     */
    public function loadMetrics(): void
    {
        $this->metrics = Cache::remember('dashboard_sync_jobs_stats', 60, function () {
            return $this->calculateMetrics();
        });
    }

    public function refreshMetrics(): void
    {
        Cache::forget('dashboard_sync_jobs_stats');
        $this->loadMetrics();
    }

    protected function calculateMetrics(): array
    {
        $runningJobs = SyncJob::running()->count();
        $pendingJobs = SyncJob::pending()->count();
        $failedJobs = SyncJob::failed()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $completedToday = SyncJob::completed()
            ->whereDate('completed_at', today())
            ->count();
        $completedWeek = SyncJob::completed()
            ->where('completed_at', '>=', now()->startOfWeek())
            ->count();
        $completedMonth = SyncJob::completed()
            ->where('completed_at', '>=', now()->startOfMonth())
            ->count();
        $avgDuration = SyncJob::completed()
            ->whereDate('completed_at', today())
            ->avg('duration_seconds');

        // Success rate: completed vs failed in last 7 days
        $recentCompleted = SyncJob::completed()
            ->where('completed_at', '>=', now()->subDays(7))
            ->count();
        $recentFailed = $failedJobs;
        $total = $recentCompleted + $recentFailed;
        $successRate = $total > 0 ? round(($recentCompleted / $total) * 100, 1) : 100.0;

        return [
            'running_jobs' => $runningJobs,
            'pending_jobs' => $pendingJobs,
            'failed_jobs' => $failedJobs,
            'completed_today' => $completedToday,
            'completed_week' => $completedWeek,
            'completed_month' => $completedMonth,
            'avg_duration' => $avgDuration ? round($avgDuration) : 0,
            'success_rate' => $successRate,
        ];
    }

    /**
     * Format seconds into human-readable duration.
     */
    public function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }

        if ($seconds < 3600) {
            $min = floor($seconds / 60);
            $sec = $seconds % 60;
            return "{$min}m {$sec}s";
        }

        $hours = floor($seconds / 3600);
        $min = floor(($seconds % 3600) / 60);
        return "{$hours}h {$min}m";
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.sync-jobs-stats');
    }
}
