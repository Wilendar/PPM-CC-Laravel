<?php

namespace App\Http\Livewire\Products\Import;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use App\Models\PendingProduct;
use App\Models\ImportSession;
use App\Models\ProductType;
use App\Models\Manufacturer;
use Illuminate\Support\Facades\Auth;

/**
 * ProductImportPanel - ETAP_06 Panel Importu Produktow
 *
 * Glowny panel do zarzadzania pending products przed publikacja.
 * Workflow: Import (SKU/CSV) -> Edycja inline -> Publikacja -> Product
 */
#[Layout('layouts.admin')]
class ProductImportPanel extends Component
{
    use WithPagination;
    use Traits\ImportPanelFilters;
    use Traits\ImportPanelActions;
    use Traits\ImportPanelBulkOperations;
    use Traits\ImportPanelCategoryShopTrait;

    /**
     * Pagination settings
     */
    #[Url]
    public int $perPage = 25;

    /**
     * Sort configuration - domyslnie wg completion_percentage DESC
     */
    #[Url]
    public string $sortField = 'completion_percentage';

    #[Url]
    public string $sortDirection = 'desc';

    /**
     * Selected product IDs for bulk operations
     */
    public array $selectedIds = [];

    /**
     * Select all flag
     */
    public bool $selectAll = false;

    /**
     * Active modal state
     */
    public ?string $activeModal = null;

    /**
     * Listen for events from modals
     */
    protected $listeners = [
        'skuImportCompleted' => 'handleSkuImportCompleted',
        'csvImportCompleted' => 'handleCsvImportCompleted',
        'refreshPendingProducts' => '$refresh',
        'closeModal' => 'closeModal',
    ];

    /**
     * Initialize component
     */
    public function mount(): void
    {
        $this->resetFilters();
    }

    /**
     * Reset pagination when filters change
     */
    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterProductType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSessionId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Toggle sort direction or change sort field
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    /**
     * Get paginated pending products
     */
    #[Computed]
    public function pendingProducts()
    {
        return PendingProduct::query()
            ->with(['productType', 'importSession', 'importer'])
            ->when($this->filterStatus, fn($q) => $this->applyStatusFilter($q))
            ->when($this->filterProductType, fn($q) => $q->where('product_type_id', $this->filterProductType))
            ->when($this->filterSessionId, fn($q) => $q->where('import_session_id', $this->filterSessionId))
            ->when($this->filterSearch, fn($q) => $this->applySearchFilter($q))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    /**
     * Get available product types
     */
    #[Computed]
    public function productTypes()
    {
        return ProductType::orderBy('name')->get();
    }

    /**
     * Get manufacturers from Manufacturer model for dropdown
     * Ordered by name, only active
     */
    #[Computed]
    public function manufacturers()
    {
        return Manufacturer::active()->ordered()->get();
    }

    /**
     * Get import sessions for filter
     */
    #[Computed]
    public function importSessions()
    {
        return ImportSession::active()
            ->orWhere('status', ImportSession::STATUS_READY)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    /**
     * Get statistics for header
     *
     * UWAGA: Wszystkie liczniki musza uzywac unpublished() scope
     * aby liczyc tylko produkty oczekujace na publikacje.
     *
     * Logika statusu oparta na completion_percentage:
     * - ready (Gotowe) = completion_percentage == 100
     * - incomplete (Niekompletne) = completion_percentage < 100
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'total' => PendingProduct::unpublished()->count(),
            'ready' => PendingProduct::unpublished()->fullyComplete()->count(),
            'incomplete' => PendingProduct::unpublished()->partiallyComplete()->count(),
            'selected' => count($this->selectedIds),
        ];
    }

    /**
     * Handle SKU import completion
     */
    public function handleSkuImportCompleted(int $count): void
    {
        $this->dispatch('flash-message', [
            'type' => 'success',
            'message' => "Zaimportowano {$count} produktow z listy SKU",
        ]);
        $this->closeModal();
        $this->resetPage();
    }

    /**
     * Handle CSV import completion
     */
    public function handleCsvImportCompleted(int $count): void
    {
        $this->dispatch('flash-message', [
            'type' => 'success',
            'message' => "Zaimportowano {$count} produktow z pliku",
        ]);
        $this->closeModal();
        $this->resetPage();
    }

    /**
     * Open modal by name
     */
    public function openModal(string $modal): void
    {
        $this->activeModal = $modal;

        // Dispatch event to reset modal state
        if ($modal === 'sku-paste') {
            $this->dispatch('openSkuModal');
        } elseif ($modal === 'csv-import') {
            $this->dispatch('openCsvImportModal');
        }
    }

    /**
     * Close active modal
     */
    public function closeModal(): void
    {
        $this->activeModal = null;
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.products.import.product-import-panel');
    }
}
