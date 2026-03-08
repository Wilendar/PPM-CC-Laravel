<?php

namespace App\Services\Export;

use App\Models\ExportProfile;
use App\Models\Product;
use App\Models\PriceGroup;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * ProductExportService - Query building, filtering, field mapping for product export.
 */
class ProductExportService
{
    private ?array $priceGroupCache = null;
    private ?array $warehouseCache = null;

    public function __construct(
        private ExportProfileService $profileService
    ) {}

    /** Buduje Eloquent query z eager loading na podstawie profilu. */
    public function buildQuery(ExportProfile $profile): Builder
    {
        $selectedFields = $this->getSelectedFieldKeys($profile);
        $query = Product::query()->with($this->resolveEagerLoading($selectedFields));
        $filterConfig = $profile->filter_config ?? [];

        return !empty($filterConfig) ? $this->applyFilters($query, $filterConfig) : $query;
    }

    /** Wykonuje query i mapuje na flat arrays. */
    public function getProducts(ExportProfile $profile): array
    {
        return $this->applyFieldSelection($this->buildQuery($profile)->get(), $profile);
    }

    /** Stosuje filtry z filter_config. */
    public function applyFilters(Builder $query, array $filterConfig): Builder
    {
        if (isset($filterConfig['is_active']) && $filterConfig['is_active'] !== '') {
            $query->where('is_active', filter_var($filterConfig['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filterConfig['category_ids'])) {
            $ids = (array) $filterConfig['category_ids'];
            $query->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $ids));
        }

        if (!empty($filterConfig['manufacturer'])) {
            $val = $filterConfig['manufacturer'];
            is_array($val) ? $query->whereIn('manufacturer', $val) : $query->where('manufacturer', $val);
        }

        if (isset($filterConfig['has_stock']) && $filterConfig['has_stock'] !== '') {
            $has = filter_var($filterConfig['has_stock'], FILTER_VALIDATE_BOOLEAN);
            $stockCb = fn (Builder $q) => $q->where('available_quantity', '>', 0);
            $has ? $query->whereHas('activeStock', $stockCb) : $query->whereDoesntHave('activeStock', $stockCb);
        }

        if (!empty($filterConfig['shop_ids'])) {
            $shopIds = (array) $filterConfig['shop_ids'];
            $query->whereHas('shopData', fn (Builder $q) => $q->whereIn('shop_id', $shopIds)->where('is_published', true));
        }

        return $query;
    }

    /** Mapuje kolekcje produktow na flat arrays z wybranymi polami. */
    public function applyFieldSelection(Collection $products, ExportProfile $profile): array
    {
        $selectedFields = $this->getSelectedFieldKeys($profile);
        $this->warmCaches($selectedFields);

        $rows = [];
        foreach ($products as $product) {
            $row = [];
            foreach ($selectedFields as $fieldKey) {
                $row[$fieldKey] = $this->resolveFieldValue($product, $fieldKey);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** Zwraca naglowki kolumn dla wybranych pol profilu. */
    public function getExportHeaders(ExportProfile $profile): array
    {
        $selectedFields = $this->getSelectedFieldKeys($profile);
        $allFields = $this->profileService->getAvailableFields();
        $headers = [];

        foreach ($selectedFields as $fieldKey) {
            $headers[$fieldKey] = $this->resolveFieldLabel($fieldKey, $allFields);
        }

        return $headers;
    }

    // === Field Resolution ===

    private function resolveFieldValue(Product $product, string $fieldKey): mixed
    {
        // Direct model attributes
        static $directFields = [
            'sku', 'name', 'slug', 'short_description', 'long_description',
            'ean', 'weight', 'height', 'width', 'length', 'tax_rate',
            'manufacturer', 'supplier_code',
            'is_active', 'is_featured', 'is_variant_master',
            'created_at', 'updated_at', 'meta_title', 'meta_description',
        ];

        if (in_array($fieldKey, $directFields, true)) {
            $value = $product->{$fieldKey};
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }
            return is_bool($value) ? ($value ? 'TAK' : 'NIE') : $value;
        }

        // Pricing
        if (str_starts_with($fieldKey, 'price_net_') || str_starts_with($fieldKey, 'price_gross_')) {
            return $this->resolvePriceField($product, $fieldKey);
        }

        // Stock
        if (str_starts_with($fieldKey, 'stock_') || str_starts_with($fieldKey, 'reserved_')) {
            return $this->resolveStockField($product, $fieldKey);
        }

        // Categories
        if ($fieldKey === 'category_path') {
            return $this->resolveCategoryPath($product);
        }
        if ($fieldKey === 'category_primary') {
            return $this->resolvePrimaryCategory($product);
        }

        // Media
        if ($fieldKey === 'image_url_main') {
            return $product->primary_image;
        }
        if ($fieldKey === 'image_urls_all') {
            $media = $product->relationLoaded('media') ? $product->media : $product->media()->active()->get();
            return $media->isEmpty() ? null : $media->pluck('url')->implode(' | ');
        }

        return null;
    }

    private function resolvePriceField(Product $product, string $fieldKey): ?string
    {
        $isNet = str_starts_with($fieldKey, 'price_net_');
        $groupCode = substr($fieldKey, $isNet ? 10 : 12); // strlen('price_net_')=10, strlen('price_gross_')=12

        $prices = $product->relationLoaded('validPrices')
            ? $product->validPrices
            : $product->validPrices()->with('priceGroup')->get();

        foreach ($prices as $price) {
            $code = $price->relationLoaded('priceGroup') ? ($price->priceGroup->code ?? null) : null;
            if ($code === null && $this->priceGroupCache !== null) {
                $code = ($this->priceGroupCache[$price->price_group_id] ?? null)?->code;
            }
            if ($code === $groupCode) {
                $value = $isNet ? $price->price_net : $price->price_gross;
                return $value !== null ? number_format((float) $value, 2, '.', '') : null;
            }
        }

        return null;
    }

    private function resolveStockField(Product $product, string $fieldKey): ?int
    {
        $isQty = str_starts_with($fieldKey, 'stock_');
        $whCode = substr($fieldKey, $isQty ? 6 : 9); // strlen('stock_')=6, strlen('reserved_')=9

        $stocks = $product->relationLoaded('activeStock')
            ? $product->activeStock
            : $product->activeStock()->with('warehouse')->get();

        foreach ($stocks as $stock) {
            $code = $stock->relationLoaded('warehouse') ? ($stock->warehouse->code ?? null) : null;
            if ($code === null && $this->warehouseCache !== null) {
                $code = ($this->warehouseCache[$stock->warehouse_id] ?? null)?->code;
            }
            if ($code === $whCode) {
                return (int) ($isQty ? $stock->available_quantity : $stock->reserved_quantity);
            }
        }

        return null;
    }

    private function resolveCategoryPath(Product $product): ?string
    {
        $cats = $product->relationLoaded('categories') ? $product->categories : $product->categories()->get();
        return $cats->isEmpty() ? null : $cats->map(fn ($c) => $c->getFullPath())->implode(' | ');
    }

    private function resolvePrimaryCategory(Product $product): ?string
    {
        $cats = $product->relationLoaded('categories') ? $product->categories : $product->categories()->get();
        $primary = $cats->first(fn ($c) => $c->pivot->is_primary ?? false);
        return $primary ? $primary->getFullPath() : $cats->first()?->name;
    }

    // === Eager Loading ===

    private function resolveEagerLoading(array $fields): array
    {
        $load = [];
        $flags = ['pricing' => false, 'stock' => false, 'cats' => false, 'media' => false];

        foreach ($fields as $f) {
            if (str_starts_with($f, 'price_net_') || str_starts_with($f, 'price_gross_')) {
                $flags['pricing'] = true;
            } elseif (str_starts_with($f, 'stock_') || str_starts_with($f, 'reserved_')) {
                $flags['stock'] = true;
            } elseif ($f === 'category_path' || $f === 'category_primary') {
                $flags['cats'] = true;
            } elseif ($f === 'image_url_main' || $f === 'image_urls_all') {
                $flags['media'] = true;
            }
        }

        if ($flags['pricing']) {
            $load[] = 'validPrices.priceGroup:id,name,code';
        }
        if ($flags['stock']) {
            $load[] = 'activeStock.warehouse:id,name,code';
        }
        if ($flags['cats']) {
            $load[] = 'categories';
        }
        if ($flags['media']) {
            $load[] = 'media';
        }

        return $load;
    }

    // === Helpers ===

    private function getSelectedFieldKeys(ExportProfile $profile): array
    {
        $config = $profile->field_config ?? [];
        if (empty($config)) {
            return [];
        }

        // Keyed array {'sku' => true, ...} vs flat array ['sku', ...]
        return array_is_list($config) ? array_values($config) : array_keys(array_filter($config));
    }

    private function resolveFieldLabel(string $fieldKey, array $allFields): string
    {
        foreach ($allFields as $group) {
            if (isset($group['fields'][$fieldKey]['label'])) {
                return $group['fields'][$fieldKey]['label'];
            }
        }
        return ucfirst(str_replace('_', ' ', $fieldKey));
    }

    private function warmCaches(array $fields): void
    {
        $needsPrice = $needsStock = false;
        foreach ($fields as $f) {
            if (str_starts_with($f, 'price_net_') || str_starts_with($f, 'price_gross_')) {
                $needsPrice = true;
            }
            if (str_starts_with($f, 'stock_') || str_starts_with($f, 'reserved_')) {
                $needsStock = true;
            }
        }

        if ($needsPrice && $this->priceGroupCache === null) {
            $this->priceGroupCache = PriceGroup::active()->get(['id', 'name', 'code'])->keyBy('id')->all();
        }
        if ($needsStock && $this->warehouseCache === null) {
            $this->warehouseCache = Warehouse::active()->get(['id', 'name', 'code'])->keyBy('id')->all();
        }
    }
}
