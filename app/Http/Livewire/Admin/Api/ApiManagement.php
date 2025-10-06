<?php

namespace App\Http\Livewire\Admin\Api;

use App\Services\ApiMonitoringService;
use App\Models\ApiUsageLog;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class ApiManagement extends Component
{
    use WithPagination;

    public $activeTab = 'overview';
    public $dateFrom = '';
    public $dateTo = '';
    public $selectedEndpoint = '';
    public $selectedStatus = '';
    public $showSuspiciousOnly = false;
    public $showSlowOnly = false;
    
    // Auto-refresh settings
    public $autoRefresh = false;
    public $refreshInterval = 30; // seconds

    protected $listeners = [
        'refreshData' => 'loadData',
    ];

    public function mount()
    {
        $this->dateFrom = now()->subHours(24)->format('Y-m-d\TH:i');
        $this->dateTo = now()->format('Y-m-d\TH:i');
    }

    public function render()
    {
        $apiService = app(ApiMonitoringService::class);
        $startDate = Carbon::parse($this->dateFrom);
        $endDate = Carbon::parse($this->dateTo);
        
        return view('livewire.admin.api.api-management', [
            'healthStatus' => $apiService->getHealthStatus(),
            'statistics' => $apiService->getUsageStatistics($startDate, $endDate),
            'endpointStats' => $apiService->getEndpointStatistics($startDate, $endDate),
            'userStats' => $apiService->getUserStatistics($startDate, $endDate),
            'responseTimePercentiles' => $apiService->getResponseTimePercentiles($startDate, $endDate),
            'topErrors' => $apiService->getTopErrorEndpoints(10, $startDate, $endDate),
            'suspiciousActivity' => $apiService->getSuspiciousActivitySummary($startDate, $endDate),
            'hourlyDistribution' => $apiService->getHourlyDistribution(now()),
            'dailyTrends' => $apiService->getDailyTrends(7),
            'recentLogs' => $this->getRecentLogs(),
        ]);
    }

    /**
     * Get recent API logs with filters
     */
    protected function getRecentLogs()
    {
        $query = ApiUsageLog::with('user:id,name,email')
            ->orderBy('requested_at', 'desc');

        // Apply date filter
        if ($this->dateFrom) {
            $query->where('requested_at', '>=', Carbon::parse($this->dateFrom));
        }

        if ($this->dateTo) {
            $query->where('requested_at', '<=', Carbon::parse($this->dateTo));
        }

        // Apply endpoint filter
        if ($this->selectedEndpoint) {
            $query->where('endpoint', $this->selectedEndpoint);
        }

        // Apply status filter
        if ($this->selectedStatus) {
            switch ($this->selectedStatus) {
                case 'success':
                    $query->successful();
                    break;
                case 'error':
                    $query->failed();
                    break;
                case 'slow':
                    $query->slow(5000);
                    break;
                case 'rate_limited':
                    $query->rateLimited();
                    break;
            }
        }

        // Apply suspicious filter
        if ($this->showSuspiciousOnly) {
            $query->suspicious();
        }

        // Apply slow requests filter
        if ($this->showSlowOnly) {
            $query->slow(5000);
        }

        // Tab filtering
        switch ($this->activeTab) {
            case 'errors':
                $query->failed();
                break;
            case 'suspicious':
                $query->suspicious();
                break;
            case 'performance':
                $query->slow(3000);
                break;
        }

        return $query->paginate(20);
    }

    /**
     * Set active tab
     */
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    /**
     * Toggle auto-refresh
     */
    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
        
        if ($this->autoRefresh) {
            $this->dispatch('startAutoRefresh', $this->refreshInterval);
        } else {
            $this->dispatch('stopAutoRefresh');
        }
    }

    /**
     * Manual refresh
     */
    public function refreshData()
    {
        // This method triggers a re-render
    }

    /**
     * Clear all filters
     */
    public function clearFilters()
    {
        $this->selectedEndpoint = '';
        $this->selectedStatus = '';
        $this->showSuspiciousOnly = false;
        $this->showSlowOnly = false;
        $this->dateFrom = now()->subHours(24)->format('Y-m-d\TH:i');
        $this->dateTo = now()->format('Y-m-d\TH:i');
        $this->resetPage();
    }

    /**
     * Export logs to CSV
     */
    public function exportLogs()
    {
        $logs = $this->getRecentLogs();
        
        $filename = "api_logs_" . now()->format('Y-m-d_H-i-s') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Timestamp',
                'Endpoint',
                'Method',
                'Response Code',
                'Response Time (ms)',
                'IP Address',
                'User',
                'Suspicious',
                'Error Message'
            ]);
            
            // CSV data
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->requested_at->format('Y-m-d H:i:s'),
                    $log->endpoint,
                    $log->method,
                    $log->response_code,
                    $log->response_time_ms,
                    $log->ip_address,
                    $log->user ? $log->user->name : 'N/A',
                    $log->suspicious ? 'Yes' : 'No',
                    $log->error_message
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Cleanup old logs
     */
    public function cleanupOldLogs()
    {
        $apiService = app(ApiMonitoringService::class);
        $deletedCount = $apiService->cleanupOldLogs(90);
        
        $this->dispatch('showToast', [
            'type' => 'success',
            'title' => 'Logi wyczyszczone',
            'message' => "Usunięto {$deletedCount} starych logów API.",
        ]);
    }

    /**
     * Get unique endpoints for filter dropdown
     */
    public function getEndpoints()
    {
        return ApiUsageLog::select('endpoint')
            ->distinct()
            ->orderBy('endpoint')
            ->limit(50)
            ->pluck('endpoint')
            ->toArray();
    }

    /**
     * Get status options for filter
     */
    public function getStatusOptions()
    {
        return [
            'success' => 'Sukces (2xx)',
            'error' => 'Błąd (4xx/5xx)',
            'slow' => 'Powolne (>5s)',
            'rate_limited' => 'Rate Limited',
        ];
    }

    /**
     * Update refresh interval
     */
    public function updatedRefreshInterval()
    {
        if ($this->autoRefresh) {
            $this->dispatch('updateRefreshInterval', $this->refreshInterval);
        }
    }

    /**
     * Get health status color
     */
    public function getHealthColor($status)
    {
        return match ($status) {
            'healthy' => 'text-green-600',
            'warning' => 'text-yellow-600',
            'critical' => 'text-red-600',
            default => 'text-gray-600',
        };
    }

    /**
     * Get health status icon
     */
    public function getHealthIcon($status)
    {
        return match ($status) {
            'healthy' => 'fas fa-check-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'critical' => 'fas fa-times-circle',
            default => 'fas fa-question-circle',
        };
    }
}