<?php

namespace App\Http\Livewire\Products\Management\Traits;

use App\Models\Category;
use App\Models\ProductType;
use App\Models\PrestaShopShop;
use App\Models\ProductAttribute;
use App\Models\BusinessPartner;
use Illuminate\Support\Facades\Log;

/**
 * ProductFormComputed Trait
 *
 * Handles computed properties for ProductForm component
 * Optimized to reduce Livewire snapshot size per CLAUDE.md guidelines
 *
 * @package App\Http\Livewire\Products\Management\Traits
 */
trait ProductFormComputed
{
    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES - Optimized for Livewire Performance
    |--------------------------------------------------------------------------
    */

    /**
     * Get available categories for selection
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCategoriesProperty()
    {
        try {
            return Category::active()
                ->orderBy('name')
                ->get();
        } catch (\Exception $e) {
            Log::warning('Could not load categories', ['error' => $e->getMessage()]);
            return collect([]);
        }
    }

    /**
     * Get available product types
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductTypesProperty()
    {
        try {
            return ProductType::active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        } catch (\Exception $e) {
            Log::warning('Could not load product types', ['error' => $e->getMessage()]);
            return collect([]);
        }
    }

    /**
     * Get available shops (computed property to avoid serialization)
     *
     * @return array
     */
    public function getAvailableShopsProperty(): array
    {
        try {
            return PrestaShopShop::active()
                ->orderBy('name')
                ->get()
                ->map(function ($shop) {
                    return [
                        'id' => $shop->id,
                        'name' => $shop->name,
                        'url' => $shop->url,
                        'is_active' => $shop->is_active,
                        'connection_status' => $shop->connection_status,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::warning('Could not load shops', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get available attributes (computed property to avoid serialization)
     *
     * @return array
     */
    public function getAvailableAttributesProperty(): array
    {
        try {
            return ProductAttribute::active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(function ($attribute) {
                    return [
                        'id' => $attribute->id,
                        'name' => $attribute->name,
                        'code' => $attribute->code,
                        'attribute_type' => $attribute->attribute_type,
                        'is_required' => $attribute->is_required,
                        'help_text' => $attribute->help_text,
                        'options' => $attribute->options_parsed ?? [],
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            // Fallback if attributes table doesn't exist yet
            Log::warning('Could not load attributes', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS PARTNER DROPDOWNS - Computed Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Get suppliers for dropdown selection
     *
     * @return \Illuminate\Support\Collection
     */
    public function getSuppliersForDropdownProperty()
    {
        try {
            return BusinessPartner::getForDropdown(BusinessPartner::TYPE_SUPPLIER);
        } catch (\Exception $e) {
            Log::warning('Could not load suppliers', ['error' => $e->getMessage()]);
            return collect([]);
        }
    }

    /**
     * Get manufacturers for dropdown selection
     *
     * @return \Illuminate\Support\Collection
     */
    public function getManufacturersForDropdownProperty()
    {
        try {
            return BusinessPartner::getForDropdown(BusinessPartner::TYPE_MANUFACTURER);
        } catch (\Exception $e) {
            Log::warning('Could not load manufacturers', ['error' => $e->getMessage()]);
            return collect([]);
        }
    }

    /**
     * Get importers for dropdown selection
     *
     * @return \Illuminate\Support\Collection
     */
    public function getImportersForDropdownProperty()
    {
        try {
            return BusinessPartner::getForDropdown(BusinessPartner::TYPE_IMPORTER);
        } catch (\Exception $e) {
            Log::warning('Could not load importers', ['error' => $e->getMessage()]);
            return collect([]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULATED PROPERTIES - Dynamic Values
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate product volume from dimensions
     *
     * @return float|null
     */
    public function getCalculatedVolumeProperty(): ?float
    {
        if ($this->height && $this->width && $this->length) {
            return round($this->height * $this->width * $this->length, 3);
        }
        return null;
    }

    /**
     * Check if short description is too long
     *
     * @return bool
     */
    public function getShortDescriptionWarningProperty(): bool
    {
        return $this->shortDescriptionCount > 800; // Warning at 80% of 1000 limit
    }

    /**
     * Check if long description is too long
     *
     * @return bool
     */
    public function getLongDescriptionWarningProperty(): bool
    {
        return $this->longDescriptionCount > 8000; // Warning at 80% of 10000 limit
    }

    /**
     * Check if form has unsaved changes
     *
     * @return bool
     */
    public function getHasChangesProperty(): bool
    {
        if (!$this->isEditMode) {
            // For new products, check if any required field is filled
            return !empty($this->sku) || !empty($this->name);
        }

        // For existing products, compare with original values
        return $this->product && (
            $this->sku !== $this->product->sku ||
            $this->name !== $this->product->name ||
            $this->short_description !== ($this->product->short_description ?? '') ||
            $this->long_description !== ($this->product->long_description ?? '') ||
            $this->weight !== $this->product->weight ||
            $this->height !== $this->product->height ||
            $this->width !== $this->product->width ||
            $this->length !== $this->product->length ||
            $this->tax_rate !== $this->product->tax_rate ||
            $this->is_active !== $this->product->is_active
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MULTI-STORE COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get current selected categories (default or shop-specific)
     *
     * @return array
     */
    public function getCurrentSelectedCategoriesProperty(): array
    {
        if ($this->activeShopId !== null && isset($this->shopCategories[$this->activeShopId])) {
            return $this->shopCategories[$this->activeShopId]['selected'];
        }
        return $this->selectedCategories;
    }

    /**
     * Get current primary category (default or shop-specific)
     *
     * @return int|null
     */
    public function getCurrentPrimaryCategoryIdProperty(): ?int
    {
        if ($this->activeShopId !== null && isset($this->shopCategories[$this->activeShopId])) {
            return $this->shopCategories[$this->activeShopId]['primary'];
        }
        return $this->primaryCategoryId;
    }
}