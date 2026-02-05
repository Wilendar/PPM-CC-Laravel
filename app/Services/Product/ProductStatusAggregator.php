<?php

declare(strict_types=1);

namespace App\Services\Product;

use App\DTOs\ProductStatusDTO;
use App\Models\Media;
use App\Models\Product;
use App\Models\PriceGroup;
use App\Models\SyncJob;
use App\Models\SystemSetting;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for aggregating product status information for Product List display.
 *
 * Aggregates:
 * - Global issues (zero price, low stock, missing images, not in PrestaShop)
 * - Per-shop data discrepancies
 * - Per-ERP data discrepancies
 * - Variant issues
 *
 * @package App\Services\Product
 * @since 2026-02-04
 * @see Plan_Projektu/synthetic-mixing-thunder.md
 */
class ProductStatusAggregator
{
    /**
     * Configuration key in system_settings
     */
    private const CONFIG_KEY = 'product_status_config';
    private const CONFIG_CATEGORY = 'product';

    /**
     * Default field groups for monitoring
     */
    private const DEFAULT_BASIC_FIELDS = ['name', 'manufacturer', 'tax_rate', 'is_active'];
    private const DEFAULT_DESC_FIELDS = ['short_description', 'long_description'];
    private const DEFAULT_PHYSICAL_FIELDS = ['weight', 'height', 'width', 'length'];

    /**
     * Default ignored fields
     */
    private const DEFAULT_IGNORED_BASIC = ['supplier_code', 'ean', 'sort_order'];
    private const DEFAULT_IGNORED_DESC = ['meta_title', 'meta_description'];

    /**
     * Product type slugs for conditional checks
     */
    private const VEHICLE_SLUGS = ['vehicle', 'pojazd'];
    private const SPARE_PART_SLUGS = ['spare_part', 'czesc-zamienna', 'część-zamienna'];

    /**
     * Cache settings
     */
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_PREFIX = 'product_status_';

    /**
     * Loaded configuration
     */
    private ?array $config = null;

    /**
     * Cached default warehouse ID
     */
    private ?int $defaultWarehouseId = null;

    /**
     * Cached active price group IDs
     */
    private ?array $activePriceGroupIds = null;

    /**
     * Aggregate statuses for a collection of products (batch processing)
     *
     * @param Collection<Product> $products
     * @return array<int, ProductStatusDTO> [product_id => ProductStatusDTO]
     */
    public function aggregateForProducts(Collection $products): array
    {
        $statuses = [];

        // Pre-load shared data
        $this->loadSharedData();

        // Pre-fetch active sync jobs for all products at once (optimization)
        $productIds = $products->pluck('id')->toArray();
        $productsWithActiveSyncJobs = $this->getProductIdsWithActiveSyncJobs($productIds);

        foreach ($products as $product) {
            $cacheKey = $this->getCacheKey($product);

            // Skip cache if product has active sync job (syncing status must be real-time)
            $hasActiveSyncJob = in_array($product->id, $productsWithActiveSyncJobs);

            if (!$hasActiveSyncJob && $this->isCacheEnabled() && Cache::has($cacheKey)) {
                $statuses[$product->id] = Cache::get($cacheKey);
            } else {
                $status = $this->aggregateForProduct($product);

                // Only cache if no active sync job (syncing products need fresh data)
                if (!$hasActiveSyncJob && $this->isCacheEnabled()) {
                    Cache::put($cacheKey, $status, self::CACHE_TTL);
                }

                $statuses[$product->id] = $status;
            }
        }

        return $statuses;
    }

    /**
     * Aggregate status for a single product
     */
    public function aggregateForProduct(Product $product): ProductStatusDTO
    {
        $status = new ProductStatusDTO($product->id);

        // Ensure shared data is loaded
        $this->loadSharedData();

        // Check if product is within import grace period
        // If so, skip validation and show "awaiting validation" status
        if ($this->isWithinGracePeriod($product)) {
            $status->isAwaitingValidation = true;
            $status->gracePeriodExpiresAt = $this->getGracePeriodExpiry($product);

            // Still collect connected integrations for display
            $this->collectConnectedIntegrations($product, $status);

            return $status;
        }

        // Check global issues
        $this->checkGlobalIssues($product, $status);

        // Check per-shop discrepancies
        $this->checkShopDiscrepancies($product, $status);

        // Check per-ERP discrepancies
        $this->checkErpDiscrepancies($product, $status);

        // Check variant issues
        $this->checkVariantIssues($product, $status);

        // Collect ALL connected integrations (for showing OK status too)
        $this->collectConnectedIntegrations($product, $status);

        return $status;
    }

