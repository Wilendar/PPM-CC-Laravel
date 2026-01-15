<?php

namespace App\Http\Livewire\Admin\Variants;

use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Services\Product\ProductVariantSearchService;
use App\Http\Livewire\Admin\Variants\Traits\TypeInlineCrud;
use App\Http\Livewire\Admin\Variants\Traits\ValueInlineCrud;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\WithPagination;

/**
 * VariantPanelContainer - 3-Panel Layout with Inline CRUD
 *
 * Variant Panel Redesign - Main container component
 * Can be used standalone or embedded in ProductParametersManager
 *
 * Layout:
 * - Left Panel: Attribute Types list + inline CRUD
 * - Center Panel: Attribute Values + inline CRUD
 * - Right Panel: Products using selected values
 *
 * @package App\Http\Livewire\Admin\Variants
 * @version 2.0
 * @since Variant Panel Inline CRUD 2025-12
 */
class VariantPanelContainer extends Component
{
    use WithPagination;
    use TypeInlineCrud;
    use ValueInlineCrud;

    // Panel visibility
    public bool $showValuePanel = false;
    public bool $showProductPanel = false;

    // Selected state
    public ?int $selectedTypeId = null;
    public array $selectedValueIds = [];

    // Filter mode for product search
    public string $filterMode = 'any'; // 'any' (OR) | 'all' (AND)

    // Product search
    public string $productSearch = '';

    // Sort
    public string $sortField = 'sku';
    public string $sortDirection = 'asc';

    // Services (lazy loaded)
    private ?ProductVariantSearchService $searchService = null;

    protected function getSearchService(): ProductVariantSearchService
    {
        return $this->searchService ??= app(ProductVariantSearchService::class);
    }

    public function mount(): void
    {
        // Auto-select first type if none selected
        if (!$this->selectedTypeId) {
            $firstType = AttributeType::active()->ordered()->first();
            if ($firstType) {
                $this->selectedTypeId = $firstType->id;
                $this->showValuePanel = true;
            }
        } else {
            $this->showValuePanel = true;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get all attribute types for left panel
     */
    #[Computed(persist: true)]
    public function attributeTypes(): Collection
    {
        return AttributeType::active()
            ->ordered()
            ->withCount('values')
            ->get();
    }

    /**
     * Get selected attribute type
     */
    #[Computed]
    public function selectedType(): ?AttributeType
    {
        return $this->selectedTypeId
            ? AttributeType::find($this->selectedTypeId)
            : null;
    }

    /**
     * Get values for selected type with product counts
     */
    #[Computed]
    public function valuesWithCounts(): Collection
    {
        if (!$this->selectedTypeId) {
            return collect([]);
        }

        return $this->getSearchService()->getValuesWithProductCounts($this->selectedTypeId);
    }

    /**
     * Get products matching selected values
     */
    #[Computed]
    public function products()
    {
        if (empty($this->selectedValueIds)) {
            return null;
        }

        if (!empty($this->productSearch)) {
            return $this->getSearchService()->searchWithVariantFilter(
                $this->productSearch,
                $this->selectedValueIds,
                $this->filterMode,
                15
            );
        }

        return $this->getSearchService()->findByVariantValues(
            $this->selectedValueIds,
            $this->filterMode,
            $this->sortField,
            $this->sortDirection,
            15
        );
    }

    /**
     * Get product count for badge
     */
    #[Computed]
    public function productCount(): int
    {
        if (empty($this->selectedValueIds)) {
            return 0;
        }

        return $this->getSearchService()->countProductsByVariantValues(
            $this->selectedValueIds,
            $this->filterMode
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EVENT HANDLERS
    |--------------------------------------------------------------------------
    */

    /**
     * Select an attribute type
     */
    public function selectType(int $typeId): void
    {
        $this->selectedTypeId = $typeId;
        $this->selectedValueIds = [];
        $this->showValuePanel = true;
        $this->showProductPanel = false;

        // Cancel any inline edit forms
        $this->cancelTypeEdit();
        $this->cancelValueCreate();
        $this->cancelValueEdit();

        // Clear computed caches
        unset($this->selectedType, $this->valuesWithCounts, $this->products, $this->productCount);
    }

    /**
     * Toggle a value selection
     */
    public function toggleValue(int $valueId): void
    {
        if (in_array($valueId, $this->selectedValueIds)) {
            $this->selectedValueIds = array_values(array_diff($this->selectedValueIds, [$valueId]));
        } else {
            $this->selectedValueIds[] = $valueId;
        }

        $this->showProductPanel = !empty($this->selectedValueIds);

        // Clear computed caches
        unset($this->products, $this->productCount);
        $this->resetPage();
    }

    /**
     * Select all values for current type
     */
    public function selectAllValues(): void
    {
        $this->selectedValueIds = $this->valuesWithCounts->pluck('id')->toArray();
        $this->showProductPanel = true;
        unset($this->products, $this->productCount);
        $this->resetPage();
    }

    /**
     * Clear all value selections
     */
    public function clearValueSelection(): void
    {
        $this->selectedValueIds = [];
        $this->showProductPanel = false;
        unset($this->products, $this->productCount);
    }

    /**
     * Change filter mode (OR/AND)
     */
    public function setFilterMode(string $mode): void
    {
        $this->filterMode = in_array($mode, ['any', 'all']) ? $mode : 'any';
        unset($this->products, $this->productCount);
        $this->resetPage();
    }

    /**
     * Sort products
     */
    public function sortProducts(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        unset($this->products);
        $this->resetPage();
    }

    /**
     * Search products
     */
    public function updatedProductSearch(): void
    {
        unset($this->products);
        $this->resetPage();
    }

    /**
     * Refresh after value CRUD operations
     */
    #[On('attribute-values-updated')]
    public function refreshValues(): void
    {
        unset($this->valuesWithCounts, $this->products, $this->productCount);
    }

    /**
     * Refresh attribute types after CRUD operations
     */
    #[On('attribute-types-updated')]
    public function refreshTypes(): void
    {
        unset($this->attributeTypes);
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.admin.variants.variant-panel-container');
    }
}
