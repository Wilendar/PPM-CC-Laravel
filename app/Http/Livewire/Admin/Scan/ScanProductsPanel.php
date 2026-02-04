<?php

namespace App\Http\Livewire\Admin\Scan;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductScanSession;
use App\Models\ProductScanResult;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * ScanProductsPanel Livewire Component
 *
 * ETAP_10 FAZA 3: Product Scan System - Main UI Panel
 *
 * Panel do skanowania i powiazywania produktow miedzy PPM a systemami zewnetrznymi.
 * Obsluguje: Subiekt GT, Baselinker, Dynamics, PrestaShop.
 *
 * Features:
 * - Trzy typy skanow: powiazania, brakujace w PPM, brakujace w zrodle
 * - Real-time progress tracking (wire:poll)
 * - Bulk actions: link, create, ignore
 * - Historia skanow
 * - Filtry i paginacja wynikow
 *
 * @package App\Http\Livewire\Admin\Scan
 * @version 1.0
 * @since ETAP_10 - Product Scan System
 */
class ScanProductsPanel extends Component
{
    use WithPagination, AuthorizesRequests;
    use Traits\ScanSourcesTrait;
    use Traits\ScanActionsTrait;
    use Traits\BulkActionsTrait;

    // ==========================================
    // COMPONENT STATE
    // ==========================================

    /** @var string Active tab: links, missing_ppm, missing_source, history */
    public string $activeTab = 'links';

    /** @var string|null Selected source type (subiekt_gt, baselinker, prestashop, etc.) */
    public ?string $selectedSourceType = null;

    /** @var int|null Selected source ID (ERPConnection.id or PrestaShopShop.id) */
    public ?int $selectedSourceId = null;

    /** @var int|null Active scan session ID for progress tracking */
    public ?int $activeScanSessionId = null;

    /** @var array Scan statistics for progress display */
    public array $scanStats = [];

    // ==========================================
    // FILTERS
    // ==========================================

    /** @var string Search query for results */
    public string $search = '';

    /** @var string Match status filter: all, matched, unmatched, conflict */
    public string $matchStatusFilter = 'all';

    /** @var string Resolution status filter: all, pending, linked, created, ignored */
    public string $resolutionFilter = 'all';

    // ==========================================
    // BULK SELECTION
    // ==========================================

    /** @var array<int> Selected result IDs for bulk actions */
    public array $selectedResults = [];

    /** @var bool Select all toggle */
    public bool $selectAll = false;

    // ==========================================
    // LISTENERS
    // ==========================================

    protected $listeners = [
        'scanCompleted' => 'handleScanCompleted',
        'refreshResults' => '$refresh',
    ];

    // ==========================================
    // LIFECYCLE METHODS
    // ==========================================

    /**
     * Mount component - load initial data
     */
    public function mount(): void
    {
        // DEVELOPMENT: Authorization temporarily disabled
        // $this->authorize('admin.scan.view');

        // Load last active scan if any
        $activeScan = ProductScanSession::running()
            ->where('user_id', auth()->id())
            ->latest()
            ->first();

        if ($activeScan) {
            $this->activeScanSessionId = $activeScan->id;
            $this->selectedSourceType = $activeScan->source_type;
            $this->selectedSourceId = $activeScan->source_id;
        }
    }

