<?php

namespace App\Http\Livewire\Products\Import;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\Category;
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
    use Traits\ImportPanelPermissionTrait;
    use Traits\ImportPanelPublicationTrait;

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
     * Kategorie - recznie wymuszony najwyzszy widoczny poziom (po kliknieciu "+")
     * Auto-rozwijanie wynika z danych (effectiveCategoryMaxLevel()).
     */
    public ?int $categoryForcedMaxLevel = null;

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
     *
     * NOTE: 'importCompleted' is handled via #[On('importCompleted')] attribute
     * on handleImportCompleted(). Do NOT add it here to avoid double-firing
     * which caused the modal to not reopen after import (E2 bug).
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
     * UI: Rozszerz kolumny kategorii o kolejny poziom (L6 -> L7 -> L8)
     */
    public function expandCategoryColumns(): void
    {
        $current = $this->effectiveCategoryMaxLevel;
        $this->categoryForcedMaxLevel = min(8, $current + 1);
    }

    /**
     * Auto: najwyzszy poziom kategorii wymagany przez dane (na aktualnej stronie tabeli)
     */
    #[Computed]
    public function autoCategoryMaxLevel(): int
    {
        $products = $this->pendingProducts;
        $items = method_exists($products, 'items') ? $products->items() : (array) $products;

        $categoryIds = collect($items)
            ->pluck('category_ids')
            ->filter(fn($ids) => is_array($ids) && !empty($ids))
            ->flatten()
            ->unique()
            ->values();

        if ($categoryIds->isEmpty()) {
            return 5;
        }

        $maxDbLevel = Category::whereIn('id', $categoryIds)->max('level');
        if ($maxDbLevel === null) {
            return 5;
        }

        $maxUiLevel = (int) $maxDbLevel + 1;

        return max(5, min(8, $maxUiLevel));
    }

    /**
     * Efektywny max poziom kolumn kategorii (auto z danych + ewentualne wymuszenie przez "+")
     */
    #[Computed]
    public function effectiveCategoryMaxLevel(): int
    {
        $auto = $this->autoCategoryMaxLevel;
        $forced = $this->categoryForcedMaxLevel !== null
            ? min(8, max(5, (int) $this->categoryForcedMaxLevel))
            : 5;

        return max($auto, $forced);
    }

    /**
     * Resetuje wymuszenie, gdy dane dogonia wymuszony poziom (zeby kolumny mogly sie ponownie zwijac)
     */
    protected function syncCategoryForcedMaxLevel(): void
    {
        if ($this->categoryForcedMaxLevel === null) {
            return;
        }

        $forced = min(8, max(5, (int) $this->categoryForcedMaxLevel));
        $auto = $this->autoCategoryMaxLevel;

        if ($auto >= $forced) {
            $this->categoryForcedMaxLevel = null;
            return;
        }

        $this->categoryForcedMaxLevel = $forced;
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
     * Handle unified import modal completion
     */
    #[On('importCompleted')]
    public function handleImportCompleted(int $count): void
    {
        $this->dispatch('flash-message', [
            'type' => 'success',
            'message' => "Zaimportowano {$count} produktow",
        ]);
        $this->closeModal();
        $this->resetPage();
    }

    /**
     * Handle PrestaShop categories saved event (BUG#3 fix - FAZA 9.7b)
     *
     * NOTE: Event is handled by Alpine.js in product-row.blade.php
     * to update badges dynamically without Livewire re-render.
     * This prevents "Snapshot missing" errors and preserves dropdown state.
     *
     * The handler below uses skipRender() to avoid full component refresh
     * while still allowing the event to propagate to Alpine listeners.
     */
    #[On('prestashop-categories-saved')]
    public function handlePrestashopCategoriesSaved(int $productId, int $shopId, int $categoryCount): void
    {
        // Skip Livewire re-render - Alpine handles UI update via @prestashop-categories-saved.window
        $this->skipRender();
    }

    /**
     * Open the unified import modal (FAZA 9.2)
     * Replaces openSKUPasteModal() and openCSVImportModal()
     *
     * NOTE: Named parameter MUST match child's openModal(?int $pendingProductId)
     */
    public function openImportModal(?int $editProductId = null): void
    {
        $this->activeModal = 'product-import';
        $this->dispatch('openImportModal', pendingProductId: $editProductId);
    }

    /**
     * Open import prices modal for a product (FAZA 9.4)
     */
    public function openImportPricesModal(int $productId): void
    {
        $this->dispatch('openImportPricesModal', productId: $productId);
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
        } elseif ($modal === 'product-import') {
            $this->dispatch('openImportModal');
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