    /**
     * Check global product issues
     */
    private function checkGlobalIssues(Product $product, ProductStatusDTO $status): void
    {
        // 1. Zero price in active price group
        if ($this->isMonitoringEnabled('zero_price')) {
            $hasZeroPrice = $this->checkZeroPrice($product);
            $status->setGlobalIssue(ProductStatusDTO::ISSUE_ZERO_PRICE, $hasZeroPrice);
        }

        // 2. Below minimum stock in default warehouse
        if ($this->isMonitoringEnabled('low_stock')) {
            $isBelowMin = $this->checkLowStock($product);
            $status->setGlobalIssue(ProductStatusDTO::ISSUE_LOW_STOCK, $isBelowMin);
        }

        // 3. No images in PPM
        if ($this->isMonitoringEnabled('images')) {
            $hasNoImages = $this->checkNoImages($product);
            $status->setGlobalIssue(ProductStatusDTO::ISSUE_NO_IMAGES, $hasNoImages);
        }

        // 4. Not in any PrestaShop shop
        $notInPrestaShop = $product->shopData->isEmpty();
        $status->setGlobalIssue(ProductStatusDTO::ISSUE_NOT_IN_PRESTASHOP, $notInPrestaShop);
    }

    /**
     * Check zero price in active price groups
     */
    private function checkZeroPrice(Product $product): bool
    {
        if (!$product->relationLoaded('prices')) {
            return false;
        }

        foreach ($product->prices as $price) {
            // Only check active price groups
            if ($price->relationLoaded('priceGroup') &&
                $price->priceGroup &&
                $price->priceGroup->is_active) {

                if ($price->price_net <= 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if product is below minimum stock in default warehouse
     */
    private function checkLowStock(Product $product): bool
    {
        if (!$product->relationLoaded('stock') || !$this->defaultWarehouseId) {
            return false;
        }

        $defaultStock = $product->stock->firstWhere('warehouse_id', $this->defaultWarehouseId);

        if (!$defaultStock) {
            return false;
        }

        $availableQty = ($defaultStock->quantity ?? 0) - ($defaultStock->reserved_quantity ?? 0);
        $minimumStock = $defaultStock->minimum_stock ?? 0;

        return $minimumStock > 0 && $availableQty < $minimumStock;
    }

    /**
     * Check if product has no active images
     */
    private function checkNoImages(Product $product): bool
    {
        if (!$product->relationLoaded('media')) {
            return true;
        }

        return $product->media->where('is_active', true)->isEmpty();
    }

    /**
     * Check per-shop data discrepancies
     */
    private function checkShopDiscrepancies(Product $product, ProductStatusDTO $status): void
    {
        if (!$product->relationLoaded('shopData')) {
            return;
        }

        foreach ($product->shopData as $shopData) {
            $shopId = $shopData->shop_id;

            // Check basic data discrepancies
            if ($this->isMonitoringEnabled('basic')) {
                $basicFields = $this->getMonitoredBasicFields();
                foreach ($basicFields as $field) {
                    if ($this->hasFieldDiscrepancy($product, $shopData, $field)) {
                        $status->addShopIssue($shopId, ProductStatusDTO::ISSUE_BASIC_DATA);
                        break; // One is enough to flag the group
                    }
                }
            }

            // Check description discrepancies
            if ($this->isMonitoringEnabled('descriptions')) {
                $descFields = $this->getMonitoredDescFields();
                foreach ($descFields as $field) {
                    if ($this->hasFieldDiscrepancy($product, $shopData, $field)) {
                        $status->addShopIssue($shopId, ProductStatusDTO::ISSUE_DESCRIPTIONS);
                        break;
                    }
                }
            }

            // Check physical properties discrepancies
            if ($this->isMonitoringEnabled('physical')) {
                foreach (self::DEFAULT_PHYSICAL_FIELDS as $field) {
                    if ($this->hasFieldDiscrepancy($product, $shopData, $field)) {
                        $status->addShopIssue($shopId, ProductStatusDTO::ISSUE_PHYSICAL);
                        break;
                    }
                }
            }

            // Check images mapping (images in PPM but not mapped to this shop)
            if ($this->isMonitoringEnabled('images')) {
                if ($this->hasUnmappedImages($product, $shopData)) {
                    $status->addShopIssue($shopId, ProductStatusDTO::ISSUE_IMAGES_MAPPING);
                }
            }

            // Conditional: Attributes (only for "Pojazd" product type)
            if ($this->isMonitoringEnabled('attributes') && $this->isVehicleType($product)) {
                // Check attribute mappings if available
                if ($this->hasAttributeDiscrepancy($product, $shopData)) {
                    $status->addShopIssue($shopId, ProductStatusDTO::ISSUE_ATTRIBUTES);
                }
            }

            // Conditional: Compatibility (only for "Część zamienna" product type)
            if ($this->isMonitoringEnabled('compatibility') && $this->isSparePartType($product)) {
                if ($this->hasCompatibilityDiscrepancy($product, $shopData)) {
                    $status->addShopIssue($shopId, ProductStatusDTO::ISSUE_COMPATIBILITY);
                }
            }
        }
    }

    /**
     * Check per-ERP data discrepancies
     */
    private function checkErpDiscrepancies(Product $product, ProductStatusDTO $status): void
    {
        if (!$product->relationLoaded('erpData')) {
            return;
        }

        foreach ($product->erpData as $erpData) {
            $erpId = $erpData->erp_connection_id;

            // Check basic data discrepancies
            if ($this->isMonitoringEnabled('basic')) {
                $basicFields = $this->getMonitoredBasicFields();
                foreach ($basicFields as $field) {
                    if ($this->hasFieldDiscrepancy($product, $erpData, $field)) {
                        $status->addErpIssue($erpId, ProductStatusDTO::ISSUE_BASIC_DATA);
                        break;
                    }
                }
            }

            // Check description discrepancies
            if ($this->isMonitoringEnabled('descriptions')) {
                $descFields = $this->getMonitoredDescFields();
                foreach ($descFields as $field) {
                    if ($this->hasFieldDiscrepancy($product, $erpData, $field)) {
                        $status->addErpIssue($erpId, ProductStatusDTO::ISSUE_DESCRIPTIONS);
                        break;
                    }
                }
            }

            // Check physical properties discrepancies
            if ($this->isMonitoringEnabled('physical')) {
                foreach (self::DEFAULT_PHYSICAL_FIELDS as $field) {
                    if ($this->hasFieldDiscrepancy($product, $erpData, $field)) {
                        $status->addErpIssue($erpId, ProductStatusDTO::ISSUE_PHYSICAL);
                        break;
                    }
                }
            }
        }
    }

    /**
     * Check variant issues
     */
    private function checkVariantIssues(Product $product, ProductStatusDTO $status): void
    {
        if (!$product->relationLoaded('variants') || $product->variants->isEmpty()) {
            return;
        }

        foreach ($product->variants as $variant) {
            // Check variant has images
            if ($this->isMonitoringEnabled('images')) {
                $hasNoImages = !$variant->relationLoaded('images') || $variant->images->isEmpty();
                if ($hasNoImages) {
                    $status->addVariantIssue($variant->id, ProductStatusDTO::VARIANT_NO_IMAGES);
                }
            }

            // Check variant zero price (VariantPrice uses 'price' column, not 'price_net')
            if ($this->isMonitoringEnabled('zero_price') && $variant->relationLoaded('prices')) {
                foreach ($variant->prices as $price) {
                    if ($this->activePriceGroupIds &&
                        in_array($price->price_group_id, $this->activePriceGroupIds) &&
                        $price->price <= 0) {
                        $status->addVariantIssue($variant->id, ProductStatusDTO::VARIANT_ZERO_PRICE);
                        break;
                    }
                }
            }

            // Check variant low stock (VariantStock uses 'reserved', not 'reserved_quantity')
            // Note: VariantStock doesn't have minimum_stock, so we check if quantity is 0
            if ($this->isMonitoringEnabled('low_stock') &&
                $variant->relationLoaded('stock') &&
                $this->defaultWarehouseId) {

                $defaultStock = $variant->stock->firstWhere('warehouse_id', $this->defaultWarehouseId);
                if ($defaultStock) {
                    $availableQty = ($defaultStock->quantity ?? 0) - ($defaultStock->reserved ?? 0);

                    // Flag as low stock if available quantity is 0 or negative
                    if ($availableQty <= 0) {
                        $status->addVariantIssue($variant->id, ProductStatusDTO::VARIANT_LOW_STOCK);
                    }
                }
            }
        }
    }

    /**
     * Collect ALL connected integrations (shops and ERPs)
     * This allows status column to show all integrations, not just those with issues
     * Also checks for active sync jobs per integration
     */
    private function collectConnectedIntegrations(Product $product, ProductStatusDTO $status): void
    {
        // Get active sync jobs for this product (pending or running)
        $activeSyncJobs = $this->getActiveSyncJobsForProduct($product->id);

        // Collect PrestaShop shops
        if ($product->relationLoaded('shopData')) {
            foreach ($product->shopData as $shopData) {
                $shop = $shopData->shop ?? null;
                if ($shop) {
                    // Check if there's an active sync job for this shop
                    $syncStatus = $activeSyncJobs['shops'][$shopData->shop_id] ?? null;

                    $status->addConnectedShop(
                        $shopData->shop_id,
                        $shop->name ?? "Sklep #{$shopData->shop_id}",
                        $shop->label_color ?? '06b6d4',
                        $shop->label_icon ?? 'shopping-cart',
                        $syncStatus
                    );
                }
            }
        }

        // Collect ERP connections
        if ($product->relationLoaded('erpData')) {
            foreach ($product->erpData as $erpData) {
                $erp = $erpData->erpConnection ?? null;
                if ($erp) {
                    // Check if there's an active sync job for this ERP
                    $syncStatus = $activeSyncJobs['erps'][$erpData->erp_connection_id] ?? null;

                    $status->addConnectedErp(
                        $erpData->erp_connection_id,
                        $erp->instance_name ?? "ERP #{$erpData->erp_connection_id}",
                        $erp->label_color ?? 'f97316',
                        $erp->label_icon ?? 'database',
                        $syncStatus
                    );
                }
            }
        }

        // Finalize hasIssues flags based on collected issues
        $status->finalizeConnectedIntegrations();
    }

    /**
     * Get product IDs that have active sync jobs (batch query for optimization)
     *
     * FIX 2026-02-05: Also checks early sync flags in cache, not just SyncJob records.
     * Early sync flags are set by ProductForm BEFORE dispatch() to show "syncing" immediately.
     *
     * @param array<int> $productIds
     * @return array<int> Product IDs with active sync jobs or early sync flags
     */
    private function getProductIdsWithActiveSyncJobs(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $result = [];

        // 1. Check for early sync flags in cache (set by ProductForm before SyncJob exists)
        foreach ($productIds as $productId) {
            $earlySyncFlags = Cache::get("product_sync_pending:{$productId}", []);
            if (!empty($earlySyncFlags)) {
                $result[] = $productId;
            }
        }

        // 2. Query actual SyncJob records (pending or running)
        $fromDb = SyncJob::whereIn('source_id', $productIds)
            ->where('source_type', SyncJob::TYPE_PPM)
            ->whereIn('status', [SyncJob::STATUS_PENDING, SyncJob::STATUS_RUNNING])
            ->distinct()
            ->pluck('source_id')
            ->toArray();

        // Merge and deduplicate
        return array_unique(array_merge($result, $fromDb));
    }

    /**
     * Get active sync jobs (pending/running) for a product
     * Returns array with 'shops' and 'erps' keys containing sync status per integration
     *
     * Also checks for "early sync flags" set by ProductForm before actual SyncJob is created.
     * This handles the timing gap between dispatch() and handle() in queue.
     *
     * @param int $productId
     * @return array{shops: array<int, string>, erps: array<int, string>}
     */
    private function getActiveSyncJobsForProduct(int $productId): array
    {
        $result = ['shops' => [], 'erps' => []];

        // 1. Check for "early sync flags" (set by ProductForm before SyncJob exists)
        // These flags bridge the gap between dispatch() and when SyncJob is created in handle()
        $earlySyncFlags = Cache::get("product_sync_pending:{$productId}", []);
        foreach ($earlySyncFlags as $flag) {
            if ($flag['type'] === 'shop') {
                $result['shops'][(int) $flag['target_id']] = 'pending';
            } elseif ($flag['type'] === 'erp') {
                $result['erps'][(int) $flag['target_id']] = 'pending';
            }
        }

        // 2. Query actual SyncJob records (pending or running)
        $activeJobs = SyncJob::where('source_id', $productId)
            ->where('source_type', SyncJob::TYPE_PPM)
            ->whereIn('status', [SyncJob::STATUS_PENDING, SyncJob::STATUS_RUNNING])
            ->select(['target_type', 'target_id', 'status'])
            ->get();

        foreach ($activeJobs as $job) {
            $syncStatus = $job->status; // 'pending' or 'running'

            if ($job->target_type === SyncJob::TYPE_PRESTASHOP) {
                // PrestaShop shop sync - override early flag with actual status
                $result['shops'][(int) $job->target_id] = $syncStatus;
            } else {
                // ERP sync (subiekt_gt, baselinker, dynamics)
                $result['erps'][(int) $job->target_id] = $syncStatus;
            }
        }

        return $result;
    }

    /**
     * Check if field has discrepancy between product and shop/ERP data
     */
    private function hasFieldDiscrepancy(Product $product, $integrationData, string $field): bool
    {
        $productValue = $this->normalizeValue($product->{$field} ?? null, $field);
        $integrationValue = $this->normalizeValue($integrationData->{$field} ?? null, $field);

        // NULL in integration = inherited, not a discrepancy
        if ($integrationValue === null) {
            return false;
        }

        return $productValue !== $integrationValue;
    }

    /**
     * Normalize value for comparison
     */
    private function normalizeValue(mixed $value, string $field): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Numeric fields
        if (in_array($field, ['weight', 'height', 'width', 'length', 'tax_rate'])) {
            return round((float) $value, 2);
        }

        // Boolean fields
        if (in_array($field, ['is_active'])) {
            return (bool) $value;
        }

        // Text fields - trim
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    /**
     * Check if product has images not mapped to shop
     *
     * Note: prestashop_mapping uses format "store_{shopId}" as key,
     * not raw shop_id. See Media::getPrestaShopMapping() / setPrestaShopMapping()
     */
    private function hasUnmappedImages(Product $product, $shopData): bool
    {
        if (!$product->relationLoaded('media')) {
            return false;
        }

        // Only check gallery images (context = product_gallery), not UVE/visual description images
        $galleryImages = $product->media
            ->where('is_active', true)
            ->filter(fn($m) => $m->context === Media::CONTEXT_PRODUCT_GALLERY || $m->context === null);

        if ($galleryImages->isEmpty()) {
            return false;
        }

        // Check if any gallery image is NOT mapped to this shop
        $shopId = $shopData->shop_id;
        $mappingKey = "store_{$shopId}";

        foreach ($galleryImages as $media) {
            $mapping = $media->prestashop_mapping ?? [];

            // Check if mapping exists for this shop
            if (!isset($mapping[$mappingKey]) || empty($mapping[$mappingKey])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check attribute discrepancy (simplified - can be expanded)
     */
    private function hasAttributeDiscrepancy(Product $product, $shopData): bool
    {
        // Check if attribute_mappings exists and is different
        $shopMappings = $shopData->attribute_mappings ?? [];
        return !empty($shopMappings) && isset($shopMappings['pending']);
    }

    /**
     * Check compatibility discrepancy (simplified - can be expanded)
     */
    private function hasCompatibilityDiscrepancy(Product $product, $shopData): bool
    {
        // For now, just check if product has compatibility data but not mapped
        if (!$product->relationLoaded('compatibilities')) {
            return false;
        }
        return $product->compatibilities->isNotEmpty() &&
               empty($shopData->compatibility_mappings ?? []);
    }

    /**
     * Check if product type is Vehicle
     */
    private function isVehicleType(Product $product): bool
    {
        if (!$product->relationLoaded('productType') || !$product->productType) {
            return false;
        }
        return in_array($product->productType->slug, self::VEHICLE_SLUGS);
    }

    /**
     * Check if product type is Spare Part
     */
    private function isSparePartType(Product $product): bool
    {
        if (!$product->relationLoaded('productType') || !$product->productType) {
            return false;
        }
        return in_array($product->productType->slug, self::SPARE_PART_SLUGS);
    }

    /**
     * Load shared data (default warehouse, active price groups)
     */
    private function loadSharedData(): void
    {
        // Load default warehouse
        if ($this->defaultWarehouseId === null) {
            $defaultWarehouse = Warehouse::where('is_default', true)->where('is_active', true)->first();
            $this->defaultWarehouseId = $defaultWarehouse?->id ?? 0;
        }

        // Load active price group IDs
        if ($this->activePriceGroupIds === null) {
            $this->activePriceGroupIds = PriceGroup::where('is_active', true)
                ->pluck('id')
                ->toArray();
        }
    }

    /**
     * Get configuration from SystemSetting
     */
    private function getConfig(): array
    {
        if ($this->config === null) {
            $this->config = SystemSetting::get(self::CONFIG_KEY, $this->getDefaultConfig());
        }
        return $this->config;
    }

    /**
     * Get default configuration
     */
    private function getDefaultConfig(): array
    {
        return [
            'monitoring' => [
                'basic' => true,
                'descriptions' => true,
                'physical' => true,
                'attributes' => true,
                'compatibility' => true,
                'images' => true,
                'zero_price' => true,
                'low_stock' => true,
            ],
            'ignored_fields' => [
                'basic' => self::DEFAULT_IGNORED_BASIC,
                'descriptions' => self::DEFAULT_IGNORED_DESC,
            ],
            'cache_enabled' => true,
            'cache_ttl' => self::CACHE_TTL,
        ];
    }

    /**
     * Check if product is within import grace period
     *
     * Products created recently (within grace period) are not validated
     * to allow time for complete import (images, prices, etc.)
     */
    private function isWithinGracePeriod(Product $product): bool
    {
        $gracePeriodSeconds = $this->getImportGracePeriodSeconds();

        if ($gracePeriodSeconds <= 0) {
            return false;
        }

        $createdAt = $product->created_at;
        if (!$createdAt) {
            return false;
        }

        $gracePeriodExpiry = $createdAt->copy()->addSeconds($gracePeriodSeconds);

        return now()->lessThan($gracePeriodExpiry);
    }

    /**
     * Get grace period expiry timestamp for a product
     */
    private function getGracePeriodExpiry(Product $product): ?\DateTimeInterface
    {
        $gracePeriodSeconds = $this->getImportGracePeriodSeconds();

        if ($gracePeriodSeconds <= 0 || !$product->created_at) {
            return null;
        }

        return $product->created_at->copy()->addSeconds($gracePeriodSeconds);
    }

    /**
     * Get import grace period from config (in seconds)
     */
    private function getImportGracePeriodSeconds(): int
    {
        $config = $this->getConfig();
        return (int) ($config['import_grace_period_seconds'] ?? 60);
    }

    /**
     * Check if specific monitoring is enabled
     */
    private function isMonitoringEnabled(string $type): bool
    {
        $config = $this->getConfig();
        return $config['monitoring'][$type] ?? true;
    }

    /**
     * Check if cache is enabled
     */
    private function isCacheEnabled(): bool
    {
        $config = $this->getConfig();
        return $config['cache_enabled'] ?? true;
    }

    /**
     * Get monitored basic fields (excluding ignored)
     */
    private function getMonitoredBasicFields(): array
    {
        $config = $this->getConfig();
        $ignored = $config['ignored_fields']['basic'] ?? self::DEFAULT_IGNORED_BASIC;
        return array_diff(self::DEFAULT_BASIC_FIELDS, $ignored);
    }

    /**
     * Get monitored description fields (excluding ignored)
     */
    private function getMonitoredDescFields(): array
    {
        $config = $this->getConfig();
        $ignored = $config['ignored_fields']['descriptions'] ?? self::DEFAULT_IGNORED_DESC;
        return array_diff(self::DEFAULT_DESC_FIELDS, $ignored);
    }

    /**
     * Get cache key for product
     */
    private function getCacheKey(Product $product): string
    {
        return self::CACHE_PREFIX . $product->id . '_' . $product->updated_at->timestamp;
    }

    /**
     * Invalidate cache for product
     *
     * FIX 2026-02-05: Cache::forget() doesn't support wildcards!
     * Must fetch actual product timestamp to build correct cache key.
     */
    public function invalidateCache(int $productId): void
    {
        // Get current product to build exact cache key (includes timestamp)
        $product = \App\Models\Product::select('id', 'updated_at')->find($productId);

        if ($product && $product->updated_at) {
            $cacheKey = self::CACHE_PREFIX . $product->id . '_' . $product->updated_at->timestamp;
            Cache::forget($cacheKey);

            Log::debug('[ProductStatusAggregator] Cache invalidated', [
                'product_id' => $productId,
                'cache_key' => $cacheKey,
            ]);
        }
    }

    /**
     * Clear all product status cache
     */
    public function clearAllCache(): void
    {
        // Clear pattern (requires Redis or similar)
        // For database cache, entries will expire naturally
        Cache::flush();
    }

    /**
     * Invalidate cache for products connected to a specific shop
     *
     * Called when shop label_color or label_icon changes
     *
     * @param int $shopId PrestaShop shop ID
     * @return int Number of products invalidated
     */
    public function invalidateCacheForShop(int $shopId): int
    {
        $productIds = \App\Models\ProductShopData::where('shop_id', $shopId)
            ->pluck('product_id')
            ->unique()
            ->toArray();

        foreach ($productIds as $productId) {
            $this->invalidateCacheByProductId($productId);
        }

        return count($productIds);
    }

    /**
     * Invalidate cache for products connected to a specific ERP
     *
     * Called when ERP label_color or label_icon changes
     *
     * @param int $erpConnectionId ERP connection ID
     * @return int Number of products invalidated
     */
    public function invalidateCacheForErp(int $erpConnectionId): int
    {
        $productIds = \App\Models\ProductErpData::where('erp_connection_id', $erpConnectionId)
            ->pluck('product_id')
            ->unique()
            ->toArray();

        foreach ($productIds as $productId) {
            $this->invalidateCacheByProductId($productId);
        }

        return count($productIds);
    }

    /**
     * Invalidate cache for a product by ID (using pattern matching)
     *
     * Since cache key includes timestamp, we use Cache::getStore() if available
     * or rely on natural expiration
     */
    private function invalidateCacheByProductId(int $productId): void
    {
        // Get current product to build exact cache key
        $product = \App\Models\Product::select('id', 'updated_at')->find($productId);

        if ($product) {
            $cacheKey = self::CACHE_PREFIX . $product->id . '_' . $product->updated_at->timestamp;
            Cache::forget($cacheKey);
        }
    }

    /**
     * Update configuration
     */
    public function updateConfig(array $config): void
    {
        SystemSetting::set(
            self::CONFIG_KEY,
            $config,
            self::CONFIG_CATEGORY,
            'json',
            'Product status monitoring configuration'
        );

        $this->config = null; // Reset cached config
        $this->clearAllCache();
    }

    /**
     * Get current configuration for admin panel
     */
    public function getCurrentConfig(): array
    {
        return $this->getConfig();
    }
}
