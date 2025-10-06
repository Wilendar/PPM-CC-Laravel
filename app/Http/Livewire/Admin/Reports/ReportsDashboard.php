<?php

namespace App\Http\Livewire\Admin\Reports;

use App\Models\SystemReport;
use App\Services\ReportsService;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class ReportsDashboard extends Component
{
    use WithPagination;

    public $activeTab = 'overview';
    public $selectedType = '';
    public $selectedPeriod = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $showGenerateModal = false;
    
    // Report generation fields
    public $generateType = 'usage_analytics';
    public $generatePeriod = 'daily';
    public $generateDate = '';

    protected $listeners = [
        'reportGenerated' => 'refreshReports',
    ];

    public function mount()
    {
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->generateDate = now()->format('Y-m-d');
    }

    public function render()
    {
        $reportsService = app(ReportsService::class);
        
        return view('livewire.admin.reports.reports-dashboard', [
            'reports' => $this->getFilteredReports(),
            'statistics' => $reportsService->getReportStatistics(),
            'latestReports' => $reportsService->getLatestReports(5),
            'chartData' => $this->getChartData(),
        ]);
    }

    /**
     * Get filtered reports
     */
    protected function getFilteredReports()
    {
        $query = SystemReport::with('generator')
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($this->selectedType) {
            $query->where('type', $this->selectedType);
        }

        // Filter by period
        if ($this->selectedPeriod) {
            $query->where('period', $this->selectedPeriod);
        }

        // Filter by date range
        if ($this->dateFrom) {
            $query->where('report_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('report_date', '<=', $this->dateTo);
        }

        // Tab filtering
        switch ($this->activeTab) {
            case 'completed':
                $query->where('status', SystemReport::STATUS_COMPLETED);
                break;
            case 'generating':
                $query->where('status', SystemReport::STATUS_GENERATING);
                break;
            case 'failed':
                $query->where('status', SystemReport::STATUS_FAILED);
                break;
            case 'usage':
                $query->where('type', SystemReport::TYPE_USAGE_ANALYTICS);
                break;
            case 'performance':
                $query->where('type', SystemReport::TYPE_PERFORMANCE);
                break;
            case 'business':
                $query->where('type', SystemReport::TYPE_BUSINESS_INTELLIGENCE);
                break;
        }

        return $query->paginate(15);
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
     * Show generate report modal
     */
    public function showGenerateModal()
    {
        $this->showGenerateModal = true;
    }

    /**
     * Hide generate report modal
     */
    public function hideGenerateModal()
    {
        $this->showGenerateModal = false;
        $this->resetGenerateForm();
    }

    /**
     * Generate new report
     */
    public function generateReport()
    {
        $this->validate([
            'generateType' => 'required|in:usage_analytics,performance,business_intelligence,integration_performance',
            'generatePeriod' => 'required|in:daily,weekly,monthly,quarterly',
            'generateDate' => 'required|date|before_or_equal:today',
        ]);

        $reportsService = app(ReportsService::class);
        $date = Carbon::parse($this->generateDate);

        try {
            $report = match ($this->generateType) {
                'usage_analytics' => $reportsService->generateUsageAnalyticsReport($this->generatePeriod, $date),
                'performance' => $reportsService->generatePerformanceReport($this->generatePeriod, $date),
                'business_intelligence' => $reportsService->generateBusinessIntelligenceReport($this->generatePeriod, $date),
                'integration_performance' => $reportsService->generateIntegrationPerformanceReport($this->generatePeriod, $date),
            };

            $this->dispatch('showToast', [
                'type' => 'success',
                'title' => 'Raport w kolejce',
                'message' => 'Raport został dodany do kolejki generowania.',
            ]);

            $this->hideGenerateModal();
            $this->refreshReports();

        } catch (\Exception $e) {
            $this->dispatch('showToast', [
                'type' => 'error',
                'title' => 'Błąd generowania',
                'message' => 'Nie udało się wygenerować raportu: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete report
     */
    public function deleteReport($reportId)
    {
        $report = SystemReport::find($reportId);
        
        if ($report) {
            $report->delete();
            
            $this->dispatch('showToast', [
                'type' => 'success',
                'title' => 'Raport usunięty',
                'message' => 'Raport został pomyślnie usunięty.',
            ]);
        }
    }

    /**
     * Download report data
     */
    public function downloadReport($reportId)
    {
        $report = SystemReport::find($reportId);
        
        if (!$report || $report->status !== SystemReport::STATUS_COMPLETED) {
            $this->dispatch('showToast', [
                'type' => 'error',
                'title' => 'Błąd pobierania',
                'message' => 'Raport nie jest dostępny do pobrania.',
            ]);
            return;
        }

        // Generate file download
        $fileName = "report_{$report->type}_{$report->period}_{$report->report_date}.json";
        $fileContent = json_encode($report->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return response()->streamDownload(function () use ($fileContent) {
            echo $fileContent;
        }, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Regenerate report
     */
    public function regenerateReport($reportId)
    {
        $existingReport = SystemReport::find($reportId);
        
        if (!$existingReport) {
            return;
        }

        $reportsService = app(ReportsService::class);
        $date = Carbon::parse($existingReport->report_date);

        try {
            $newReport = match ($existingReport->type) {
                'usage_analytics' => $reportsService->generateUsageAnalyticsReport($existingReport->period, $date),
                'performance' => $reportsService->generatePerformanceReport($existingReport->period, $date),
                'business_intelligence' => $reportsService->generateBusinessIntelligenceReport($existingReport->period, $date),
                'integration_performance' => $reportsService->generateIntegrationPerformanceReport($existingReport->period, $date),
            };

            $this->dispatch('showToast', [
                'type' => 'success',
                'title' => 'Raport regenerowany',
                'message' => 'Nowa wersja raportu została dodana do kolejki.',
            ]);

        } catch (\Exception $e) {
            $this->dispatch('showToast', [
                'type' => 'error',
                'title' => 'Błąd regeneracji',
                'message' => 'Nie udało się wygenerować raportu: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear filters
     */
    public function clearFilters()
    {
        $this->selectedType = '';
        $this->selectedPeriod = '';
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    /**
     * Refresh reports
     */
    public function refreshReports()
    {
        // This method is called by the listener
    }

    /**
     * Reset generate form
     */
    protected function resetGenerateForm()
    {
        $this->generateType = 'usage_analytics';
        $this->generatePeriod = 'daily';
        $this->generateDate = now()->format('Y-m-d');
    }

    /**
     * Get chart data for dashboard
     */
    protected function getChartData(): array
    {
        $reportsService = app(ReportsService::class);
        
        // Get report generation trends
        $reportTrends = SystemReport::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Get report types distribution
        $typeDistribution = SystemReport::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        // Get status distribution
        $statusDistribution = SystemReport::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return [
            'report_trends' => $reportTrends,
            'type_distribution' => $typeDistribution,
            'status_distribution' => $statusDistribution,
        ];
    }

    /**
     * Get available report types
     */
    public function getReportTypes(): array
    {
        return [
            SystemReport::TYPE_USAGE_ANALYTICS => 'Analytics użycia',
            SystemReport::TYPE_PERFORMANCE => 'Wydajność',
            SystemReport::TYPE_BUSINESS_INTELLIGENCE => 'Business Intelligence',
            SystemReport::TYPE_INTEGRATION_PERFORMANCE => 'Wydajność integracji',
        ];
    }

    /**
     * Get available periods
     */
    public function getPeriods(): array
    {
        return [
            SystemReport::PERIOD_DAILY => 'Dzienny',
            SystemReport::PERIOD_WEEKLY => 'Tygodniowy',
            SystemReport::PERIOD_MONTHLY => 'Miesięczny',
            SystemReport::PERIOD_QUARTERLY => 'Kwartalny',
        ];
    }
}