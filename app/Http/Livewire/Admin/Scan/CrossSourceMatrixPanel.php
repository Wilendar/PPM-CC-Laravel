<?php

namespace App\Http\Livewire\Admin\Scan;

use App\Http\Livewire\Admin\Scan\Traits\ChunkedScanTrait;
use App\Http\Livewire\Admin\Scan\Traits\MatrixActionsTrait;
use App\Http\Livewire\Admin\Scan\Traits\MatrixDataTrait;
use App\Http\Livewire\Admin\Scan\Traits\MatrixFiltersTrait;
use App\Http\Livewire\Admin\Scan\Traits\ScanSourcesTrait;
use App\Models\ProductScanSession;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

/**
 * CrossSourceMatrixPanel
 *
 * Glowny panel macierzy Cross-Source zastepujacy stary ScanProductsPanel.
 * Pokazuje status kazdego produktu we WSZYSTKICH zrodlach naraz (PS shops + ERP).
 *
 * Architektura trait-based:
 * - MatrixDataTrait    - ladowanie danych i zrodel
 * - MatrixFiltersTrait - filtry (search, status, brand, paginacja)
 * - MatrixActionsTrait - akcje na komorkach macierzy
 * - ChunkedScanTrait   - chunked scan engine (prefetch + chunk-by-chunk)
 *
 * Zakładki:
 * - 'matrix'  - widok macierzy Quick Matrix (bez pelnego skanu)
 * - 'history' - historia sesji skanowania
 *
 * @package App\Http\Livewire\Admin\Scan
 * @version 1.0.0
 */
class CrossSourceMatrixPanel extends Component
{
    use AuthorizesRequests;
    use MatrixDataTrait;
    use MatrixFiltersTrait;
    use MatrixActionsTrait;
    use ChunkedScanTrait;
    use ScanSourcesTrait;

    /**
     * Aktywna zakładka panelu.
     * Mozliwe wartosci: 'matrix' | 'history'
     */
    public string $activeTab = 'matrix';

    /**
     * Inicjalizuje komponent: laduje zrodla i wykrywa aktywny scan.
     *
     * @return void
     */
    public function mount(): void
    {
        $this->authorize('admin.scan.view');
        $this->loadSources();
        $this->detectActiveChunkedScan();
    }

    /**
     * Renderuje komponent z danymi zalezymi od aktywnej zakladki.
     *
     * @return View
     */
    public function render(): View
    {
        $data = [
            'sources'   => $this->sources,
            'activeTab' => $this->activeTab,
        ];

        if ($this->activeTab === 'matrix') {
            $matrixData                   = $this->getMatrixData();
            $data['matrixData']           = $matrixData;
            $data['hasMoreProducts']      = $matrixData->total() > ($this->perPage + $this->loadedCount);
            $data['summaryStats']         = $this->getSummaryStatsData();
            $brandData                    = $this->loadBrandSuggestions();
            $data['brandSuggestions']     = $brandData['active'];
            $data['dismissedSuggestions'] = $brandData['dismissed'];
            $data['availableBrands']      = $this->getAvailableBrands();
        } else {
            $data['history'] = ProductScanSession::latest()
                ->take(20)
                ->get();
        }

        return view('livewire.admin.scan.cross-source-matrix-panel', $data)
            ->layout('layouts.admin', [
                'title'      => 'Macierz Produktow - Admin PPM',
                'breadcrumb' => 'Macierz Produktow',
            ]);
    }

    /**
     * Przelacza aktywna zakladke panelu.
     * Resetuje paginacje przy zmianie zakladki.
     *
     * @param  string $tab Nazwa zakladki: 'matrix' | 'history'
     * @return void
     */
    public function setTab(string $tab): void
    {
        $this->activeTab        = $tab;
        $this->loadedCount      = 0;
        $this->hasMoreProducts  = true;
    }

    /**
     * Zwraca dane postępu skanu do Alpine (proxy do ChunkedScanTrait::getScanProgressData).
     * Wywolywane przez wire:poll podczas aktywnego skanu.
     *
     * @return array{phase: string, progress: int, processedChunks: int, totalChunks: int, estimatedTime: string|null, stats: array}
     */
    public function getScanProgress(): array
    {
        return $this->getScanProgressData();
    }

    /**
     * Sprawdza czy sa komorki pending_sync w biezacym widoku macierzy.
     * Uzywane do warunkowego wire:poll - odswiezanie az joby sie zakoncza.
     */
    public function hasPendingSyncCells(): bool
    {
        if ($this->activeTab !== 'matrix') {
            return false;
        }

        $matrixData = $this->getMatrixData();
        foreach ($matrixData->items() as $product) {
            $cells = $product->matrix_cells ?? [];
            foreach ($cells as $cell) {
                if (($cell['status'] ?? '') === 'pending_sync') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Polling method - odswieza macierz i zwraca czy dalej polowac.
     * Wywoływane przez wire:poll gdy sa pending cells.
     */
    public function pollPendingSync(): void
    {
        $this->refreshMatrix();
    }
}
