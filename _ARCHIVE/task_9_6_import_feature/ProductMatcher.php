<?php

namespace App\Services\PrestaShop;

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use Illuminate\Support\Facades\Log;

/**
 * Product Matcher Service
 *
 * ETAP_07 - Task 9.6: Import New Products Feature
 *
 * Matches PrestaShop products to existing PPM products using SKU-FIRST architecture.
 * Implements intelligent matching strategy for import operations.
 *
 * Architecture (SKU-FIRST):
 * - PRIMARY: Match by SKU (PrestaShop reference field → PPM sku)
 * - FALLBACK: Match by external_id (product_shop_data.prestashop_product_id)
 * - GENERATE: Auto-generate SKU if PrestaShop reference is empty
 *
 * Matching Strategy:
 * 1. SKU match (highest priority - universal identifier)
 * 2. External ID match (shop-specific fallback)
 * 3. No match → return null (caller creates new product)
 *
 * SKU Generation (when PrestaShop reference is empty):
 * Format: PS-{SHOP_CODE}-{PRODUCT_ID}
 * Example: PS-PIT-12345 (Pitbike shop, product ID 12345)
 *
 * Usage:
 * ```php
 * $matcher = app(ProductMatcher::class);
 * $product = $matcher->findExistingProduct($psProduct, $shop);
 *
 * if (!$product) {
 *     // Create new product with auto-generated SKU
 *     $sku = $matcher->generateSKU($psProduct, $shop);
 * }
 * ```
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since ETAP_07 - Task 9.6
 */
class ProductMatcher
{
    /**
     * Find existing PPM product by PrestaShop data
     *
     * Implements SKU-FIRST matching strategy:
     * 1. Try SKU match (universal identifier)
     * 2. Try external_id match (shop-specific)
     * 3. Return null if not found
     *
     * @param array $psProduct PrestaShop product data
     * @param PrestaShopShop $shop Shop instance
     * @return Product|null Existing product or null if not found
     */
    public function findExistingProduct(array $psProduct, PrestaShopShop $shop): ?Product
    {
        $psId = data_get($psProduct, 'id');
        $sku = data_get($psProduct, 'reference');

        Log::debug('ProductMatcher: Starting product matching', [
            'prestashop_id' => $psId,
            'sku' => $sku,
            'shop_id' => $shop->id,
        ]);

        // PRIMARY: Match by SKU (PrestaShop reference field)
        if ($sku) {
            $product = Product::findBySku($sku);

            if ($product) {
                Log::info('ProductMatcher: Product matched by SKU', [
                    'sku' => $sku,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                ]);
                return $product;
            }
        }

        // FALLBACK: Match by external_id (product_shop_data.prestashop_product_id)
        if ($psId) {
            $productShopData = ProductShopData::where('shop_id', $shop->id)
                ->where('prestashop_product_id', $psId)
                ->first();

            if ($productShopData) {
                Log::info('ProductMatcher: Product matched by external_id', [
                    'prestashop_id' => $psId,
                    'product_id' => $productShopData->product_id,
                    'product_name' => $productShopData->product->name,
                ]);
                return $productShopData->product;
            }
        }

        // NOT FOUND
        Log::info('ProductMatcher: No existing product found', [
            'prestashop_id' => $psId,
            'sku' => $sku,
            'shop_id' => $shop->id,
        ]);
        return null;
    }

    /**
     * Generate SKU if PrestaShop reference is empty
     *
     * Format: PS-{SHOP_CODE}-{PRODUCT_ID}
     * - PS prefix indicates PrestaShop-generated SKU
     * - SHOP_CODE: First 3 uppercase letters of shop name
     * - PRODUCT_ID: PrestaShop product ID
     *
     * Examples:
     * - PS-PIT-12345 (Pitbike.pl shop, product 12345)
     * - PS-CAM-9876 (Cameraman shop, product 9876)
     *
     * @param array $psProduct PrestaShop product data
     * @param PrestaShopShop $shop Shop instance
     * @return string Generated SKU
     */
    public function generateSKU(array $psProduct, PrestaShopShop $shop): string
    {
        $psId = data_get($psProduct, 'id');
        $shopCode = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $shop->name), 0, 3));

        $generatedSku = "PS-{$shopCode}-{$psId}";

        Log::info('ProductMatcher: Generated SKU for product', [
            'prestashop_id' => $psId,
            'shop_name' => $shop->name,
            'shop_code' => $shopCode,
            'generated_sku' => $generatedSku,
        ]);

        return $generatedSku;
    }

    /**
     * Check if product already linked to this shop
     *
     * Prevents duplicate linking of same product to same shop.
     * Used in import workflow to skip already linked products.
     *
     * @param Product $product Product instance
     * @param PrestaShopShop $shop Shop instance
     * @return bool True if already linked, false otherwise
     */
    public function isAlreadyLinked(Product $product, PrestaShopShop $shop): bool
    {
        $isLinked = $product->shopData()
            ->where('shop_id', $shop->id)
            ->exists();

        if ($isLinked) {
            Log::debug('ProductMatcher: Product already linked to shop', [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'shop_id' => $shop->id,
            ]);
        }

        return $isLinked;
    }

    /**
     * Validate SKU uniqueness before creation
     *
     * Checks if generated/provided SKU is already used by another product.
     * Critical for preventing SKU collisions in import operations.
     *
     * @param string $sku SKU to validate
     * @param int|null $excludeProductId Product ID to exclude from check (for updates)
     * @return bool True if SKU is unique, false if already exists
     */
    public function isSkuUnique(string $sku, ?int $excludeProductId = null): bool
    {
        $query = Product::where('sku', strtoupper(trim($sku)));

        if ($excludeProductId) {
            $query->where('id', '!=', $excludeProductId);
        }

        $exists = $query->exists();

        if ($exists) {
            Log::warning('ProductMatcher: SKU already exists', [
                'sku' => $sku,
                'exclude_product_id' => $excludeProductId,
            ]);
        }

        return !$exists;
    }

    /**
     * Extract product name from PrestaShop data (multi-language support)
     *
     * PrestaShop stores names in multi-language format:
     * - Multi-language: ['language' => [['id' => 1, 'value' => 'Name']]]
     * - Single-language: "Name" (direct string)
     *
     * Priority:
     * 1. Polish language (id=1) if available
     * 2. First available language
     * 3. Direct string value
     * 4. Fallback to "Imported Product {ID}"
     *
     * @param array $psProduct PrestaShop product data
     * @return string Product name
     */
    public function extractProductName(array $psProduct): string
    {
        $psId = data_get($psProduct, 'id');
        $name = data_get($psProduct, 'name');

        // Multi-language format
        if (is_array($name)) {
            // Try to find Polish language (id=1)
            $languages = data_get($name, 'language', []);
            if (!is_array($languages)) {
                $languages = [$languages];
            }

            foreach ($languages as $lang) {
                if (is_array($lang) && data_get($lang, 'id') == 1) {
                    return data_get($lang, 'value', "Imported Product {$psId}");
                }
            }

            // Fallback to first language
            if (!empty($languages) && is_array($languages[0])) {
                return data_get($languages[0], 'value', "Imported Product {$psId}");
            }
        }

        // Single-language format (direct string)
        if (is_string($name) && !empty($name)) {
            return $name;
        }

        // Fallback
        Log::warning('ProductMatcher: Could not extract product name from PrestaShop data', [
            'prestashop_id' => $psId,
            'name_data' => $name,
        ]);

        return "Imported Product {$psId}";
    }
}
