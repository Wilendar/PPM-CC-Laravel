<?php

namespace App\Http\Livewire\Admin\Scan\Traits;

use App\Jobs\Scan\ScanProductLinksJob;
use App\Jobs\Scan\ScanMissingInPpmJob;
use App\Jobs\Scan\ScanMissingInSourceJob;
use App\Models\ProductScanSession;
use App\Models\ProductScanResult;
use Illuminate\Support\Facades\Log;

/**
 * Trait for scan actions (start, cancel, refresh)
 *
 * ETAP_10 FAZA 3: Product Scan System - Scan Actions
 *
 * @package App\Http\Livewire\Admin\Scan\Traits
 */
trait ScanActionsTrait
{
    /**
     * Start links scan - find PPM products without links to source
     */
    public function startLinksScan(): void
    {
        if (!$this->validateSelectedSource()) {
            return;
        }

        try {
            $session = ProductScanSession::create([
                'scan_type' => ProductScanSession::SCAN_LINKS,
                'source_type' => $this->selectedSourceType,
                'source_id' => $this->selectedSourceId,
                'status' => ProductScanSession::STATUS_PENDING,
                'user_id' => auth()->id(),
            ]);

            $this->activeScanSessionId = $session->id;

            // dispatchSync - uruchamia job natychmiast (bez kolejki)
            // Wymagane na hostingu bez ciąglego queue workera (Hostido)
            ScanProductLinksJob::dispatchSync(
                $session->id,
                $session->source_type,
                $session->source_id
            );

            session()->flash('success', 'Skan powiazan zostal zakonczony');

            Log::info('ScanProductsPanel: Links scan started', [
                'session_id' => $session->id,
                'source_type' => $this->selectedSourceType,
                'source_id' => $this->selectedSourceId,
            ]);

        } catch (\Exception $e) {
            Log::error('ScanProductsPanel: Failed to start links scan', [
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Blad podczas uruchamiania skanu: ' . $e->getMessage());
        }
    }

    /**
     * Start missing in PPM scan - find products in source that don't exist in PPM
     */
    public function startMissingInPpmScan(): void
    {
        if (!$this->validateSelectedSource()) {
            return;
        }

        try {
            $session = ProductScanSession::create([
                'scan_type' => ProductScanSession::SCAN_MISSING_PPM,
                'source_type' => $this->selectedSourceType,
                'source_id' => $this->selectedSourceId,
                'status' => ProductScanSession::STATUS_PENDING,
                'user_id' => auth()->id(),
            ]);

            $this->activeScanSessionId = $session->id;

            // dispatchSync - uruchamia job natychmiast (bez kolejki)
            ScanMissingInPpmJob::dispatchSync(
                $session->id,
                $session->source_type,
                $session->source_id
            );

            session()->flash('success', 'Skan brakujacych w PPM zostal zakonczony');

            Log::info('ScanProductsPanel: Missing in PPM scan started', [
                'session_id' => $session->id,
                'source_type' => $this->selectedSourceType,
                'source_id' => $this->selectedSourceId,
            ]);

        } catch (\Exception $e) {
            Log::error('ScanProductsPanel: Failed to start missing in PPM scan', [
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Blad podczas uruchamiania skanu: ' . $e->getMessage());
        }
    }

    /**
     * Start missing in source scan - find PPM products that don't exist in source
     */
    public function startMissingInSourceScan(): void
    {
        if (!$this->validateSelectedSource()) {
            return;
        }

        try {
            $session = ProductScanSession::create([
                'scan_type' => ProductScanSession::SCAN_MISSING_SOURCE,
                'source_type' => $this->selectedSourceType,
                'source_id' => $this->selectedSourceId,
                'status' => ProductScanSession::STATUS_PENDING,
                'user_id' => auth()->id(),
            ]);

            $this->activeScanSessionId = $session->id;

            // dispatchSync - uruchamia job natychmiast (bez kolejki)
            ScanMissingInSourceJob::dispatchSync(
                $session->id,
                $session->source_type,
                $session->source_id
            );

            session()->flash('success', 'Skan brakujacych w zrodle zostal zakonczony');

            Log::info('ScanProductsPanel: Missing in source scan started', [
                'session_id' => $session->id,
                'source_type' => $this->selectedSourceType,
                'source_id' => $this->selectedSourceId,
            ]);

        } catch (\Exception $e) {
            Log::error('ScanProductsPanel: Failed to start missing in source scan', [
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Blad podczas uruchamiania skanu: ' . $e->getMessage());
        }
    }

    /**
     * Cancel active scan
     */
    public function cancelScan(): void
    {
        if (!$this->activeScanSessionId) {
            return;
        }

        try {
            $session = ProductScanSession::find($this->activeScanSessionId);

            if ($session && $session->isActive()) {
                $session->markAsCancelled();
                session()->flash('success', 'Skan zostal anulowany');

                Log::info('ScanProductsPanel: Scan cancelled', [
                    'session_id' => $session->id,
                ]);
            }

            $this->activeScanSessionId = null;

        } catch (\Exception $e) {
            Log::error('ScanProductsPanel: Failed to cancel scan', [
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Blad podczas anulowania skanu');
        }
    }

    /**
     * Check active scan status - called by wire:poll
     */
    public function checkScanStatus(): void
    {
        if (!$this->activeScanSessionId) {
            return;
        }

        $session = ProductScanSession::find($this->activeScanSessionId);

        if (!$session) {
            $this->activeScanSessionId = null;
            return;
        }

        // Update scan stats for progress display
        $this->scanStats = [
            'total' => $session->total_scanned,
            'matched' => $session->matched_count,
            'unmatched' => $session->unmatched_count,
            'errors' => $session->errors_count,
            'progress' => $session->getProgressPercentage(),
            'status' => $session->status,
        ];

        // If scan completed, show notification
        if (!$session->isActive()) {
            $this->activeScanSessionId = null;
            $this->dispatch('scanCompleted', sessionId: $session->id);

            if ($session->isCompleted()) {
                session()->flash('success', 'Skan zakonczony pomyslnie');
            } elseif ($session->isFailed()) {
                session()->flash('error', 'Skan zakonczyl sie bledem: ' . $session->error_message);
            }
        }
    }
}
