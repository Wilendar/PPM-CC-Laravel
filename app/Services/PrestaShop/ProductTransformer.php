<?php

namespace App\Services\PrestaShop;

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\BasePrestaShopClient;
use App\Services\PrestaShop\CategoryMapper;
use App\Services\PrestaShop\PriceGroupMapper;
use App\Services\PrestaShop\WarehouseMapper;
use Illuminate\Support\Facades\Log;
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
                'description_short' => $this->buildMultilangField(
                    $this->getEffectiveValue($shopData, $product, 'short_description') ?? '',
                    $defaultLangId
                ),
                'description' => $this->buildMultilangField(
                    $this->getEffectiveValue($shopData, $product, 'long_description') ?? '',
                    $defaultLangId
                ),

                // Pricing (net price, PrestaShop calculates gross)
                'price' => $this->calculatePrice($product, $shop),

                // Physical properties
                'weight' => (float) ($product->weight ?? 0),
                'width' => (float) ($product->width ?? 0),
                'height' => (float) ($product->height ?? 0),
                'depth' => (float) ($product->length ?? 0), // PrestaShop uses 'depth' not 'length'

                // Status and visibility
                'active' => $this->getEffectiveValue($shopData, $product, 'is_active') ? 1 : 0,
                'available_for_order' => 1,
                'show_price' => 1,
                'visibility' => 'both', // both|catalog|search|none

                // Categories (mapped IDs)
                'associations' => [
                    'categories' => $this->buildCategoryAssociations($product, $shop),
                ],

                // Stock quantity (aggregated from warehouses)
                'quantity' => $this->warehouseMapper->calculateStockForShop($product, $shop),

                // Tax (PrestaShop tax_rules_group_id)
                'id_tax_rules_group' => $this->mapTaxRate($product->tax_rate),

                // Manufacturer
                'manufacturer_name' => $product->manufacturer ?? '',

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
     * @param Product $product Product instance
     * @param PrestaShopShop $shop Shop instance
     * @return array Category associations
     */
    private function buildCategoryAssociations(Product $product, PrestaShopShop $shop): array
    {
        $categories = [];

        // Get all product categories
        $productCategories = $product->categories;

        foreach ($productCategories as $category) {
            // Map PPM category to PrestaShop category
            $prestashopCategoryId = $this->categoryMapper->mapToPrestaShop($category->id, $shop);

            if ($prestashopCategoryId) {
                $categories[] = [
                    'id' => $prestashopCategoryId,
                ];
            }
        }

        // Fallback: If no categories mapped, use default category (2 = Home)
        if (empty($categories)) {
            Log::warning('No categories mapped, using default', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
            ]);

            $categories[] = [
                'id' => 2, // PrestaShop default category
            ];
        }

        return $categories;
    }

    /**
     * Map PPM tax rate to PrestaShop tax rules group ID
     *
     * Common Polish tax rates:
     * - 23% (standard VAT) → tax_rules_group_id 1
     * - 8% (reduced VAT) → tax_rules_group_id 2
     * - 5% (reduced VAT) → tax_rules_group_id 3
     * - 0% (VAT exempt) → tax_rules_group_id 4
     *
     * @param float $taxRate Tax rate percentage
     * @return int PrestaShop tax_rules_group_id
     */
    private function mapTaxRate(float $taxRate): int
    {
        return match (true) {
            $taxRate >= 23 => 1, // 23% VAT
            $taxRate >= 8 && $taxRate < 23 => 2, // 8% VAT
            $taxRate >= 5 && $taxRate < 8 => 3, // 5% VAT
            $taxRate < 5 => 4, // 0% VAT or exempt
            default => 1, // Default to standard VAT
        };
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

                // Status (PrestaShop uses '0'/'1' strings, convert to bool)
                'is_active' => $this->convertPrestaShopBoolean($prestashopProduct['active'] ?? '0'),

                // Physical dimensions (convert strings to floats)
                'weight' => isset($prestashopProduct['weight']) ? (float) $prestashopProduct['weight'] : null,
                'width' => isset($prestashopProduct['width']) ? (float) $prestashopProduct['width'] : null,
                'height' => isset($prestashopProduct['height']) ? (float) $prestashopProduct['height'] : null,
                'length' => isset($prestashopProduct['depth']) ? (float) $prestashopProduct['depth'] : null,

                // Tax rate (reverse map from PrestaShop tax_rules_group_id)
                'tax_rate' => $this->reverseMapTaxRate((int) ($prestashopProduct['id_tax_rules_group'] ?? 1)),

                // Manufacturer
                'manufacturer' => $prestashopProduct['manufacturer_name'] ?? null,

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
}
