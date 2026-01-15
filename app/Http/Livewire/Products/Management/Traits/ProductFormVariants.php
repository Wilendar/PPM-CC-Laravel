<?php

namespace App\Http\Livewire\Products\Management\Traits;

/**
 * ProductFormVariants - Orchestrator Trait for Variant Management
 *
 * This trait composes all variant-related traits into a single entry point
 * for the ProductForm Livewire component.
 *
 * REFACTORED FROM: ProductFormVariants.php (1369 lines)
 * NEW STRUCTURE:
 * - VariantCrudTrait (~290 lines) - CRUD operations
 * - VariantPriceTrait (~180 lines) - Price management
 * - VariantStockTrait (~160 lines) - Stock management
 * - VariantImageTrait (~240 lines) - Image management
 * - VariantAttributeTrait (~110 lines) - Attribute handling
 * - VariantValidation (~460 lines) - Validation rules (existing)
 *
 * TOTAL: ~1440 lines split into 6 files (< 300 lines each)
 * THIS FILE: ~100 lines (orchestrator + initialization)
 *
 * USAGE IN LIVEWIRE COMPONENT:
 * ```php
 * class ProductForm extends Component
 * {
 *     use ProductFormVariants;
 *
 *     public function mount(Product $product)
 *     {
 *         $this->product = $product;
 *         $this->initializeVariantData();
 *     }
 * }
 * ```
 *
 * @package App\Http\Livewire\Products\Management\Traits
 * @version 2.0 (Refactored)
 * @since ETAP_05b FAZA 1
 */
trait ProductFormVariants
{
    /*
    |--------------------------------------------------------------------------
    | COMPOSED TRAITS
    |--------------------------------------------------------------------------
    */

    use VariantValidation;       // Validation rules for all variant operations
    use VariantCrudTrait;        // Create, Update, Delete, Duplicate variants
    use VariantPriceTrait;       // Price management per price group
    use VariantStockTrait;       // Stock management per warehouse
    use VariantImageTrait;       // Image upload, assign, delete, cover
    use VariantAttributeTrait;   // Attribute selection and management

    /*
    |--------------------------------------------------------------------------
    | INITIALIZATION
    |--------------------------------------------------------------------------
    */

    /**
     * Initialize all variant-related data
     *
     * Should be called in mount() or boot() of the Livewire component
     */
    public function initializeVariantData(): void
    {
        if (!$this->product) {
            return;
        }

        // Load product with variants and related data
        $this->product->load([
            'variants.attributes',
            'variants.prices.priceGroup',
            'variants.stock.warehouse',
            'variants.images',
        ]);

        // Initialize price and stock grids if variant master
        if ($this->product->is_variant_master) {
            $this->loadVariantPrices();
            $this->loadVariantStock();
        }
    }

    /**
     * Refresh variant data after changes
     *
     * Call this after bulk operations or sync with external systems
     */
    public function refreshVariantData(): void
    {
        $this->product->refresh();
        $this->initializeVariantData();

        $this->dispatch('variant-data-refreshed');
    }

    /**
     * Check if product has variants
     */
    public function hasVariants(): bool
    {
        return $this->product
            && $this->product->is_variant_master
            && $this->product->variants->count() > 0;
    }

    /**
     * Get variants count
     */
    public function getVariantsCount(): int
    {
        return $this->product?->variants?->count() ?? 0;
    }

    /**
     * Get default variant
     */
    public function getDefaultVariant(): ?\App\Models\ProductVariant
    {
        if (!$this->product) {
            return null;
        }

        return $this->product->variants()
            ->where('is_default', true)
            ->first();
    }

    /**
     * Get active variants only
     */
    public function getActiveVariants(): \Illuminate\Support\Collection
    {
        if (!$this->product) {
            return collect();
        }

        return $this->product->variants()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();
    }
}
