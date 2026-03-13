<?php

namespace App\Http\Livewire\Admin\Export\Traits;

use App\Models\PrestaShopShop;
use App\Models\ExportProfile;

/**
 * ProfileFormFilters Trait
 *
 * Filter logic for ExportProfileForm wizard (Step 3).
 * Manages basic product filters: active status, categories, shops.
 *
 * Advanced filters (manufacturer, stock, price range, etc.) are handled
 * by ProfileFormAdvancedFilters trait.
 *
 * @package App\Http\Livewire\Admin\Export\Traits
 */
trait ProfileFormFilters
{
    // Filter properties
    public string $filterIsActive = 'true';  // 'all'/'true'/'false'
    public array $filterCategoryIds = [];
    public array $filterShopIds = [];

    // Data sources (loaded from DB)
    public array $availableShops = [];

    /**
     * Initialize filter data sources from DB.
     */
    public function initFilters(): void
    {
        $this->availableShops = PrestaShopShop::active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($s) => ['id' => $s->id, 'name' => $s->name])
            ->toArray();
    }

    /**
     * Build filter config array for profile storage.
     *
     * Merges basic filters with advanced filters (from ProfileFormAdvancedFilters trait)
     * when available.
     *
     * @return array<string, mixed>
     */
    public function getFilterConfig(): array
    {
        $config = [];

        if ($this->filterIsActive !== '' && $this->filterIsActive !== 'all') {
            $config['is_active'] = $this->filterIsActive;
        }

        if (!empty($this->filterCategoryIds)) {
            $config['category_ids'] = array_map('intval', $this->filterCategoryIds);
        }

        if (!empty($this->filterShopIds)) {
            $config['shop_ids'] = array_map('intval', $this->filterShopIds);
        }

        // Merge with advanced filters (from ProfileFormAdvancedFilters trait)
        if (method_exists($this, 'getAdvancedFilterConfig')) {
            $config = array_merge($config, $this->getAdvancedFilterConfig());
        }

        // Merge with category products exclusion (from ProfileFormCategoryProducts trait)
        if (method_exists($this, 'getCategoryProductsFilterConfig')) {
            $config = array_merge($config, $this->getCategoryProductsFilterConfig());
        }

        return $config;
    }

    /**
     * Load filters from an existing profile.
     *
     * Handles backward compatibility with old profile format:
     * - Old bool `is_active` -> new string format
     * - Old `has_stock` bool -> maps to `filterStockStatus` in advanced trait
     * - Old `manufacturer` string -> kept for ProductExportService backward compat
     */
    public function loadFiltersFromProfile(ExportProfile $profile): void
    {
        $filterConfig = $profile->filter_config ?? [];

        // is_active (backward compat: old bool format)
        if (isset($filterConfig['is_active'])) {
            $val = $filterConfig['is_active'];
            $this->filterIsActive = is_bool($val) ? ($val ? 'true' : 'false') : (string) $val;
        } else {
            $this->filterIsActive = 'all';
        }

        // Backward compat: old has_stock bool -> map to filterStockStatus in advanced trait
        if (isset($filterConfig['has_stock']) && property_exists($this, 'filterStockStatus')) {
            if (filter_var($filterConfig['has_stock'], FILTER_VALIDATE_BOOLEAN)) {
                $this->filterStockStatus = 'in_stock';
            }
        }

        $this->filterCategoryIds = array_map(
            'intval',
            (array) ($filterConfig['category_ids'] ?? [])
        );

        $this->filterShopIds = array_map(
            'strval',
            (array) ($filterConfig['shop_ids'] ?? [])
        );

        // Load advanced filters from profile (if trait is used)
        if (method_exists($this, 'loadAdvancedFiltersFromProfile')) {
            $this->loadAdvancedFiltersFromProfile($profile);
        }

        // Load excluded product IDs (from ProfileFormCategoryProducts trait)
        if (method_exists($this, 'loadCategoryProductsFromProfile')) {
            $this->loadCategoryProductsFromProfile($profile);
        }
    }

    /**
     * Toggle a shop in the filter selection.
     */
    public function toggleShop(int $shopId): void
    {
        $key = (string) $shopId;

        if (in_array($key, $this->filterShopIds, true)) {
            $this->filterShopIds = array_values(
                array_filter($this->filterShopIds, fn($id) => $id !== $key)
            );
        } else {
            $this->filterShopIds[] = $key;
        }
    }
}
