<?php

namespace App\Http\Livewire\Admin\Export\Traits;

use App\Models\Category;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ExportProfile;

/**
 * ProfileFormFilters Trait
 *
 * Filter logic for ExportProfileForm wizard (Step 3).
 * Manages product filters: active status, stock, categories, manufacturer, shops.
 *
 * @package App\Http\Livewire\Admin\Export\Traits
 */
trait ProfileFormFilters
{
    // Filter properties
    public bool $filterIsActive = true;
    public bool $filterHasStock = false;
    public array $filterCategoryIds = [];
    public string $filterManufacturer = '';
    public array $filterShopIds = [];

    // Data sources (loaded from DB)
    public array $availableCategories = [];
    public array $availableManufacturers = [];
    public array $availableShops = [];

    /**
     * Initialize filter data sources from DB.
     */
    public function initFilters(): void
    {
        $this->availableCategories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($c) => ['id' => $c->id, 'name' => $c->name])
            ->toArray();

        $this->availableManufacturers = Product::query()
            ->whereNotNull('manufacturer')
            ->where('manufacturer', '!=', '')
            ->distinct()
            ->orderBy('manufacturer')
            ->pluck('manufacturer')
            ->toArray();

        $this->availableShops = PrestaShopShop::active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($s) => ['id' => $s->id, 'name' => $s->name])
            ->toArray();
    }

    /**
     * Build filter config array for profile storage.
     *
     * @return array<string, mixed>
     */
    public function getFilterConfig(): array
    {
        $config = [];

        if ($this->filterIsActive) {
            $config['is_active'] = 'true';
        }

        if ($this->filterHasStock) {
            $config['has_stock'] = 'true';
        }

        if (!empty($this->filterCategoryIds)) {
            $config['category_ids'] = array_map('intval', $this->filterCategoryIds);
        }

        if (!empty(trim($this->filterManufacturer))) {
            $config['manufacturer'] = trim($this->filterManufacturer);
        }

        if (!empty($this->filterShopIds)) {
            $config['shop_ids'] = array_map('intval', $this->filterShopIds);
        }

        return $config;
    }

    /**
     * Load filters from an existing profile.
     */
    public function loadFiltersFromProfile(ExportProfile $profile): void
    {
        $filterConfig = $profile->filter_config ?? [];

        $this->filterIsActive = isset($filterConfig['is_active'])
            && filter_var($filterConfig['is_active'], FILTER_VALIDATE_BOOLEAN);

        $this->filterHasStock = isset($filterConfig['has_stock'])
            && filter_var($filterConfig['has_stock'], FILTER_VALIDATE_BOOLEAN);

        $this->filterCategoryIds = array_map(
            'strval',
            (array) ($filterConfig['category_ids'] ?? [])
        );

        $this->filterManufacturer = (string) ($filterConfig['manufacturer'] ?? '');

        $this->filterShopIds = array_map(
            'strval',
            (array) ($filterConfig['shop_ids'] ?? [])
        );
    }

    /**
     * Toggle a category in the filter selection.
     */
    public function toggleCategory(int $categoryId): void
    {
        $key = (string) $categoryId;

        if (in_array($key, $this->filterCategoryIds, true)) {
            $this->filterCategoryIds = array_values(
                array_filter($this->filterCategoryIds, fn($id) => $id !== $key)
            );
        } else {
            $this->filterCategoryIds[] = $key;
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
