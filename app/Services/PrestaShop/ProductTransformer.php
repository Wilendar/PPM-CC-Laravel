<?php

namespace App\Services\PrestaShop;

use App\Models\Category;
use App\Models\BusinessPartner;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\BasePrestaShopClient;
use App\Services\PrestaShop\CategoryMapper;
use App\Services\PrestaShop\PriceGroupMapper;
use App\Services\PrestaShop\WarehouseMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Product Transformer for PrestaShop API
 *
 * ETAP_07 FAZA 1D - Data Layer
 *
 * Transforms PPM Product model to PrestaShop API format
 *
 * Features:
 * - Version-specific formatting (PrestaShop 8.x vs 9.x)
 * - Shop-specific data inheritance (ProductShopData override)
 * - Multilingual field handling
 * - Category mapping integration
 * - Price group calculation
 * - Stock aggregation per shop
 * - Validation before transformation
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since ETAP_07 FAZA 1D
 */
class ProductTransformer
{
    /**
     * Constructor with dependency injection
     *
     * @param CategoryMapper $categoryMapper
     * @param PriceGroupMapper $priceGroupMapper
     * @param WarehouseMapper $warehouseMapper
     */
    public function __construct(
        private readonly CategoryMapper $categoryMapper,
        private readonly PriceGroupMapper $priceGroupMapper,
        private readonly WarehouseMapper $warehouseMapper
    ) {}

    /**
     * Transform Product model to PrestaShop API format
     *
     * @param Product $product PPM Product instance
     * @param BasePrestaShopClient $client PrestaShop API client
     * @return array PrestaShop product data structure
     * @throws InvalidArgumentException On validation failure
     */
    public function transformForPrestaShop(Product $product, BasePrestaShopClient $client): array
    {
        // Validate product before transformation
        $this->validateProduct($product);

        $shop = $client->getShop();

        // Get shop-specific data or fallback to product defaults
        $shopData = $product->dataForShop($shop->id)->first();

        // Get default language ID (PrestaShop default: 1 = English/Polish)
        $defaultLangId = 1;

        // FIX 2025-11-19 BUG #3: Get default category ID from primary category (not first)
        $categoryAssociations = $this->buildCategoryAssociations($product, $shop);
        $defaultCategoryId = $this->getDefaultCategoryId($product, $shop, $categoryAssociations);

        // FAZA 5.2 Integration (2025-11-14): Calculate effective tax rate BEFORE building array
        // Use shop-specific tax_rate_override if set, otherwise fall back to product default
        $effectiveTaxRate = $shopData?->getEffectiveTaxRate() ?? $product->tax_rate;

        Log::debug('[FAZA 5.2 FIX] ProductTransformer tax rate mapping', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'product_default_tax_rate' => $product->tax_rate,
            'shop_override' => $shopData?->tax_rate_override ?? 'NULL',
            'effective_tax_rate' => $effectiveTaxRate,
            'id_tax_rules_group' => $this->mapTaxRate($effectiveTaxRate, $shop),
        ]);

        // Single source of truth: ProductShopData
        // UVE writes rendered HTML to ProductShopData automatically (bidirectional sync)
        // Textarea edits go to ProductShopData directly
        // No need for Visual Description bypass - all descriptions come from ProductShopData
        $effectiveShortDesc = $this->getEffectiveValue($shopData, $product, 'short_description') ?? '';
        $effectiveLongDesc = $this->getEffectiveValue($shopData, $product, 'long_description') ?? '';

