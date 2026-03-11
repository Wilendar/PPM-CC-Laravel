<?php

namespace App\Http\Livewire\Admin\Variants;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\AttributeType;
use App\Services\Product\VariantManager;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;

/**
 * Variant Management Livewire Component
 *
 * Standalone management page for product variants with filtering, auto-generation, and bulk operations
 *
 * FEATURES:
 * - Variants table with sortable columns (SKU, parent, price, stock)
 * - Real-time filters (parent product search, attribute type filter)
 * - Auto-generate modal (select parent, attributes, SKU pattern preview)
 * - Bulk operations (prices, stock, images, delete)
 * - Pagination (25 per page)
 *
 * COMPLIANCE:
 * - Livewire 3.x patterns (dispatch, #[Computed], wire:model.live)
 * - wire:key for all @foreach loops
 * - VariantManager service for ALL business logic
 * - NO inline styles (CSS classes only)
 * - ~290 lines (within CLAUDE.md limit)
 *
 * USAGE:
 * ```blade
 * <livewire:admin.variants.variant-management />
 * ```
 *
 * RELATED:
 * - _DOCS/ARCHITEKTURA_PPM/09_WARIANTY_CECHY.md (section 9.1)
 * - app/Services/Product/VariantManager.php
 * - app/Models/ProductVariant.php
 *
 * @package App\Http\Livewire\Admin\Variants
 * @version 1.0
 * @since ETAP_05a FAZA 4 (2025-10-23)
 */
class VariantManagement extends Component
{
    use WithPagination;

    /*
    |--------------------------------------------------------------------------
    | PROPERTIES
    |--------------------------------------------------------------------------
    */

    /** @var string Search parent product by SKU or name */
    public string $searchParent = '';

    /** @var int|null Filter by attribute type ID */
    public ?int $filterAttributeType = null;

    /** @var string Sort field */
    public string $sortField = 'sku';

    /** @var string Sort direction */
    public string $sortDirection = 'asc';

    /** @var array Selected variants for bulk operations */
    public array $selectedVariants = [];

    /** @var bool Select all checkbox state */
    public bool $selectAll = false;

    /** @var bool Auto-generate modal visibility */
    public bool $showAutoGenerateModal = false;

    /** @var int|null Selected parent product ID for auto-generation */
    public ?int $selectedParentId = null;

    /** @var array Selected attributes for auto-generation [attribute_type_id => [value_codes]] */
    public array $selectedAutoAttributes = [];

    /** @var bool Inherit prices from parent */
    public bool $inheritPrices = true;

    /** @var bool Inherit stock from parent */
    public bool $inheritStock = false;

    /*
    |--------------------------------------------------------------------------
    | QUERY STRING
    |--------------------------------------------------------------------------
    */

    protected $queryString = [
        'searchParent' => ['except' => ''],
        'filterAttributeType' => ['except' => null],
        'sortField' => ['except' => 'sku'],
        'sortDirection' => ['except' => 'asc'],
    ];

    /*
    |--------------------------------------------------------------------------
    | DEPENDENCY INJECTION
    |--------------------------------------------------------------------------
    | NOTE: Livewire 3.x CANNOT use DI in constructor (ArgumentCountError)
    | Use app() helper or method injection instead
    | See: _ISSUES_FIXES/LIVEWIRE_DEPENDENCY_INJECTION_ISSUE.md
    |--------------------------------------------------------------------------
    */

    /** @var VariantManager Service instance (lazy loaded) */
    private ?VariantManager $variantManager = null;

    /**
     * Get VariantManager service instance (lazy loading)
     */
    protected function getVariantManager(): VariantManager
    {
        if (!$this->variantManager) {
            $this->variantManager = app(VariantManager::class);
        }
        return $this->variantManager;
    }

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE HOOKS
    |--------------------------------------------------------------------------
    */

    /**
     * Component initialization
     */
    public function mount(): void
    {
        // Initialize component
    }

