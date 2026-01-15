<?php

namespace App\Http\Livewire\Admin\Import;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\PendingProduct;
use App\Models\ImportSession;
use App\Models\ProductType;
use App\Models\PrestaShopShop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

/**
 * ProductImportPanel Component
 *
 * ETAP_06 FAZA 2: Core UI dla systemu importu produktów
 *
 * Panel do zarządzania produktami w stanie DRAFT (pending_products).
 * Umożliwia edycję, kategoryzację, wybór sklepów i publikację.
 *
 * Features:
 * - Advanced filtering (status, type, session, completion)
 * - Bulk operations (category, type, shops, publish, delete)
 * - Inline editing (SKU, name)
 * - Completion tracking per product
 * - Modal import (SKU paste, CSV) - FAZA 3-4
 *
 * Architecture:
 * - Main component (properties, lifecycle, listeners)
 * - Traits for logical separation (max 300 lines per file rule)
 *
 * @package App\Http\Livewire\Admin\Import
 * @version 1.0
 * @since ETAP_06 - Import/Export Module
 */
class ProductImportPanel extends Component
{
    use WithPagination;
    use Traits\ImportPanelBulkActions;
    use Traits\ImportPanelFilters;
    use Traits\ImportPanelModals;
    use Traits\ImportPanelTable;

    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES - Component State
    |--------------------------------------------------------------------------
    */

    // Filtering & Search
    public string $search = '';
    public string $statusFilter = 'all'; // all, incomplete, ready, published
    public string $productTypeFilter = 'all'; // all, or ProductType ID
    public string $sessionFilter = 'all'; // all, or ImportSession ID
    public int $completionMin = 0; // 0-100
    public int $completionMax = 100; // 0-100

    // Sorting & Display
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public int $perPage = 25;

    // Bulk Operations
    public array $selectedIds = [];
    public bool $selectAll = false;

    // Active Session (for filtering)
    public ?int $activeSessionId = null;

    // UI State
    public bool $showFilters = false;
    public bool $showBulkActions = false;

    // Modal State (FAZA 3-4 implementation)
    public bool $showSKUPasteModal = false;
    public bool $showCSVImportModal = false;

    /*
    |--------------------------------------------------------------------------
    | LIVEWIRE LIFECYCLE
    |--------------------------------------------------------------------------
    */

    /**
     * Component initialization
     */
    public function mount(): void
    {
        // Load active session if exists
        $this->activeSessionId = ImportSession::active()
            ->byUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->first()?->id;

        // Auto-set session filter if active session exists
        if ($this->activeSessionId) {
            $this->sessionFilter = (string) $this->activeSessionId;
        }
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.admin.import.product-import-panel', [
            'pendingProducts' => $this->pendingProducts,
            'importSessions' => $this->getImportSessions(),
            'productTypes' => $this->getProductTypes(),
            'shops' => $this->getShops(),
            'bulkActionCount' => count($this->selectedIds),
        ])->layout('layouts.admin');
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get paginated pending products with filters applied
     * (Implementation in ImportPanelFilters trait)
     */
    #[Computed]
    public function pendingProducts(): LengthAwarePaginator
    {
        return $this->buildQuery()->paginate($this->perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | LIVEWIRE LISTENERS
    |--------------------------------------------------------------------------
    */

    /**
     * Listen for product published event
     */
    #[On('product-published')]
    public function handleProductPublished(int $pendingProductId): void
    {
        // Remove from selection if was selected
        $this->selectedIds = array_filter(
            $this->selectedIds,
            fn($id) => $id !== $pendingProductId
        );

        // Reset selection state if no more selected
        if (empty($this->selectedIds)) {
            $this->selectAll = false;
            $this->showBulkActions = false;
        }

        // Flash success message
        session()->flash('message', 'Produkt opublikowany pomyslnie!');
        session()->flash('message_type', 'success');
    }

    /**
     * Listen for import session completed
     */
    #[On('import-session-completed')]
    public function handleImportCompleted(int $sessionId): void
    {
        // Refresh active session
        $this->activeSessionId = ImportSession::active()
            ->byUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->first()?->id;

        // Flash message
        session()->flash('message', 'Import zakończony!');
        session()->flash('message_type', 'success');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS - Data Providers
    |--------------------------------------------------------------------------
    */

    /**
     * Get import sessions for filter dropdown
     */
    protected function getImportSessions(): \Illuminate\Support\Collection
    {
        return ImportSession::byUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
    }

    /**
     * Get product types for filter dropdown
     */
    protected function getProductTypes(): \Illuminate\Support\Collection
    {
        return ProductType::orderBy('name')->get();
    }

    /**
     * Get shops for bulk assignment
     */
    protected function getShops(): \Illuminate\Support\Collection
    {
        return PrestaShopShop::active()->orderBy('name')->get();
    }

    /*
    |--------------------------------------------------------------------------
    | UI HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle filters panel
     */
    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
    }

    /**
     * Toggle bulk actions panel
     */
    public function toggleBulkActions(): void
    {
        $this->showBulkActions = !$this->showBulkActions;
    }

    /**
     * Reset all filters to default
     */
    public function resetAllFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->productTypeFilter = 'all';
        $this->sessionFilter = 'all';
        $this->completionMin = 0;
        $this->completionMax = 100;
        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | WATCHERS
    |--------------------------------------------------------------------------
    */

    /**
     * Reset pagination when search changes
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when filters change
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedProductTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSessionFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Update bulk actions visibility when selection changes
     */
    public function updatedSelectedIds(): void
    {
        $this->showBulkActions = !empty($this->selectedIds);
    }
}
