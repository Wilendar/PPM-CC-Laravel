<?php

namespace App\Http\Livewire\Components;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

/**
 * ErrorDetailsModal Component - Display job errors in modal
 *
 * Features:
 * - Modal dialog with error list
 * - SKU | Error Message table
 * - Export to CSV functionality
 * - Responsive design
 * - Click outside to close
 *
 * Usage:
 * <livewire:components.error-details-modal />
 *
 * Triggered by event:
 * $this->dispatch('show-error-details', ['jobId' => '...', 'errors' => [...]])
 *
 * @package App\Http\Livewire\Components
 * @version 1.0
 * @since Real-Time Progress Tracking Feature
 */
class ErrorDetailsModal extends Component
{
    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    public bool $isOpen = false;
    public string $jobId = '';
    public array $errors = [];

    /*
    |--------------------------------------------------------------------------
    | PUBLIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Show modal with error details
     */
    #[On('show-error-details')]
    public function show(array $data): void
    {
        $this->jobId = $data['jobId'] ?? '';
        $this->errors = $data['errors'] ?? [];
        $this->isOpen = true;

        Log::info('ErrorDetailsModal: Showing errors', [
            'job_id' => $this->jobId,
            'error_count' => count($this->errors),
        ]);
    }

    /**
     * Close modal
     */
    public function close(): void
    {
        $this->isOpen = false;
        $this->jobId = '';
        $this->errors = [];
    }

    /**
     * Export errors to CSV
     */
    public function exportToCsv(): void
    {
        try {
            $filename = 'job_errors_' . $this->jobId . '_' . date('Y-m-d_His') . '.csv';
            $csvData = $this->generateCsvData();

            // Dispatch browser download event
            $this->dispatch('download-csv', [
                'filename' => $filename,
                'data' => $csvData,
            ]);

            $this->dispatch('success', message: 'Eksport błędów rozpoczęty');

            Log::info('ErrorDetailsModal: CSV export initiated', [
                'job_id' => $this->jobId,
                'filename' => $filename,
                'error_count' => count($this->errors),
            ]);

        } catch (\Exception $e) {
            Log::error('ErrorDetailsModal: CSV export failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Nie udało się wyeksportować błędów');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Generate CSV data from errors
     */
    private function generateCsvData(): string
    {
        $csv = "SKU,Komunikat błędu\n";

        foreach ($this->errors as $error) {
            $sku = $error['sku'] ?? 'Unknown';
            $message = $error['message'] ?? 'No message';

            // Escape CSV values
            $sku = '"' . str_replace('"', '""', $sku) . '"';
            $message = '"' . str_replace('"', '""', $message) . '"';

            $csv .= "{$sku},{$message}\n";
        }

        return $csv;
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
        return view('livewire.components.error-details-modal');
    }
}