    /**
     * Render the component
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('livewire.admin.scan.scan-products-panel', [
            'sources' => $this->getAvailableSources(),
            'groupedSources' => $this->getGroupedSources(),
            'results' => $this->getFilteredResults(),
            'activeScan' => $this->getActiveScan(),
            'stats' => $this->getScanStats(),
            'history' => $this->getScanHistory(),
        ])->layout('layouts.admin', [
            'title' => 'Skanowanie Produktow - Admin PPM',
            'breadcrumb' => 'Skanowanie Produktow',
        ]);
    }

    // ==========================================
    // DATA METHODS
    // ==========================================

    /**
     * Get filtered and paginated results for current session/tab
     *
     * @return LengthAwarePaginator
     */
    public function getFilteredResults(): LengthAwarePaginator
    {
        $query = ProductScanResult::query()
            ->with(['scanSession', 'ppmProduct']);

        // Filter by active session or latest session for selected source
        if ($this->activeScanSessionId) {
            $query->where('scan_session_id', $this->activeScanSessionId);
        } else {
            // Get results from latest completed session for selected source
            $latestSession = ProductScanSession::completed()
                ->when($this->selectedSourceType, fn($q) => $q->where('source_type', $this->selectedSourceType))
                ->when($this->selectedSourceId, fn($q) => $q->where('source_id', $this->selectedSourceId))
                ->when($this->activeTab !== 'history', function ($q) {
                    $scanType = match ($this->activeTab) {
                        'links' => ProductScanSession::SCAN_LINKS,
                        'missing_ppm' => ProductScanSession::SCAN_MISSING_PPM,
                        'missing_source' => ProductScanSession::SCAN_MISSING_SOURCE,
                        default => null,
                    };
                    if ($scanType) {
                        $q->where('scan_type', $scanType);
                    }
                })
                ->latest()
                ->first();

            if ($latestSession) {
                $query->where('scan_session_id', $latestSession->id);
            } else {
                // No results to show
                return new LengthAwarePaginator([], 0, 25);
            }
        }

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('sku', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%")
                    ->orWhere('external_id', 'like', "%{$this->search}%");
            });
        }

        // Apply match status filter
        if ($this->matchStatusFilter !== 'all') {
            $query->where('match_status', $this->matchStatusFilter);
        }

        // Apply resolution filter
        if ($this->resolutionFilter !== 'all') {
            $query->where('resolution_status', $this->resolutionFilter);
        }

        return $query->orderBy('created_at', 'desc')->paginate(25);
    }

    /**
     * Get active scan session
     *
     * @return ProductScanSession|null
     */
    public function getActiveScan(): ?ProductScanSession
    {
        if (!$this->activeScanSessionId) {
            return null;
        }

        return ProductScanSession::find($this->activeScanSessionId);
    }

    /**
     * Get statistics for current session
     *
     * @return array
     */
    public function getScanStats(): array
    {
        if (!empty($this->scanStats)) {
            return $this->scanStats;
        }

        $session = $this->getActiveScan();

        if (!$session) {
            return [
                'total' => 0,
                'matched' => 0,
                'unmatched' => 0,
                'errors' => 0,
                'progress' => 0,
                'status' => 'idle',
            ];
        }

        return [
            'total' => $session->total_scanned,
            'matched' => $session->matched_count,
            'unmatched' => $session->unmatched_count,
            'errors' => $session->errors_count,
            'progress' => $session->getProgressPercentage(),
            'status' => $session->status,
        ];
    }

    /**
     * Get scan history
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getScanHistory()
    {
        return ProductScanSession::with('user')
            ->when($this->selectedSourceType, fn($q) => $q->where('source_type', $this->selectedSourceType))
            ->when($this->selectedSourceId, fn($q) => $q->where('source_id', $this->selectedSourceId))
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();
    }

    // ==========================================
    // TAB AND FILTER METHODS
    // ==========================================

    /**
     * Set active tab
     *
     * @param string $tab
     */
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
        $this->selectedResults = [];
        $this->selectAll = false;
    }

    /**
     * Set source selection
     *
     * @param string $sourceType
     * @param int $sourceId
     */
    public function selectSource(string $sourceType, int $sourceId): void
    {
        $this->selectedSourceType = $sourceType;
        $this->selectedSourceId = $sourceId;
        $this->resetPage();
    }

    /**
     * Reset filters
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->matchStatusFilter = 'all';
        $this->resolutionFilter = 'all';
        $this->resetPage();
    }

    /**
     * Handle search update
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // ==========================================
    // EVENT HANDLERS
    // ==========================================

    /**
     * Handle scan completed event
     *
     * @param int $sessionId
     */
    public function handleScanCompleted(int $sessionId): void
    {
        Log::info('ScanProductsPanel: Scan completed', ['session_id' => $sessionId]);
        $this->activeScanSessionId = null;
        $this->scanStats = [];
    }
}
