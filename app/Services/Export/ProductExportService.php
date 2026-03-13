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

        // Backward compat: stary manufacturer (string) -> lookup
        if (!empty($filterConfig['manufacturer']) && empty($filterConfig['manufacturer_ids'])) {
            $val = $filterConfig['manufacturer'];
            is_array($val) ? $query->whereIn('manufacturer', $val) : $query->where('manufacturer', $val);
        }

        // Nowe: manufacturer_ids (multiselect z BusinessPartner)
        if (!empty($filterConfig['manufacturer_ids'])) {
            $query->whereIn('manufacturer_id', (array) $filterConfig['manufacturer_ids']);
        }

        // supplier_ids
        if (!empty($filterConfig['supplier_ids'])) {
            $query->whereIn('supplier_id', (array) $filterConfig['supplier_ids']);
        }

        // product_type_id
        if (!empty($filterConfig['product_type_id'])) {
            $query->where('product_type_id', $filterConfig['product_type_id']);
        }

        // has_stock (backward compat - preferowany jest stock_status)
        if (isset($filterConfig['has_stock']) && $filterConfig['has_stock'] !== '' && empty($filterConfig['stock_status'])) {
            $has = filter_var($filterConfig['has_stock'], FILTER_VALIDATE_BOOLEAN);
            $stockCb = fn (Builder $q) => $q->where('available_quantity', '>', 0);
            $has ? $query->whereHas('activeStock', $stockCb) : $query->whereDoesntHave('activeStock', $stockCb);
        }

        // stock_status (in_stock/low_stock/out_of_stock)
        if (!empty($filterConfig['stock_status'])) {
            match ($filterConfig['stock_status']) {
                'in_stock' => $query->whereHas('activeStock', fn (Builder $q) => $q->where('available_quantity', '>', 0)),
                'low_stock' => $query->whereHas('activeStock', fn (Builder $q) => $q->where('available_quantity', '>', 0)->where('available_quantity', '<=', 5)),
                'out_of_stock' => $query->where(function (Builder $q) {
                    $q->whereDoesntHave('activeStock')
                      ->orWhereHas('activeStock', fn (Builder $sq) => $sq->where('available_quantity', '<=', 0));
                }),
                default => null,
            };
        }

        // warehouse_ids
        if (!empty($filterConfig['warehouse_ids'])) {
            $query->whereHas('activeStock', fn (Builder $q) => $q->whereIn('warehouse_id', (array) $filterConfig['warehouse_ids'])->where('available_quantity', '>', 0));
        }

        // price_min/price_max + price_group_id
        if (!empty($filterConfig['price_min']) || !empty($filterConfig['price_max'])) {
            $query->whereHas('validPrices', function (Builder $q) use ($filterConfig) {
                if (!empty($filterConfig['price_group_id'])) {
                    $q->where('price_group_id', $filterConfig['price_group_id']);
                }
                if (!empty($filterConfig['price_min'])) {
                    $q->where('price_net', '>=', (float) $filterConfig['price_min']);
                }
                if (!empty($filterConfig['price_max'])) {
                    $q->where('price_net', '<=', (float) $filterConfig['price_max']);
                }
            });
        }

        if (!empty($filterConfig['shop_ids'])) {
            $shopIds = (array) $filterConfig['shop_ids'];
            $query->whereHas('shopData', fn (Builder $q) => $q->whereIn('shop_id', $shopIds)->where('is_published', true));
        }

        // erp_connection_ids
        if (!empty($filterConfig['erp_connection_ids'])) {
            $query->whereHas('erpData', fn (Builder $q) => $q->whereIn('erp_connection_id', (array) $filterConfig['erp_connection_ids']));
        }

        // date_from/date_to + date_type
        $dateCol = ($filterConfig['date_type'] ?? 'created_at') === 'updated_at' ? 'updated_at' : 'created_at';
        if (!empty($filterConfig['date_from'])) {
            $query->where($dateCol, '>=', $filterConfig['date_from']);
        }
        if (!empty($filterConfig['date_to'])) {
            $query->where($dateCol, '<=', $filterConfig['date_to'] . ' 23:59:59');
        }

        // media_filter
        if (!empty($filterConfig['media_filter'])) {
            match ($filterConfig['media_filter']) {
                'with_images' => $query->whereHas('media'),
                'without_images' => $query->whereDoesntHave('media'),
                default => null,
            };
        }

        // has_compatibility
        if (!empty($filterConfig['has_compatibility'])) {
            match ($filterConfig['has_compatibility']) {
                'with' => $query->whereHas('vehicleCompatibility'),
                'without' => $query->whereDoesntHave('vehicleCompatibility'),
                default => null,
            };
        }

        // excluded_product_ids - remove individually excluded products
        if (!empty($filterConfig['excluded_product_ids'])) {
            $query->whereNotIn('products.id', (array) $filterConfig['excluded_product_ids']);
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

        // Dynamic product status name (from ProductStatus relation, fallback to is_active)
        if ($fieldKey === 'status_name') {
            return $product->productStatus?->name
                ?? ($product->is_active ? 'Aktywny' : 'Nieaktywny');
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

        // Compatibility fields
        if ($fieldKey === 'compatible_vehicles') {
            $compat = $product->relationLoaded('vehicleCompatibility') ? $product->vehicleCompatibility : $product->vehicleCompatibility()->get();
            return $compat->isEmpty() ? null : $compat->map(fn ($c) => ($c->vehicleModel->name ?? 'N/A'))->implode(' | ');
        }
        if ($fieldKey === 'compatible_vehicles_count') {
            return $product->relationLoaded('vehicleCompatibility') ? $product->vehicleCompatibility->count() : $product->vehicleCompatibility()->count();
        }
        if ($fieldKey === 'compatibility_types') {
            $compat = $product->relationLoaded('vehicleCompatibility') ? $product->vehicleCompatibility : $product->vehicleCompatibility()->get();
            return $compat->isEmpty() ? null : $compat->pluck('compatibility_type')->filter()->unique()->implode(', ');
        }
        if ($fieldKey === 'compatibility_full') {
            return !empty($product->getCompatibilityExportFormat()) ? json_encode($product->getCompatibilityExportFormat(), JSON_UNESCAPED_UNICODE) : null;
        }

        // Location fields (warehouse_location_{code})
        if (str_starts_with($fieldKey, 'warehouse_location_')) {
            return $this->resolveLocationField($product, $fieldKey);
        }

        // Partner fields
        if ($fieldKey === 'manufacturer_name') {
            $rel = $product->relationLoaded('manufacturerRelation') ? $product->manufacturerRelation : $product->manufacturerRelation;
            return $rel?->name;
        }
        if ($fieldKey === 'supplier_name') {
            $rel = $product->relationLoaded('supplierRelation') ? $product->supplierRelation : $product->supplierRelation;
            return $rel?->name;
        }
        if ($fieldKey === 'importer_name') {
            $rel = $product->relationLoaded('importerRelation') ? $product->importerRelation : $product->importerRelation;
            return $rel?->name;
        }
        if ($fieldKey === 'product_type_name') {
            $rel = $product->relationLoaded('productType') ? $product->productType : $product->productType;
            return $rel?->name;
        }

        // Feature fields
        if ($fieldKey === 'features_list') {
            $features = $product->relationLoaded('features') ? $product->features : $product->features()->get();
            return $features->isEmpty() ? null : $features->map(fn ($f) => ($f->featureType->name ?? '?') . ': ' . ($f->featureValue->value ?? $f->custom_value ?? ''))->implode(' | ');
        }
        if ($fieldKey === 'feature_groups') {
            $features = $product->relationLoaded('features') ? $product->features : $product->features()->get();
            return $features->isEmpty() ? null : $features->map(fn ($f) => $f->featureType->name ?? null)->filter()->unique()->implode(', ');
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

    private function resolveLocationField(Product $product, string $fieldKey): ?string
    {
        $whCode = substr($fieldKey, 19); // strlen('warehouse_location_') = 19

        $stocks = $product->relationLoaded('activeStock')
            ? $product->activeStock
            : $product->activeStock()->with('warehouse')->get();

        foreach ($stocks as $stock) {
            $code = $stock->relationLoaded('warehouse') ? ($stock->warehouse->code ?? null) : null;
            if ($code === null && $this->warehouseCache !== null) {
                $code = ($this->warehouseCache[$stock->warehouse_id] ?? null)?->code;
            }
            if ($code === $whCode) {
                return $stock->warehouse_location ?: $stock->bin_location ?: $stock->location;
            }
        }

        return null;
    }

    // === Eager Loading ===

    private function resolveEagerLoading(array $fields): array
    {
        $load = [];
        $flags = [
            'pricing' => false, 'stock' => false, 'cats' => false, 'media' => false,
            'compat' => false, 'locations' => false, 'partners' => false, 'features' => false,
            'status' => false,
        ];

        foreach ($fields as $f) {
            if (str_starts_with($f, 'price_net_') || str_starts_with($f, 'price_gross_')) {
                $flags['pricing'] = true;
            } elseif (str_starts_with($f, 'stock_') || str_starts_with($f, 'reserved_')) {
                $flags['stock'] = true;
            } elseif ($f === 'category_path' || $f === 'category_primary') {
                $flags['cats'] = true;
            } elseif ($f === 'image_url_main' || $f === 'image_urls_all') {
                $flags['media'] = true;
            } elseif (str_starts_with($f, 'compatible_') || str_starts_with($f, 'compatibility_')) {
                $flags['compat'] = true;
            } elseif (str_starts_with($f, 'warehouse_location_')) {
                $flags['locations'] = true;
            } elseif (in_array($f, ['manufacturer_name', 'supplier_name', 'importer_name', 'product_type_name'])) {
                $flags['partners'] = true;
            } elseif ($f === 'features_list' || $f === 'feature_groups') {
                $flags['features'] = true;
            } elseif ($f === 'status_name') {
                $flags['status'] = true;
            }
        }

        if ($flags['pricing']) {
            $load[] = 'validPrices.priceGroup:id,name,code';
        }
        if ($flags['stock'] || $flags['locations']) {
            $load[] = 'activeStock.warehouse:id,name,code';
        }
        if ($flags['cats']) {
            $load[] = 'categories';
        }
        if ($flags['media']) {
            $load[] = 'media';
        }
        if ($flags['compat']) {
            $load[] = 'vehicleCompatibility.vehicleModel';
        }
        if ($flags['partners']) {
            $load[] = 'manufacturerRelation:id,name';
            $load[] = 'supplierRelation:id,name';
            $load[] = 'importerRelation:id,name';
            $load[] = 'productType:id,name';
        }
        if ($flags['features']) {
            $load[] = 'features.featureType';
            $load[] = 'features.featureValue';
        }
        if ($flags['status']) {
            $load[] = 'productStatus:id,name,slug,color';
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
            if (str_starts_with($f, 'stock_') || str_starts_with($f, 'reserved_') || str_starts_with($f, 'warehouse_location_')) {
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