        Log::debug('[DESC TRANSFORM] Description source (single source of truth)', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'short_len' => strlen($effectiveShortDesc),
            'long_len' => strlen($effectiveLongDesc),
        ]);

        // Build PrestaShop product structure
        $prestashopProduct = [
            'product' => [
                // Basic identification
                'reference' => $this->getEffectiveValue($shopData, $product, 'sku'),
                'ean13' => $product->ean ?? '',

                // Multilingual fields
                'name' => $this->buildMultilangField(
                    $this->getEffectiveValue($shopData, $product, 'name'),
                    $defaultLangId
                ),
                // Single source of truth: ProductShopData (UVE + textarea both write here)
                'description_short' => $this->buildMultilangField($effectiveShortDesc, $defaultLangId),
                'description' => $this->buildMultilangField($effectiveLongDesc, $defaultLangId),

                // Pricing (net price, PrestaShop calculates gross)
                'price' => $this->calculatePrice($product, $shop),

                // Physical properties
                'weight' => (float) ($this->getEffectiveValue($shopData, $product, 'weight') ?? 0),
                'width' => (float) ($this->getEffectiveValue($shopData, $product, 'width') ?? 0),
                'height' => (float) ($this->getEffectiveValue($shopData, $product, 'height') ?? 0),
                'depth' => (float) ($this->getEffectiveValue($shopData, $product, 'length') ?? 0),

                // Status and visibility
                'active' => $this->getEffectiveValue($shopData, $product, 'is_active') ? 1 : 0,
                'available_for_order' => 1,
                'show_price' => 1,
                'visibility' => 'both', // both|catalog|search|none

                // CRITICAL REQUIRED FIELDS (2025-11-14): Fix for products disappearing from admin panel
                // Reference: _DOCS/PRESTASHOP_REQUIRED_FIELDS.md
                'id_category_default' => $defaultCategoryId, // MUST have default category
                'id_shop_default' => $shop->prestashop_shop_id ?? 1, // MUST for multistore
                'minimal_quantity' => 1, // MUST be 1 (not 0)
                'redirect_type' => '301-category', // MUST be set (not empty string)
                'state' => 1, // MUST be 1 (not draft)
                'additional_delivery_times' => 1, // MUST be 1

                // Categories (mapped IDs)
                'associations' => [
                    'categories' => $categoryAssociations,
                ],

                // Stock quantity - REMOVED (2025-11-14): quantity is READONLY field
                // PrestaShop manages stock via separate resource: /stock_availables
                // 'quantity' => $this->warehouseMapper->calculateStockForShop($product, $shop), // ❌ READONLY
                // TODO: Implement stock sync via PrestaShopStockExporter service

                // Product type: 'standard', 'combinations', 'pack', 'virtual'
                // BUG#12 FIX: Variant masters MUST be 'combinations' for PS admin to show variants
                'product_type' => $product->is_variant_master ? 'combinations' : 'standard',

                // Tax (PrestaShop tax_rules_group_id) - FAZA 5.2 Integration (2025-11-14)
                'id_tax_rules_group' => $this->mapTaxRate($effectiveTaxRate, $shop),

                // Manufacturer (FIX 2025-12-15: Map manufacturer_id to PrestaShop id_manufacturer)
                // 'manufacturer_name' => $product->manufacturer ?? '', // ❌ READONLY - causes API error
                'id_manufacturer' => $this->getManufacturerPsId($product, $shop),

                // Supplier / Importer (ETAP_08: Map importer_id to PrestaShop id_supplier)
                // PPM Importer (BusinessPartner type='importer') → PS Supplier
                'id_supplier' => $this->getImporterPsId($product, $shop),

                // SEO fields
                'meta_title' => $this->buildMultilangField(
                    $this->getEffectiveValue($shopData, $product, 'meta_title') ?? $product->name,
                    $defaultLangId
                ),
                'meta_description' => $this->buildMultilangField(
                    $this->getEffectiveValue($shopData, $product, 'meta_description') ?? '',
                    $defaultLangId
                ),
                'link_rewrite' => $this->buildMultilangField(
                    $this->getEffectiveValue($shopData, $product, 'slug') ?? \Illuminate\Support\Str::slug($product->name),
                    $defaultLangId
                ),
            ]
        ];

        // Version-specific adjustments
        if ($client->getVersion() === '9') {
            $prestashopProduct = $this->applyVersion9Adjustments($prestashopProduct);
        }

        Log::info('Product transformed for PrestaShop', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'prestashop_version' => $client->getVersion(),
            'has_shop_data' => $shopData !== null,
        ]);

        return $prestashopProduct;
    }

    /**
     * Get effective value with shop-specific override support
     *
     * @param mixed $shopData ProductShopData instance or null
     * @param Product $product Product instance
     * @param string $field Field name
     * @return mixed Effective value
     */
    private function getEffectiveValue($shopData, Product $product, string $field): mixed
    {
        // If shop data exists and field is set, use shop-specific value
        if ($shopData && isset($shopData->$field) && $shopData->$field !== null) {
            return $shopData->$field;
        }

        // Fallback to product default value
        return $product->$field;
    }

    /**
     * Get Visual Description HTML for PrestaShop sync
     *
     * ETAP_07f Faza 8.2: Visual Description Editor integration
     *
     * Checks if product has a visual description for the shop:
     * 1. Looks for ProductDescription record (product_id + shop_id)
     * 2. Checks if sync_to_prestashop is enabled
     * 3. Renders and caches HTML if needed
     * 4. Returns appropriate field based on target_field setting
     *
     * @param Product $product Product instance
     * @param PrestaShopShop $shop Shop instance
     * @param string $targetField Which field to get ('description' or 'description_short')
     * @return string|null Visual description HTML or null if not available
     */
    private function getVisualDescription(Product $product, PrestaShopShop $shop, string $targetField): ?string
    {
        try {
            // Find visual description for this product-shop pair
            $visualDescription = ProductDescription::where('product_id', $product->id)
                ->where('shop_id', $shop->id)
                ->where('sync_to_prestashop', true)
                ->first();

            // No visual description or sync disabled
            if (!$visualDescription) {
                return null;
            }

            // Check if this description targets the requested field
            $allowedTargets = match ($targetField) {
                'description' => ['description', 'both'],
                'description_short' => ['description_short', 'both'],
                default => [],
            };

            if (!in_array($visualDescription->target_field, $allowedTargets)) {
                return null;
            }

            // Get HTML for PrestaShop (renders if needed)
            $htmlData = $visualDescription->getHtmlForPrestaShop();

            Log::debug('[VISUAL DESC TRANSFORM] Retrieved visual description', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'target_field' => $targetField,
                'visual_target' => $visualDescription->target_field,
                'has_html' => !empty($htmlData[$targetField]),
            ]);

            return $htmlData[$targetField] ?? null;

        } catch (\Exception $e) {
            Log::warning('[VISUAL DESC TRANSFORM] Failed to get visual description', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build multilingual field structure for PrestaShop
     *
     * PrestaShop format: [['id' => 1, 'value' => 'Text']]
     *
     * @param string $value Field value
     * @param int $languageId Language ID
     * @return array Multilingual structure
     */
    private function buildMultilangField(string $value, int $languageId = 1): array
    {
        return [
            [
                'id' => $languageId,
                'value' => $value,
            ]
        ];
    }

    /**
     * Calculate price for shop (uses default price group)
     *
     * @param Product $product Product instance
     * @param PrestaShopShop $shop Shop instance
     * @return float Net price
     */
    private function calculatePrice(Product $product, PrestaShopShop $shop): float
    {
        // Get default price group for this shop
        $defaultPriceGroup = $this->priceGroupMapper->getDefaultPriceGroup($shop);

        // Get price for default group
        $price = $product->getPriceForGroup($defaultPriceGroup->id);

        if (!$price) {
            Log::warning('No price found for default group', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'price_group_id' => $defaultPriceGroup->id,
            ]);
            return 0.0;
        }

        // Return net price (PrestaShop calculates gross based on tax rules)
        return (float) $price->price_net;
    }

    /**
     * Build category associations for PrestaShop
     *
     * FIX 2025-11-18 (#12): Updated to support Option A category_mappings structure
     *
     * Option A structure:
     * {
     *   "ui": {"selected": [100, 103, 42], "primary": 100},
     *   "mappings": {"100": 9, "103": 15, "42": 800},
     *   "metadata": {"last_updated": "...", "source": "..."}
     * }
     *
     * Strategy:
     * 1. Check ProductShopData.category_mappings (shop-specific overrides)
     * 2. Extract PrestaShop IDs from mappings key (Option A structure)
     * 3. Fallback to CategoryMapper if mappings empty
     * 4. Fallback to product global categories if no shop-specific mappings
     * 5. Always ensure at least one category (default: Home = 2)
     *
     * Backward Compatibility:
     * - ProductShopDataCast auto-converts legacy formats to Option A
     * - Old code using category_mappings will continue to work
     *
     * Format: [['id' => 2], ['id' => 15], ...]
     *
     * @param Product $product Product instance
     * @param PrestaShopShop $shop Shop instance
     * @return array Category associations in PrestaShop format
     */
    private function buildCategoryAssociations(Product $product, PrestaShopShop $shop): array
    {
        // FIX 2025-11-20: PRIORITY 1 = category_mappings (Option A) - FRESH USER DATA
        $shopData = $product->dataForShop($shop->id)->first();

        if ($shopData && !empty($shopData->category_mappings)) {
            $categoryMappings = $shopData->category_mappings;

            // Extract category IDs from Option A structure: ui.selected
            $shopCategories = $categoryMappings['ui']['selected'] ?? [];
            $categorySource = $categoryMappings['metadata']['source'] ?? 'manual';

            if (!empty($shopCategories)) {
                // FIX 2026-02-10: Handle PrestaShop direct IDs from import publication
                // When source is 'prestashop_direct', ui.selected contains PS IDs (not PPM IDs)
                if ($categorySource === 'prestashop_direct') {
                    Log::debug('[CATEGORY SYNC] Using PrestaShop direct category IDs from import', [
                        'product_id' => $product->id,
                        'shop_id' => $shop->id,
                        'prestashop_category_ids' => $shopCategories,
                        'source' => 'prestashop_direct',
                    ]);

                    $associations = [];
                    $addedIds = [];

                    foreach ($shopCategories as $psId) {
                        $psId = (int) $psId;
                        if ($psId > 0 && !in_array($psId, $addedIds)) {
                            $associations[] = ['id' => $psId];
                            $addedIds[] = $psId;
                        }
                    }

                    if (!empty($associations)) {
                        Log::info('[CATEGORY SYNC] Category associations built from PrestaShop direct IDs', [
                            'product_id' => $product->id,
                            'shop_id' => $shop->id,
                            'association_count' => count($associations),
                            'prestashop_category_ids' => $addedIds,
                        ]);

                        return $this->injectRootCategories($associations);
                    }
                }

                Log::debug('[CATEGORY SYNC] Using shop-specific categories from category_mappings', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'ppm_category_ids' => $shopCategories,
                    'source' => 'product_shop_data.category_mappings',
                ]);

                // FIX 2025-11-19 BUG #2: Map PPM category IDs → PrestaShop IDs + BUILD PARENT HIERARCHY
                $associations = [];
                $addedIds = []; // Track to avoid duplicates

                foreach ($shopCategories as $categoryId) {
                    // NEW: Get full category hierarchy (child → parent → grandparent → ...)
                    $hierarchyIds = $this->getCategoryHierarchy($categoryId);

                    foreach ($hierarchyIds as $hierarchyCatId) {
                        $prestashopId = $this->categoryMapper->mapToPrestaShop((int) $hierarchyCatId, $shop);

                        if ($prestashopId && !in_array($prestashopId, $addedIds)) {
                            $associations[] = ['id' => $prestashopId];
                            $addedIds[] = $prestashopId;
                        } elseif (!$prestashopId) {
                            Log::warning('[CATEGORY SYNC] Category mapping not found in hierarchy', [
                                'product_id' => $product->id,
                                'shop_id' => $shop->id,
                                'ppm_category_id' => $hierarchyCatId,
                                'parent_of' => $categoryId !== $hierarchyCatId ? $categoryId : 'root',
                            ]);
                        }
                    }
                }

                if (!empty($associations)) {
                    Log::info('[CATEGORY SYNC] Category associations built with full hierarchy', [
                        'product_id' => $product->id,
                        'shop_id' => $shop->id,
                        'original_category_count' => count($shopCategories),
                        'association_count' => count($associations),
                        'prestashop_category_ids' => array_column($associations, 'id'),
                    ]);

                    // REQUIREMENT 2025-11-19: Auto-inject Baza + Wszystko root categories
                    return $this->injectRootCategories($associations);
                }
            }
        }

        // PRIORITY 2: Fallback - pivot table (BACKWARD COMPATIBILITY)
        // (Used ONLY if category_mappings is empty)
        $shopCategories = $product->categoriesForShop($shop->id, false)
            ->pluck('categories.id')
            ->toArray();

        if (!empty($shopCategories)) {
            Log::debug('[CATEGORY SYNC] Fallback: Using pivot table categories', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'ppm_category_ids' => $shopCategories,
                'reason' => 'category_mappings empty',
            ]);

            // FIX 2025-11-19 BUG #2: Map PPM category IDs → PrestaShop IDs + BUILD PARENT HIERARCHY
            $associations = [];
            $addedIds = []; // Track to avoid duplicates

            foreach ($shopCategories as $categoryId) {
                // NEW: Get full category hierarchy (child → parent → grandparent → ...)
                $hierarchyIds = $this->getCategoryHierarchy($categoryId);

                foreach ($hierarchyIds as $hierarchyCatId) {
                    $prestashopId = $this->categoryMapper->mapToPrestaShop((int) $hierarchyCatId, $shop);

                    if ($prestashopId && !in_array($prestashopId, $addedIds)) {
                        $associations[] = ['id' => $prestashopId];
                        $addedIds[] = $prestashopId;
                    } elseif (!$prestashopId) {
                        Log::warning('[CATEGORY SYNC] Category mapping not found in hierarchy', [
                            'product_id' => $product->id,
                            'shop_id' => $shop->id,
                            'ppm_category_id' => $hierarchyCatId,
                            'parent_of' => $categoryId !== $hierarchyCatId ? $categoryId : 'root',
                        ]);
                    }
                }
            }

            if (!empty($associations)) {
                Log::info('[CATEGORY SYNC] Category associations built from pivot fallback', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'original_category_count' => count($shopCategories),
                    'association_count' => count($associations),
                    'prestashop_category_ids' => array_column($associations, 'id'),
                ]);

                // REQUIREMENT 2025-11-19: Auto-inject Baza + Wszystko root categories
                return $this->injectRootCategories($associations);
            }
        }

        // PRIORITY 3: Fallback - use default product categories if no shop-specific
        $shopData = $product->dataForShop($shop->id)->first();

        if ($shopData && $shopData->hasCategoryMappings()) {
            Log::debug('[CATEGORY SYNC] Fallback: Using category_mappings cache', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'reason' => 'Pivot table empty',
            ]);

            $prestashopIds = $this->extractPrestaShopIds($shopData->category_mappings);

            if (!empty($prestashopIds)) {
                $associations = [];
                foreach ($prestashopIds as $prestashopId) {
                    $associations[] = ['id' => (int) $prestashopId];
                }

                Log::info('[CATEGORY SYNC] Category associations built from cache', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'association_count' => count($associations),
                    'prestashop_category_ids' => $prestashopIds,
                ]);

                // REQUIREMENT 2025-11-19: Auto-inject Baza + Wszystko root categories
                return $this->injectRootCategories($associations);
            }
        }

        // PRIORITY 3: Final fallback - global categories
        $categoryIds = $product->categories()->pluck('categories.id')->toArray();

        Log::debug('[CATEGORY SYNC] Using product default categories', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'category_ids' => $categoryIds,
        ]);

        // If no categories at all, use default (Home)
        if (empty($categoryIds)) {
            Log::warning('[CATEGORY SYNC] No categories found, using default (Home)', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
            ]);

            // REQUIREMENT 2025-11-19: Auto-inject Baza + Wszystko root categories
            // Even for fallback, inject root chain (will become [1, 2])
            return $this->injectRootCategories([['id' => 2]]);
        }

        $associations = [];

        // Map each PPM category ID to PrestaShop category ID via CategoryMapper
        foreach ($categoryIds as $categoryId) {
            $prestashopId = $this->categoryMapper->mapToPrestaShop((int) $categoryId, $shop);

            if ($prestashopId) {
                $associations[] = ['id' => $prestashopId];
            } else {
                Log::warning('[CATEGORY SYNC] Category mapping not found', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'ppm_category_id' => $categoryId,
                ]);
            }
        }

        // Fallback: If no categories mapped successfully, use default
        if (empty($associations)) {
            Log::warning('[CATEGORY SYNC] No categories mapped successfully, using default (Home)', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'attempted_category_ids' => $categoryIds,
            ]);

            // REQUIREMENT 2025-11-19: Auto-inject Baza + Wszystko root categories
            // Even for fallback, inject root chain (will become [1, 2])
            return $this->injectRootCategories([['id' => 2]]);
        }

        Log::info('[CATEGORY SYNC] Category associations built from global categories', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'association_count' => count($associations),
            'prestashop_category_ids' => array_column($associations, 'id'),
        ]);

        // REQUIREMENT 2025-11-19: Auto-inject Baza + Wszystko root categories
        return $this->injectRootCategories($associations);
    }

    /**
     * Extract PrestaShop category IDs from category_mappings
     *
     * FIX #12: Handles Option A structure with backward compatibility
     *
     * @param array $categoryMappings Option A structure
     * @return array PrestaShop category IDs
     */
    private function extractPrestaShopIds(array $categoryMappings): array
    {
        // Try mappings first (canonical Option A)
        if (isset($categoryMappings['mappings']) && is_array($categoryMappings['mappings'])) {
            return array_values($categoryMappings['mappings']);
        }

        // Backward compatibility: UI format without mappings
        if (isset($categoryMappings['ui']) && !isset($categoryMappings['mappings'])) {
            return []; // UI format requires CategoryMapper
        }

        // Legacy format: {"100": 9, "103": 15} (direct PPM → PrestaShop mapping)
        $values = array_values($categoryMappings);
        if (count($values) > 0 && is_int($values[0])) {
            return $values;
        }

        return [];
    }

    /**
     * Map PPM tax rate to PrestaShop tax rules group ID
     *
     * ENTERPRISE SOLUTION (2025-11-14): Dynamic per-shop mapping
     *
     * Strategy:
     * 1. Use shop-configured tax_rules_group_id_XX from database (if set)
     * 2. Fallback to PrestaShop API auto-detection (cache result)
     * 3. Final fallback to sensible defaults
     *
     * Why dynamic mapping?
     * - Different PrestaShop installations use different tax_rules_group IDs
     * - Example: Shop A uses ID 1 for 23% VAT, Shop B uses ID 6
     * - Hardcoding caused products to have wrong tax rates
     *
     * Reference: _ISSUES_FIXES/PRESTASHOP_TAX_RULES_OVERWRITE_ISSUE.md
     *
     * @param float $taxRate Tax rate percentage (23, 8, 5, 0)
     * @param PrestaShopShop $shop Shop instance with tax rules configuration
     * @return int PrestaShop tax_rules_group_id
     */
    private function mapTaxRate(float $taxRate, PrestaShopShop $shop): int
    {
        // Round tax rate to nearest standard rate
        $roundedRate = match (true) {
            $taxRate >= 23 => 23,
            $taxRate >= 8 && $taxRate < 23 => 8,
            $taxRate >= 5 && $taxRate < 8 => 5,
            $taxRate < 5 => 0,
            default => 23,
        };

        // 1. Try shop-configured mapping (preferred - no API calls)
        $configuredId = match ($roundedRate) {
            23 => $shop->tax_rules_group_id_23,
            8 => $shop->tax_rules_group_id_8,
            5 => $shop->tax_rules_group_id_5,
            0 => $shop->tax_rules_group_id_0,
            default => null,
        };

        if ($configuredId !== null) {
            Log::debug('[ProductTransformer] Using configured tax rules group', [
                'tax_rate' => $taxRate,
                'rounded_rate' => $roundedRate,
                'group_id' => $configuredId,
                'shop_id' => $shop->id,
            ]);

            return $configuredId;
        }

        // 2. Auto-detect from PrestaShop API (if not configured or stale)
        $shouldAutoDetect = $shop->tax_rules_last_fetched_at === null
            || $shop->tax_rules_last_fetched_at->lt(now()->subDays(7));

        if ($shouldAutoDetect) {
            Log::info('[ProductTransformer] Auto-detecting tax rules from PrestaShop API', [
                'shop_id' => $shop->id,
                'last_fetched' => $shop->tax_rules_last_fetched_at,
            ]);

            try {
                $this->autoDetectTaxRules($shop);

                // Reload shop from database (fresh instance to avoid caching issues)
                $freshShop = PrestaShopShop::find($shop->id);

                if (!$freshShop) {
                    throw new \Exception("Failed to reload shop after auto-detection");
                }

                // Retry configured mapping after auto-detection (using fresh instance)
                $configuredId = match ($roundedRate) {
                    23 => $freshShop->tax_rules_group_id_23,
                    8 => $freshShop->tax_rules_group_id_8,
                    5 => $freshShop->tax_rules_group_id_5,
                    0 => $freshShop->tax_rules_group_id_0,
                    default => null,
                };

                if ($configuredId !== null) {
                    Log::debug('[ProductTransformer] Using auto-detected tax rules group', [
                        'tax_rate' => $taxRate,
                        'rounded_rate' => $roundedRate,
                        'group_id' => $configuredId,
                        'shop_id' => $shop->id,
                    ]);

                    return $configuredId;
                }
            } catch (\Exception $e) {
                Log::warning('[ProductTransformer] Failed to auto-detect tax rules, using fallback', [
                    'shop_id' => $shop->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 3. Fallback to sensible defaults (Polish tax system)
        $fallbackId = match ($roundedRate) {
            23 => 1, // PL Standard Rate (most common)
            8 => 2,  // PL Reduced Rate
            5 => 3,  // PL Super Reduced Rate
            0 => 4,  // PL Exempt Rate
            default => 1,
        };

        Log::warning('[ProductTransformer] Using fallback tax rules group (consider configuring shop)', [
            'tax_rate' => $taxRate,
            'rounded_rate' => $roundedRate,
            'fallback_id' => $fallbackId,
            'shop_id' => $shop->id,
        ]);

        return $fallbackId;
    }

    /**
     * Auto-detect tax rules group IDs from PrestaShop API
     *
     * Fetches all tax_rule_groups from PrestaShop and maps rates to IDs
     * Updates shop configuration and sets last_fetched timestamp
     *
     * @param PrestaShopShop $shop Shop to auto-detect for
     * @throws \Exception If API call fails
     */
    private function autoDetectTaxRules(PrestaShopShop $shop): void
    {
        // REFACTORED (FAZA 5.1): Use centralized getTaxRuleGroups() method
        // Previous: Inline API call with makeRequest('GET', '/tax_rule_groups')
        // Current: Use new getTaxRuleGroups() method for consistency and reusability

        // Create API client
        $client = \App\Services\PrestaShop\PrestaShopClientFactory::create($shop);

        // Fetch tax_rule_groups using new centralized method
        $taxRuleGroups = $client->getTaxRuleGroups();

        if (empty($taxRuleGroups)) {
            throw new \Exception('No tax_rule_groups found in PrestaShop');
        }

        $mapping = [
            23 => null,
            8 => null,
            5 => null,
            0 => null,
        ];

        // Parse standardized response format: [['id' => 6, 'name' => 'PL Standard Rate (23%)', 'rate' => 23.0], ...]
        foreach ($taxRuleGroups as $group) {
            $groupId = $group['id'];
            $groupName = $group['name'];

            // Match by name patterns (Polish tax system)
            // STRATEGY: Prefer HIGHEST ID (most recent/current configuration)
            if (str_contains(strtolower($groupName), '23%') || str_contains(strtolower($groupName), 'standard')) {
                if ($mapping[23] === null || $groupId > $mapping[23]) {
                    $mapping[23] = $groupId; // Prefer highest ID
                }
            } elseif (str_contains(strtolower($groupName), '8%') || str_contains(strtolower($groupName), 'reduced rate (8%)')) {
                if ($mapping[8] === null || $groupId > $mapping[8]) {
                    $mapping[8] = $groupId;
                }
            } elseif (str_contains(strtolower($groupName), '5%') || str_contains(strtolower($groupName), 'reduced rate (5%)')) {
                if ($mapping[5] === null || $groupId > $mapping[5]) {
                    $mapping[5] = $groupId;
                }
            } elseif (str_contains(strtolower($groupName), '0%') || str_contains(strtolower($groupName), 'exempt')) {
                if ($mapping[0] === null || $groupId > $mapping[0]) {
                    $mapping[0] = $groupId;
                }
            }
        }

        // Update shop configuration
        Log::debug('[ProductTransformer] About to update shop with tax rules mapping', [
            'shop_id' => $shop->id,
            'mapping' => $mapping,
        ]);

        $updated = $shop->update([
            'tax_rules_group_id_23' => $mapping[23],
            'tax_rules_group_id_8' => $mapping[8],
            'tax_rules_group_id_5' => $mapping[5],
            'tax_rules_group_id_0' => $mapping[0],
            'tax_rules_last_fetched_at' => now(),
        ]);

        Log::info('[ProductTransformer] Auto-detected tax rules successfully', [
            'shop_id' => $shop->id,
            'mapping' => $mapping,
            'update_result' => $updated,
        ]);

        // Verify update was persisted
        $verification = PrestaShopShop::find($shop->id);
        Log::debug('[ProductTransformer] Verification after update', [
            'shop_id' => $shop->id,
            'tax_rules_group_id_23_in_db' => $verification->tax_rules_group_id_23,
            'tax_rules_group_id_23_expected' => $mapping[23],
        ]);
    }

    /**
     * Apply PrestaShop 9.x specific adjustments
     *
     * @param array $productData Product data
     * @return array Adjusted product data
     */
    private function applyVersion9Adjustments(array $productData): array
    {
        // PrestaShop 9.x requires additional fields or different structure
        // Currently no specific adjustments needed, but ready for future changes

        Log::debug('Applied PrestaShop 9.x adjustments');

        return $productData;
    }

    /**
     * Validate product before transformation
     *
     * @param Product $product Product instance
     * @throws InvalidArgumentException On validation failure
     */
    private function validateProduct(Product $product): void
    {
        if (empty($product->sku)) {
            throw new InvalidArgumentException("Product SKU is required for PrestaShop sync (product ID: {$product->id})");
        }

        if (empty($product->name)) {
            throw new InvalidArgumentException("Product name is required for PrestaShop sync (product ID: {$product->id})");
        }

        // Validate categories exist
        if ($product->categories->isEmpty()) {
            Log::warning('Product has no categories', [
                'product_id' => $product->id,
            ]);
        }

        // Validate price exists
        if ($product->prices->isEmpty()) {
            Log::warning('Product has no prices', [
                'product_id' => $product->id,
            ]);
        }
    }

    /**
     * Transform PrestaShop API product data to PPM format
     *
     * ETAP_07 FAZA 2A.1 - Reverse Transformation (PrestaShop → PPM)
     *
     * Converts PrestaShop product structure to PPM Product model format.
     * Handles multilingual fields, category mapping, and data type conversions.
     *
     * PrestaShop product structure example:
     * [
     *     'id' => 123,
     *     'name' => [['id' => 1, 'value' => 'Nazwa PL'], ['id' => 2, 'value' => 'Name EN']],
     *     'description' => [...],
     *     'reference' => 'SKU-12345',
     *     'price' => '199.99',
     *     'id_category_default' => 5,
     *     'quantity' => 10,
     *     'active' => '1',
     *     'weight' => '2.5',
     *     ...
     * ]
     *
     * @param array $prestashopProduct PrestaShop API product data
     * @param PrestaShopShop $shop Shop instance
     * @return array PPM Product format (ready for create/update)
     */
    public function transformToPPM(array $prestashopProduct, PrestaShopShop $shop): array
    {
        Log::debug('ProductTransformer: transformToPPM CALLED', [
            'prestashop_product_id' => data_get($prestashopProduct, 'id'),
            'shop_id' => $shop->id,
            'product_keys' => array_keys($prestashopProduct),
        ]);

        try {
            // Extract multilingual fields (language ID 1 = Polish, 2 = English)
            $namePL = $this->extractMultilangValue($prestashopProduct, 'name', 1);
            $nameEN = $this->extractMultilangValue($prestashopProduct, 'name', 2);
            $descriptionPL = $this->extractMultilangValue($prestashopProduct, 'description', 1);
            $descriptionEN = $this->extractMultilangValue($prestashopProduct, 'description', 2);
            $shortDescPL = $this->extractMultilangValue($prestashopProduct, 'description_short', 1);
            $shortDescEN = $this->extractMultilangValue($prestashopProduct, 'description_short', 2);

            // Map PrestaShop category to PPM category
            $categoryId = null;
            if (isset($prestashopProduct['id_category_default'])) {
                $categoryId = $this->categoryMapper->mapFromPrestaShop(
                    (int) $prestashopProduct['id_category_default'],
                    $shop
                );

                if ($categoryId === null) {
                    Log::warning('PrestaShop category not mapped to PPM', [
                        'prestashop_category_id' => $prestashopProduct['id_category_default'],
                        'shop_id' => $shop->id,
                    ]);
                }
            }

            // Build PPM product data
            $ppmProduct = [
                // Identifiers
                'prestashop_product_id' => (int) ($prestashopProduct['id'] ?? 0),
                'sku' => $prestashopProduct['reference'] ?? null,
                'ean' => $prestashopProduct['ean13'] ?? null,

                // Names (multilingual)
                'name' => $namePL,
                'name_en' => $nameEN,

                // Descriptions (multilingual)
                'short_description' => $shortDescPL,
                'short_description_en' => $shortDescEN,
                'long_description' => $descriptionPL,
                'long_description_en' => $descriptionEN,

                // Category mapping
                'category_id' => $categoryId,

                // Product type (default to "spare_part" for imported products)
                // PrestaShop types: Standard, virtual, Pack
                // PPM types: 1=pojazd, 2=czesc-zamienna, 3=odziez, 4=inne
                // User can change type manually in PPM if needed
                'product_type_id' => 2, // Default: Część zamienna (spare_part)

                // Status (PrestaShop uses '0'/'1' strings, convert to bool)
                'is_active' => $this->convertPrestaShopBoolean($prestashopProduct['active'] ?? '0'),

                // Physical dimensions (convert strings to floats)
                'weight' => isset($prestashopProduct['weight']) ? (float) $prestashopProduct['weight'] : null,
                'width' => isset($prestashopProduct['width']) ? (float) $prestashopProduct['width'] : null,
                'height' => isset($prestashopProduct['height']) ? (float) $prestashopProduct['height'] : null,
                'length' => isset($prestashopProduct['depth']) ? (float) $prestashopProduct['depth'] : null,

                // Tax rate (reverse map from PrestaShop tax_rules_group_id)
                'tax_rate' => $this->reverseMapTaxRate((int) ($prestashopProduct['id_tax_rules_group'] ?? 1)),

                // Manufacturer (FIX 2025-12-15: Import manufacturer_id from PrestaShop)
                'manufacturer' => $prestashopProduct['manufacturer_name'] ?? null,
                'manufacturer_id' => $this->importManufacturer($prestashopProduct, $shop),

                // Importer / Supplier (ETAP_08: Import importer from PrestaShop supplier)
                'importer_id' => $this->importImporter($prestashopProduct, $shop),

                // Timestamps (preserve PrestaShop dates)
                'created_at' => $prestashopProduct['date_add'] ?? now(),
                'updated_at' => $prestashopProduct['date_upd'] ?? now(),
            ];

            Log::info('Product transformed from PrestaShop to PPM', [
                'prestashop_product_id' => $ppmProduct['prestashop_product_id'],
                'sku' => $ppmProduct['sku'],
                'shop_id' => $shop->id,
                'category_mapped' => $categoryId !== null,
            ]);

            return $ppmProduct;

        } catch (\Exception $e) {
            Log::error('Product transformation from PrestaShop failed', [
                'prestashop_product_id' => data_get($prestashopProduct, 'id'),
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new InvalidArgumentException(
                "Failed to transform PrestaShop product to PPM: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Transform PrestaShop product price to PPM format
     *
     * ETAP_07 FAZA 2A.1 - Price Transformation
     *
     * Converts PrestaShop price structure to PPM ProductPrice model format.
     * PrestaShop has single base price, PPM has multiple price groups.
     *
     * @param array $prestashopProduct PrestaShop API product data
     * @param PrestaShopShop $shop Shop instance
     * @return array Array of price group data for ProductPrice model
     */
    public function transformPriceToPPM(array $prestashopProduct, PrestaShopShop $shop): array
    {
        Log::debug('ProductTransformer: transformPriceToPPM CALLED', [
            'prestashop_product_id' => data_get($prestashopProduct, 'id'),
            'price' => data_get($prestashopProduct, 'price'),
            'shop_id' => $shop->id,
        ]);

        try {
            // Extract price from PrestaShop (always net price)
            $price = isset($prestashopProduct['price']) ? (float) $prestashopProduct['price'] : 0.0;

            // Get default price group for shop
            $defaultPriceGroup = $this->priceGroupMapper->getDefaultPriceGroup($shop);

            // Build price array (PPM requires price groups, PrestaShop has single price)
            $prices = [
                [
                    'price_group' => $defaultPriceGroup->code ?? 'detaliczna',
                    'price' => $price,
                    'price_min' => null, // PrestaShop doesn't have min price concept
                    'currency' => 'PLN', // MPP TRADE only uses PLN
                ]
            ];

            Log::info('Price transformed from PrestaShop to PPM', [
                'prestashop_product_id' => data_get($prestashopProduct, 'id'),
                'price' => $price,
                'price_group' => $defaultPriceGroup->code ?? 'detaliczna',
            ]);

            return $prices;

        } catch (\Exception $e) {
            Log::error('Price transformation from PrestaShop failed', [
                'prestashop_product_id' => data_get($prestashopProduct, 'id'),
                'error' => $e->getMessage(),
            ]);

            // Return empty array on error (no prices)
            return [];
        }
    }

    /**
     * Transform PrestaShop product stock to PPM format
     *
     * ETAP_07 FAZA 2A.1 - Stock Transformation
     *
     * Converts PrestaShop quantity to PPM Stock model format.
     * PrestaShop has single quantity value, PPM has warehouse-based stock.
     *
     * @param array $prestashopProduct PrestaShop API product data
     * @param PrestaShopShop $shop Shop instance
     * @return array Array of stock data for Stock model
     */
    public function transformStockToPPM(array $prestashopProduct, PrestaShopShop $shop): array
    {
        Log::debug('ProductTransformer: transformStockToPPM CALLED', [
            'prestashop_product_id' => data_get($prestashopProduct, 'id'),
            'quantity' => data_get($prestashopProduct, 'quantity'),
            'shop_id' => $shop->id,
        ]);

        try {
            // Extract quantity from PrestaShop
            $quantity = isset($prestashopProduct['quantity']) ? (int) $prestashopProduct['quantity'] : 0;

            // Build stock array (assign to default warehouse)
            $stock = [
                [
                    'warehouse_code' => 'MPPTRADE', // Default main warehouse
                    'quantity' => $quantity,
                    'reserved' => 0, // PrestaShop doesn't track reservations
                    'available' => $quantity, // available = quantity - reserved
                ]
            ];

            Log::info('Stock transformed from PrestaShop to PPM', [
                'prestashop_product_id' => data_get($prestashopProduct, 'id'),
                'quantity' => $quantity,
                'warehouse' => 'MPPTRADE',
            ]);

            return $stock;

        } catch (\Exception $e) {
            Log::error('Stock transformation from PrestaShop failed', [
                'prestashop_product_id' => data_get($prestashopProduct, 'id'),
                'error' => $e->getMessage(),
            ]);

            // Return empty array on error (no stock)
            return [];
        }
    }

    /**
     * Extract multilingual field value from PrestaShop structure
     *
     * PrestaShop multilingual format: [['id' => 1, 'value' => 'Text PL'], ['id' => 2, 'value' => 'Text EN']]
     *
     * @param array $prestashopProduct PrestaShop product data
     * @param string $fieldName Field name (e.g., 'name', 'description')
     * @param int $languageId Language ID (1 = Polish, 2 = English)
     * @return string|null Field value or null if not found
     */
    private function extractMultilangValue(array $prestashopProduct, string $fieldName, int $languageId): ?string
    {
        $field = $prestashopProduct[$fieldName] ?? null;

        // If field doesn't exist, return null
        if ($field === null) {
            return null;
        }

        // If field is string (single language mode), return as-is
        if (is_string($field)) {
            return $field;
        }

        // If field is array (multilingual mode), find matching language
        if (is_array($field)) {
            foreach ($field as $langData) {
                if (isset($langData['id']) && (int) $langData['id'] === $languageId) {
                    return $langData['value'] ?? null;
                }
            }
        }

        // Language not found
        return null;
    }

    /**
     * Convert PrestaShop boolean string to PHP boolean
     *
     * PrestaShop uses '0'/'1' strings for boolean values
     *
     * @param mixed $value PrestaShop boolean value
     * @return bool PHP boolean
     */
    private function convertPrestaShopBoolean(mixed $value): bool
    {
        // Handle string '0'/'1'
        if ($value === '1' || $value === 1 || $value === true) {
            return true;
        }

        if ($value === '0' || $value === 0 || $value === false) {
            return false;
        }

        // Default to false for null/empty
        return false;
    }

    /**
     * Reverse map PrestaShop tax_rules_group_id to PPM tax rate
     *
     * PrestaShop tax rules groups:
     * - 1: 23% VAT (standard)
     * - 2: 8% VAT (reduced)
     * - 3: 5% VAT (reduced)
     * - 4: 0% VAT (exempt)
     *
     * @param int $taxRulesGroupId PrestaShop tax_rules_group_id
     * @return float Tax rate percentage
     */
    private function reverseMapTaxRate(int $taxRulesGroupId): float
    {
        return match ($taxRulesGroupId) {
            1 => 23.0, // Standard VAT
            2 => 8.0,  // Reduced VAT
            3 => 5.0,  // Reduced VAT
            4 => 0.0,  // VAT exempt
            default => 23.0, // Default to standard VAT
        };
    }

    /**
     * Get default category ID (primary category from pivot table)
     *
     * FIX 2025-11-19 BUG #3: Use primary category instead of first category
     *
     * Business Logic:
     * - PRIORITY 1: Primary category from pivot table (is_primary=true)
     * - PRIORITY 2: First category in associations (fallback)
     * - PRIORITY 3: PrestaShop default (2 = Home)
     *
     * @param Product $product Product instance
     * @param PrestaShopShop $shop Shop instance
     * @param array $categoryAssociations Built category associations
     * @return int PrestaShop category ID
     */
    private function getDefaultCategoryId(Product $product, PrestaShopShop $shop, array $categoryAssociations): int
    {
        // FIX 2025-11-21: Shop-specific categories are in ProductShopData.category_mappings (Option A)
        // NOT in product_categories pivot table (DISABLED since 2025-11-20)

        // PRIORITY 1: Get primary category from ProductShopData.category_mappings['ui']['primary']
        $shopData = $product->shopData->where('shop_id', $shop->id)->first();

        if ($shopData && !empty($shopData->category_mappings)) {
            $categoryMappings = $shopData->category_mappings;
            $primaryCategoryId = $categoryMappings['ui']['primary'] ?? null;
            $categorySource = $categoryMappings['metadata']['source'] ?? 'manual';

            if ($primaryCategoryId) {
                // FIX 2026-02-10: If source is prestashop_direct, primary is already a PS ID
                if ($categorySource === 'prestashop_direct') {
                    Log::debug('[CATEGORY SYNC] Using PrestaShop direct primary category as default', [
                        'product_id' => $product->id,
                        'shop_id' => $shop->id,
                        'prestashop_primary_id' => $primaryCategoryId,
                        'source' => 'prestashop_direct',
                    ]);

                    return (int) $primaryCategoryId;
                }

                // Map PPM category ID to PrestaShop ID
                $prestashopPrimaryId = $this->categoryMapper->mapToPrestaShop((int) $primaryCategoryId, $shop);

                if ($prestashopPrimaryId) {
                    Log::debug('[CATEGORY SYNC] Using shop-specific primary category as default (from category_mappings)', [
                        'product_id' => $product->id,
                        'shop_id' => $shop->id,
                        'ppm_primary_id' => $primaryCategoryId,
                        'prestashop_primary_id' => $prestashopPrimaryId,
                        'source' => 'ProductShopData.category_mappings',
                    ]);

                    return $prestashopPrimaryId;
                }
            }
        }

        // PRIORITY 2: Fallback to first NON-ROOT category in associations
        // BUG FIX 2025-11-19: After injectRootCategories(), first category is always Baza (1)
        // Skip root categories (1 = Baza, 2 = Wszystko) to find first real category
        if (!empty($categoryAssociations)) {
            $firstRealCategory = null;

            foreach ($categoryAssociations as $assoc) {
                if (!in_array($assoc['id'], [1, 2])) {
                    $firstRealCategory = $assoc['id'];
                    break;
                }
            }

            if ($firstRealCategory) {
                Log::warning('[CATEGORY SYNC] No primary category, using first NON-ROOT association', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'default_id' => $firstRealCategory,
                    'skipped_root_categories' => [1, 2],
                ]);

                return $firstRealCategory;
            }
        }

        // PRIORITY 3: Fallback to PrestaShop default (2 = Home)
        Log::warning('[CATEGORY SYNC] No categories, using PrestaShop default', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
        ]);

        return 2;
    }

    /**
     * Get full category hierarchy (child → parent → grandparent → root)
     *
     * FIX 2025-11-19 BUG #2: Build parent hierarchy to send full tree to PrestaShop
     *
     * Example:
     * Input: Category ID 61 (TEST-PPM, parent: 60 Buggy, grandparent: NULL)
     * Output: [61, 60] (child to root)
     *
     * @param int $categoryId Leaf category ID
     * @return array Array of category IDs from leaf to root
     */
    private function getCategoryHierarchy(int $categoryId): array
    {
        $hierarchy = [];
        $currentId = $categoryId;
        $maxDepth = 10; // Safety limit to prevent infinite loops
        $depth = 0;

        while ($currentId && $depth < $maxDepth) {
            $hierarchy[] = $currentId;

            // Get parent
            $category = Category::find($currentId);

            if (!$category || !$category->parent_id) {
                break;
            }

            $currentId = $category->parent_id;
            $depth++;
        }

        if ($depth >= $maxDepth) {
            Log::error('[CATEGORY SYNC] Max depth reached in category hierarchy', [
                'start_category_id' => $categoryId,
                'hierarchy' => $hierarchy,
            ]);
        }

        // Return hierarchy from child to root (e.g., [61, 60, 1])
        return $hierarchy;
    }

    /**
     * Inject PrestaShop root categories (Baza + Wszystko)
     *
     * REQUIREMENT 2025-11-19: Auto-inject root category chain for ALL products
     *
     * Business Rule:
     * - Every PrestaShop product MUST have root category chain
     * - Structure: Baza (1) → Wszystko (2) → [Product Categories]
     * - UI Display: PPM hides Baza/Wszystko for readability
     * - Export Behavior: ALWAYS include Baza + Wszystko in associations
     *
     * PrestaShop Root Categories (STANDARD in every installation):
     * - ID 1: "Baza" (Root category, parent_id = 0)
     * - ID 2: "Wszystko" or "Home" (Home category, parent_id = 1)
     *
     * These IDs are HARDCODED because they are standard in every PrestaShop
     * installation and never change between shops.
     *
     * @param array $associations Existing category associations
     * @return array Associations with root categories prepended
     */
    private function injectRootCategories(array $associations): array
    {
        // PrestaShop root categories (HARDCODED - standard in every PS installation)
        $rootCategories = [
            ['id' => 1], // Baza (Root)
            ['id' => 2], // Wszystko (Home)
        ];

        // Extract IDs from existing associations
        $existingIds = array_column($associations, 'id');

        // Remove root categories if already present (avoid duplicates)
        $filteredAssociations = array_filter($associations, function($assoc) {
            return !in_array($assoc['id'], [1, 2]);
        });

        // Prepend root categories → Final structure: [1, 2, 9, 15, 800, ...]
        $result = array_merge($rootCategories, array_values($filteredAssociations));

        Log::info('[ROOT INJECTION] Added Baza + Wszystko to category associations', [
            'original_count' => count($associations),
            'final_count' => count($result),
            'original_ids' => $existingIds,
            'final_ids' => array_column($result, 'id'),
        ]);

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | MANUFACTURER MAPPING (FIX 2025-12-15)
    |--------------------------------------------------------------------------
    */

    /**
     * Import manufacturer from PrestaShop product data
     *
     * FIX 2025-12-15: Auto-import manufacturers during product import
     *
     * Workflow:
     * 1. Extract manufacturer_name and id_manufacturer from PS product
     * 2. If manufacturer_name is empty, return null
     * 3. Look up existing manufacturer by code (slugified name)
     * 4. If not found, create new manufacturer
     * 5. Assign manufacturer to shop with PS manufacturer ID
     * 6. Return manufacturer_id for Product
     *
     * @param array $prestashopProduct PrestaShop product data
     * @param PrestaShopShop $shop Shop instance
     * @return int|null Manufacturer ID or null
     */
    private function importManufacturer(array $prestashopProduct, PrestaShopShop $shop): ?int
    {
        $manufacturerName = $prestashopProduct['manufacturer_name'] ?? null;
        $psManufacturerId = isset($prestashopProduct['id_manufacturer'])
            ? (int) $prestashopProduct['id_manufacturer']
            : null;

        // Skip if no manufacturer name
        if (empty($manufacturerName) || $psManufacturerId === 0) {
            return null;
        }

        // Generate code from name
        $code = \Illuminate\Support\Str::slug($manufacturerName, '_');

        // Look up existing manufacturer by code
        $manufacturer = Manufacturer::where('code', $code)->first();

        if (!$manufacturer) {
            // Create new manufacturer
            $manufacturer = Manufacturer::create([
                'name' => $manufacturerName,
                'code' => $code,
                'is_active' => true,
                'sort_order' => 0,
            ]);

            Log::info('ProductTransformer: Created new manufacturer during import', [
                'manufacturer_id' => $manufacturer->id,
                'name' => $manufacturerName,
                'code' => $code,
                'ps_manufacturer_id' => $psManufacturerId,
                'shop_id' => $shop->id,
            ]);
        }

        // Ensure manufacturer is assigned to shop with PS ID
        $existingPivot = $manufacturer->shops()
            ->where('prestashop_shop_id', $shop->id)
            ->first();

        if (!$existingPivot) {
            // Assign to shop with PrestaShop ID
            $manufacturer->shops()->attach($shop->id, [
                'ps_manufacturer_id' => $psManufacturerId,
                'sync_status' => 'synced',
                'last_synced_at' => now(),
            ]);
        } elseif ($existingPivot->pivot->ps_manufacturer_id !== $psManufacturerId) {
            // Update PS ID if different
            $manufacturer->shops()->updateExistingPivot($shop->id, [
                'ps_manufacturer_id' => $psManufacturerId,
                'sync_status' => 'synced',
                'last_synced_at' => now(),
            ]);
        }

        return $manufacturer->id;
    }

    /**
     * Get PrestaShop manufacturer ID for product sync
     *
     * FIX 2025-12-15: Map manufacturer_id to PrestaShop id_manufacturer
     *
     * @param Product $product PPM Product instance
     * @param PrestaShopShop $shop Shop instance
     * @return int|null PrestaShop manufacturer ID or null
     */
    private function getManufacturerPsId(Product $product, PrestaShopShop $shop): ?int
    {
        if (!$product->manufacturer_id) {
            return null;
        }

        $manufacturer = Manufacturer::find($product->manufacturer_id);
        if (!$manufacturer) {
            return null;
        }

        // Get PS manufacturer ID from pivot table
        $pivot = $manufacturer->shops()
            ->where('prestashop_shop_id', $shop->id)
            ->first();

        return $pivot?->pivot?->ps_manufacturer_id;
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORTER / SUPPLIER MAPPING (ETAP_08)
    |--------------------------------------------------------------------------
    */

    /**
     * Get PrestaShop supplier ID for product sync
     *
     * ETAP_08: Map importer_id (BusinessPartner) to PrestaShop id_supplier
     *
     * @param Product $product PPM Product instance
     * @param PrestaShopShop $shop Shop instance
     * @return int|null PrestaShop supplier ID or null
     */
    private function getImporterPsId(Product $product, PrestaShopShop $shop): ?int
    {
        if (!$product->importer_id) {
            return null;
        }

        $importer = BusinessPartner::find($product->importer_id);
        if (!$importer || !$importer->isImporter()) {
            return null;
        }

        return $importer->getPsSupplierIdForShop($shop->id);
    }

    /**
     * Import importer from PrestaShop product supplier data
     *
     * ETAP_08: Auto-import importers during product import
     *
     * Workflow:
     * 1. Extract supplier_name and id_supplier from PS product
     * 2. If supplier_name is empty or id_supplier is 0, return null
     * 3. Look up existing BusinessPartner (type=importer) by code
     * 4. If not found, create new BusinessPartner
     * 5. Assign to shop with PS supplier ID
     * 6. Return importer_id for Product
     *
     * @param array $prestashopProduct PrestaShop product data
     * @param PrestaShopShop $shop Shop instance
     * @return int|null Importer (BusinessPartner) ID or null
     */
    private function importImporter(array $prestashopProduct, PrestaShopShop $shop): ?int
    {
        $supplierName = $prestashopProduct['supplier_name'] ?? null;
        $psSupplierId = (int) ($prestashopProduct['id_supplier'] ?? 0);

        if (empty($supplierName) || $psSupplierId === 0) {
            return null;
        }

        $code = Str::slug($supplierName, '_');

        // Look up existing importer by code
        $importer = BusinessPartner::where('code', $code)
            ->where('type', 'importer')
            ->first();

        if (!$importer) {
            $importer = BusinessPartner::create([
                'name' => $supplierName,
                'code' => $code,
                'type' => 'importer',
                'is_active' => true,
            ]);

            Log::info('ProductTransformer: Created new importer during import', [
                'importer_id' => $importer->id,
                'name' => $supplierName,
                'code' => $code,
                'ps_supplier_id' => $psSupplierId,
                'shop_id' => $shop->id,
            ]);
        }

        // Ensure importer is assigned to shop with PS supplier ID
        if (!$importer->shops()->where('prestashop_shop_id', $shop->id)->exists()) {
            $importer->shops()->attach($shop->id, [
                'ps_supplier_id' => $psSupplierId,
                'sync_status' => 'synced',
                'last_synced_at' => now(),
            ]);
        }

        return $importer->id;
    }
}