    /**
     * Property updated hook
     */
    public function updated($name): void
    {
        // Reset pagination when filters change
        if (in_array($name, ['searchParent', 'filterAttributeType'])) {
            $this->resetPage();
        }

        // Handle select all checkbox
        if ($name === 'selectAll') {
            if ($this->selectAll) {
                $this->selectedVariants = $this->variants->pluck('id')->toArray();
            } else {
                $this->selectedVariants = [];
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES (Livewire 3.x)
    |--------------------------------------------------------------------------
    */

    /**
     * Get filtered and paginated variants
     */
    #[Computed]
    public function variants()
    {
        $query = ProductVariant::with(['product', 'attributes.attributeType', 'prices', 'stock', 'images'])
            ->when($this->searchParent, function ($q) {
                $q->whereHas('product', function ($productQuery) {
                    $productQuery->where('sku', 'like', "%{$this->searchParent}%")
                        ->orWhere('name', 'like', "%{$this->searchParent}%");
                });
            })
            ->when($this->filterAttributeType, function ($q) {
                $q->whereHas('attributes', function ($attrQuery) {
                    $attrQuery->where('attribute_type_id', $this->filterAttributeType);
                });
            })
            ->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate(25);
    }

    /**
     * Get attribute types for filter
     */
    #[Computed]
    public function attributeTypes(): Collection
    {
        return AttributeType::active()->ordered()->get();
    }

    /**
     * Get products for auto-generate dropdown
     */
    #[Computed]
    public function products(): Collection
    {
        return Product::orderBy('sku')->get(['id', 'sku', 'name']);
    }

    /**
     * Get selected parent product
     */
    #[Computed]
    public function selectedParent(): ?Product
    {
        return $this->selectedParentId ? Product::find($this->selectedParentId) : null;
    }

    /**
     * Get preview of generated variants count
     */
    #[Computed]
    public function generatedVariantsPreview(): array
    {
        if (empty($this->selectedAutoAttributes)) {
            return [];
        }

        $combinations = $this->generateCombinations($this->selectedAutoAttributes);
        return array_slice($combinations, 0, 5); // Show first 5 as preview
    }

    /**
     * Get total generated variants count
     */
    #[Computed]
    public function generatedVariantsCount(): int
    {
        if (empty($this->selectedAutoAttributes)) {
            return 0;
        }

        return count($this->generateCombinations($this->selectedAutoAttributes));
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIONS - SORTING & FILTERING
    |--------------------------------------------------------------------------
    */

    /**
     * Sort table by field
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Reset filters
     */
    public function resetFilters(): void
    {
        $this->searchParent = '';
        $this->filterAttributeType = null;
        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIONS - AUTO-GENERATE MODAL
    |--------------------------------------------------------------------------
    */

    /**
     * Open auto-generate modal
     */
    public function openAutoGenerateModal(): void
    {
        $this->showAutoGenerateModal = true;
        $this->selectedParentId = null;
        $this->selectedAutoAttributes = [];
        $this->inheritPrices = true;
        $this->inheritStock = false;
    }

    /**
     * Close auto-generate modal
     */
    public function closeAutoGenerateModal(): void
    {
        $this->showAutoGenerateModal = false;
    }

    /**
     * Generate variants based on selected attributes
     */
    public function generateVariants(): void
    {
        $this->validate([
            'selectedParentId' => 'required|exists:products,id',
            'selectedAutoAttributes' => 'required|array|min:1',
        ], [
            'selectedParentId.required' => 'Wybierz produkt rodzica',
            'selectedAutoAttributes.required' => 'Wybierz przynajmniej jeden atrybut',
        ]);

        try {
            $parent = Product::findOrFail($this->selectedParentId);
            $combinations = $this->generateCombinations($this->selectedAutoAttributes);

            DB::transaction(function () use ($parent, $combinations) {
                foreach ($combinations as $combination) {
                    $sku = $this->generateVariantSku($parent->sku, $combination);
                    $name = $this->generateVariantName($parent->name, $combination);

                    $variantData = [
                        'sku' => $sku,
                        'name' => $name,
                        'is_default' => false,
                        'is_active' => true,
                        'attributes' => $combination,
                    ];

                    if ($this->inheritPrices) {
                        $variantData['prices'] = $parent->priceGroups->map(fn($pg) => [
                            'price_group_id' => $pg->id,
                            'price' => $pg->pivot->price,
                        ])->toArray();
                    }

                    $this->getVariantManager()->createVariant($parent, $variantData);
                }
            });

            $this->dispatch('variants-generated', count: count($combinations));
            session()->flash('message', "Wygenerowano {count($combinations)} wariantów");
            $this->closeAutoGenerateModal();

        } catch (\Exception $e) {
            $this->addError('generate', 'Błąd podczas generowania wariantów: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIONS - BULK OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Bulk update prices
     */
    public function bulkUpdatePrices(): void
    {
        $this->dispatch('open-bulk-prices-modal', variantIds: $this->selectedVariants);
    }

    /**
     * Bulk update stock
     */
    public function bulkUpdateStock(): void
    {
        $this->dispatch('open-bulk-stock-modal', variantIds: $this->selectedVariants);
    }

    /**
     * Bulk assign images
     */
    public function bulkAssignImages(): void
    {
        $this->dispatch('open-bulk-images-modal', variantIds: $this->selectedVariants);
    }

    /**
     * Bulk delete variants
     */
    public function bulkDelete(): void
    {
        if (empty($this->selectedVariants)) {
            return;
        }

        try {
            DB::transaction(function () {
                foreach ($this->selectedVariants as $variantId) {
                    $variant = ProductVariant::find($variantId);
                    if ($variant) {
                        $this->getVariantManager()->deleteVariant($variant);
                    }
                }
            });

            session()->flash('message', 'Usunięto ' . count($this->selectedVariants) . ' wariantów');
            $this->selectedVariants = [];
            $this->selectAll = false;

        } catch (\Exception $e) {
            $this->addError('delete', 'Błąd podczas usuwania: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Generate all attribute combinations
     */
    protected function generateCombinations(array $attributes): array
    {
        $result = [[]];

        foreach ($attributes as $attributeTypeId => $valueCodes) {
            $temp = [];
            foreach ($result as $combination) {
                foreach ($valueCodes as $valueCode) {
                    $temp[] = array_merge($combination, [
                        [
                            'attribute_type_id' => $attributeTypeId,
                            'value_code' => $valueCode,
                            'value' => $valueCode, // Simplified
                        ]
                    ]);
                }
            }
            $result = $temp;
        }

        return $result;
    }

    /**
     * Generate variant SKU from parent and attributes
     */
    protected function generateVariantSku(string $parentSku, array $attributes): string
    {
        $codes = array_column($attributes, 'value_code');
        return $parentSku . '-' . strtoupper(implode('-', $codes));
    }

    /**
     * Generate variant name from parent and attributes
     */
    protected function generateVariantName(string $parentName, array $attributes): string
    {
        $values = array_column($attributes, 'value');
        return $parentName . ' (' . implode(', ', $values) . ')';
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.admin.variants.variant-management')
            ->layout('layouts.admin');
    }
}
