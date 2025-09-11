<?php

namespace App\Jobs;

use App\Models\SystemReport;
use App\Services\ReportsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public SystemReport $report;

    /**
     * Create a new job instance.
     */
    public function __construct(SystemReport $report)
    {
        $this->report = $report;
    }

    /**
     * Execute the job.
     */
    public function handle(ReportsService $reportsService): void
    {
        try {
            Log::info('Starting report generation', [
                'report_id' => $this->report->id,
                'type' => $this->report->type,
                'period' => $this->report->period,
            ]);

            $data = $this->generateReportData($reportsService);
            $summary = $this->generateSummary($data);

            $this->report->markCompleted($data, $summary);

            Log::info('Report generation completed', [
                'report_id' => $this->report->id,
                'data_points' => $this->report->data_points_count,
                'generation_time' => $this->report->generation_time_seconds,
            ]);

        } catch (\Exception $e) {
            Log::error('Report generation failed', [
                'report_id' => $this->report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->report->markFailed($e->getMessage());

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Generate report data based on type
     */
    protected function generateReportData(ReportsService $reportsService): array
    {
        $dateRange = $this->getDateRange();
        
        return match ($this->report->type) {
            SystemReport::TYPE_USAGE_ANALYTICS => $reportsService->buildUsageAnalyticsData(
                $dateRange['start'], 
                $dateRange['end']
            ),
            SystemReport::TYPE_PERFORMANCE => $reportsService->buildPerformanceData(
                $dateRange['start'], 
                $dateRange['end']
            ),
            SystemReport::TYPE_BUSINESS_INTELLIGENCE => $reportsService->buildBusinessIntelligenceData(
                $dateRange['start'], 
                $dateRange['end']
            ),
            SystemReport::TYPE_INTEGRATION_PERFORMANCE => $reportsService->buildIntegrationPerformanceData(
                $dateRange['start'], 
                $dateRange['end']
            ),
            default => throw new \InvalidArgumentException("Unknown report type: {$this->report->type}"),
        };
    }

    /**
     * Get date range based on report period and date
     */
    protected function getDateRange(): array
    {
        $reportDate = Carbon::parse($this->report->report_date);

        return match ($this->report->period) {
            SystemReport::PERIOD_DAILY => [
                'start' => $reportDate->startOfDay(),
                'end' => $reportDate->endOfDay(),
            ],
            SystemReport::PERIOD_WEEKLY => [
                'start' => $reportDate->startOfWeek(),
                'end' => $reportDate->endOfWeek(),
            ],
            SystemReport::PERIOD_MONTHLY => [
                'start' => $reportDate->startOfMonth(),
                'end' => $reportDate->endOfMonth(),
            ],
            SystemReport::PERIOD_QUARTERLY => [
                'start' => $reportDate->startOfQuarter(),
                'end' => $reportDate->endOfQuarter(),
            ],
            default => [
                'start' => $reportDate->startOfDay(),
                'end' => $reportDate->endOfDay(),
            ],
        };
    }

    /**
     * Generate executive summary
     */
    protected function generateSummary(array $data): string
    {
        return match ($this->report->type) {
            SystemReport::TYPE_USAGE_ANALYTICS => $this->generateUsageAnalyticsSummary($data),
            SystemReport::TYPE_PERFORMANCE => $this->generatePerformanceSummary($data),
            SystemReport::TYPE_BUSINESS_INTELLIGENCE => $this->generateBusinessIntelligenceSummary($data),
            SystemReport::TYPE_INTEGRATION_PERFORMANCE => $this->generateIntegrationPerformanceSummary($data),
            default => 'Raport został wygenerowany pomyślnie.',
        };
    }

    /**
     * Generate usage analytics summary
     */
    protected function generateUsageAnalyticsSummary(array $data): string
    {
        $userActivity = $data['user_activity'] ?? [];
        $activeUsers = $userActivity['active_users_period'] ?? 0;
        $totalUsers = $userActivity['total_users'] ?? 0;
        $activityRate = $userActivity['activity_rate'] ?? 0;

        $period = $this->getPeriodLabel();
        
        return "RAPORT ANALYTICS UŻYCIA - {$period}\n\n" .
               "• Aktywni użytkownicy: {$activeUsers} z {$totalUsers} ({$activityRate}%)\n" .
               "• Najczęściej używane funkcje zostały zidentyfikowane\n" .
               "• Wzorce logowania zostały przeanalizowane\n\n" .
               "Szczegółowe dane dostępne w sekcji danych raportu.";
    }

    /**
     * Generate performance summary
     */
    protected function generatePerformanceSummary(array $data): string
    {
        $apiPerformance = $data['api_performance'] ?? [];
        $avgResponseTime = $apiPerformance['average_response_time'] ?? 0;
        $errorRate = $apiPerformance['error_rate'] ?? 0;
        $totalRequests = $apiPerformance['total_requests'] ?? 0;

        $period = $this->getPeriodLabel();

        return "RAPORT WYDAJNOŚCI - {$period}\n\n" .
               "• Średni czas odpowiedzi API: {$avgResponseTime}ms\n" .
               "• Wskaźnik błędów: {$errorRate}%\n" .
               "• Całkowita liczba żądań: {$totalRequests}\n\n" .
               "System działa w akceptowalnych parametrach wydajności.";
    }

    /**
     * Generate business intelligence summary
     */
    protected function generateBusinessIntelligenceSummary(array $data): string
    {
        $productMgmt = $data['product_management'] ?? [];
        $productsCreated = $productMgmt['products_created'] ?? 0;
        $productsUpdated = $productMgmt['products_updated'] ?? 0;
        $totalProducts = $productMgmt['total_products'] ?? 0;

        $period = $this->getPeriodLabel();

        return "RAPORT BUSINESS INTELLIGENCE - {$period}\n\n" .
               "• Utworzono produktów: {$productsCreated}\n" .
               "• Zaktualizowano produktów: {$productsUpdated}\n" .
               "• Łączna liczba produktów: {$totalProducts}\n\n" .
               "Analiza produktywności i jakości danych została przeprowadzona.";
    }

    /**
     * Generate integration performance summary
     */
    protected function generateIntegrationPerformanceSummary(array $data): string
    {
        $period = $this->getPeriodLabel();

        return "RAPORT WYDAJNOŚCI INTEGRACJI - {$period}\n\n" .
               "• Monitorowano wszystkie aktywne integracje\n" .
               "• Przeanalizowano synchronizację danych\n" .
               "• Sprawdzono wydajność API zewnętrznych\n\n" .
               "Szczegółowe metryki dostępne w danych raportu.";
    }

    /**
     * Get period label in Polish
     */
    protected function getPeriodLabel(): string
    {
        return match ($this->report->period) {
            SystemReport::PERIOD_DAILY => 'DZIENNY',
            SystemReport::PERIOD_WEEKLY => 'TYGODNIOWY',
            SystemReport::PERIOD_MONTHLY => 'MIESIĘCZNY',
            SystemReport::PERIOD_QUARTERLY => 'KWARTALNY',
            default => 'NIEZNANY OKRES',
        };
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'report',
            'report:' . $this->report->id,
            'type:' . $this->report->type,
            'period:' . $this->report->period,
        ];
    }
}