<?php

namespace App\Services\PrestaShop;

use App\Exceptions\PrestaShopAPIException;
use Illuminate\Support\Facades\Log;

/**
 * PrestaShop 8.x API Client
 *
 * Implements BasePrestaShopClient for PrestaShop version 8.x
 */
class PrestaShop8Client extends BasePrestaShopClient
{
    public function getVersion(): string
    {
        return '8';
    }

    protected function getApiBasePath(): string
    {
        return '/api'; // PrestaShop 8.x uses /api
    }

    /**
     * Get all products with optional filters
     *
     * @param array $filters Query filters (limit, display, filter, sort)
     * @return array Products array
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getProducts(array $filters = []): array
    {
        $queryParams = $this->buildQueryParams($filters);
        $endpoint = empty($queryParams) ? '/products' : "/products?{$queryParams}";

        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get single product by ID
     *
     * @param int $productId PrestaShop product ID
     * @return array Product data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getProduct(int $productId): array
    {
        return $this->makeRequest('GET', "/products/{$productId}");
    }

    /**
     * Get only date_upd for multiple products (lightweight API call)
     *
     * OPTIMIZATION: Fetch only id + date_upd for change detection
     * Used by PullProductsFromPrestaShop to skip unchanged products.
     *
     * PrestaShop API params:
     * - display=[id,date_upd] - return only these fields
     * - filter[id]=[1|2|3] - filter by multiple IDs using pipe separator
     *
     * @param array $productIds PrestaShop product IDs
     * @return array [prestashop_id => date_upd, ...] Keyed by product ID
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getProductsDateUpd(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        // PrestaShop API uses pipe (|) as OR separator for filter values
        $idsFilter = implode('|', array_map('intval', $productIds));

        $endpoint = "/products?display=[id,date_upd]&filter[id]=[{$idsFilter}]";

        try {
            $response = $this->makeRequest('GET', $endpoint);

            $result = [];

            // Handle empty response
            if (!isset($response['products']) || empty($response['products'])) {
                Log::debug('[PrestaShop8Client] getProductsDateUpd - no products returned', [
                    'requested_ids' => count($productIds),
                ]);
                return [];
            }

            // Handle both single product (object) and multiple products (array)
            $products = $response['products'];
            if (!isset($products[0]) && isset($products['id'])) {
                // Single product returned as object
                $products = [$products];
            }

            foreach ($products as $product) {
                $id = (int) ($product['id'] ?? 0);
                $dateUpd = $product['date_upd'] ?? null;

                if ($id > 0 && $dateUpd !== null) {
                    $result[$id] = $dateUpd;
                }
            }

            Log::debug('[PrestaShop8Client] getProductsDateUpd completed', [
                'requested' => count($productIds),
                'returned' => count($result),
                'shop_id' => $this->shop->id,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::warning('[PrestaShop8Client] getProductsDateUpd failed', [
                'error' => $e->getMessage(),
                'shop_id' => $this->shop->id,
                'product_ids_count' => count($productIds),
            ]);

            // Return empty array on failure - let the job fall back to full sync
            return [];
        }
    }

    /**
     * Create new product
     *
     * FIX (2025-11-14): Unwrap 'product' key if ProductTransformer returned wrapped structure
     * FIX (2025-11-13): PrestaShop Web Service API requires XML for POST requests
     *
     * @param array $productData Product data in PrestaShop format (raw or wrapped in 'product' key)
     * @return array Created product data with ID
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function createProduct(array $productData): array
    {
        // Unwrap 'product' key if transformer returned wrapped structure
        // ProductTransformer returns: ['product' => [...]] but this method expects raw data
        if (isset($productData['product'])) {
            $productData = $productData['product'];
        }

        $xmlBody = $this->arrayToXml(['product' => $productData]);

        return $this->makeRequest('POST', '/products', [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);
    }

    /**
     * Update existing product
     *
     * CRITICAL FIX (2025-11-14 #3): Implemented GET-MODIFY-PUT pattern to preserve existing fields
     * CRITICAL FIX (2025-11-14 #2): Fixed double wrapping (unwrap 'product' key from ProductTransformer)
     * CRITICAL FIX (2025-11-14 #1): PrestaShop UPDATE requires ID injection + XML format
     *
     * HISTORY:
     * - 2025-11-13: Added XML conversion (was sending JSON)
     * - 2025-11-14 #1: Added ID injection (PrestaShop requirement for UPDATE)
     * - 2025-11-14 #2: Fixed double wrapping (unwrap 'product' key from ProductTransformer)
     * - 2025-11-14 #3: Implemented GET-MODIFY-PUT pattern (PrestaShop Best Practice)
     *
     * PrestaShop API BEST PRACTICE for UPDATE (GET-MODIFY-PUT):
     * 1. GET existing product from PrestaShop
     * 2. MERGE new data with existing data (preserve unchanged fields)
     * 3. PUT merged data
     *
     * This prevents overwriting critical fields like:
     * - id_tax_rules_group (tax rules) ← CRITICAL: User reported tax rules being reset
     * - position_in_category
     * - cache fields
     * - Other fields not managed by PPM
     *
     * PrestaShop API REQUIREMENT for UPDATE operations:
     * - Body MUST be XML (not JSON)
     * - XML structure MUST contain <id> element at the beginning
     * - Without <id>, PrestaShop returns: "id is required when modifying a resource"
     *
     * EXAMPLE XML:
     * <prestashop>
     *   <product>
     *     <id><![CDATA[123]]></id>  ← REQUIRED FOR UPDATE
     *     <name>...</name>
     *     <price>99.99</price>
     *   </product>
     * </prestashop>
     *
     * @param int $productId PrestaShop product ID
     * @param array $productData Updated product data (raw or wrapped in 'product' key)
     * @return array Updated product data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function updateProduct(int $productId, array $productData): array
    {
        // Unwrap 'product' key if transformer returned wrapped structure
        // ProductTransformer returns: ['product' => [...]] but this method expects raw data
        if (isset($productData['product'])) {
            $productData = $productData['product'];
        }

        // TEMPORARY ROLLBACK (2025-11-14): Removed GET-MODIFY-PUT due to shallow merge destroying nested structures
        // TODO: Implement SELECTIVE merge that preserves multilang/associations
        // Issue: array_merge() destroys 'name', 'link_rewrite', 'associations' → products disappear from admin

        // CRITICAL: PrestaShop requires 'id' in product data for UPDATE
        // Inject id at the beginning of product array
        $productData = array_merge(['id' => $productId], $productData);

        // Convert to XML format (PrestaShop Web Service requirement)
        $xmlBody = $this->arrayToXml(['product' => $productData]);

        try {
            return $this->makeRequest('PUT', "/products/{$productId}", [], [
                'body' => $xmlBody,
                'headers' => ['Content-Type' => 'application/xml'],
            ]);
        } catch (PrestaShopAPIException $e) {
            // FIX (2025-12-05): Handle deprecation warnings from PrestaShop modules
            // Some modules (e.g., ultimateimagetool) use deprecated hooks causing 500 errors
            // but the actual update may have succeeded
            $context = $e->getContext();

            if (($context['is_deprecation'] ?? false) || ($context['error_category'] ?? '') === 'deprecation_warning') {
                Log::warning('[PrestaShop API] Deprecation warning during product update - verifying if update succeeded', [
                    'product_id' => $productId,
                    'deprecation_message' => $e->getMessage(),
                ]);

                // Wait a moment for PS to finish processing
                usleep(500000); // 0.5 seconds

                // Verify if product was actually updated by fetching it
                try {
                    $verifiedProduct = $this->getProduct($productId);

                    if (!empty($verifiedProduct['product'])) {
                        Log::info('[PrestaShop API] Product update succeeded despite deprecation warning', [
                            'product_id' => $productId,
                            'reference' => $verifiedProduct['product']['reference'] ?? 'N/A',
                        ]);

                        // Return the verified product data
                        return $verifiedProduct;
                    }
                } catch (\Exception $verifyException) {
                    Log::error('[PrestaShop API] Failed to verify product after deprecation warning', [
                        'product_id' => $productId,
                        'verify_error' => $verifyException->getMessage(),
                    ]);
                }

                // If verification failed, re-throw original exception
                throw $e;
            }

            // Not a deprecation warning - re-throw
            throw $e;
        }
    }

    /**
     * Delete product
     *
     * @param int $productId PrestaShop product ID
     * @return bool True on success
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function deleteProduct(int $productId): bool
    {
        $this->makeRequest('DELETE', "/products/{$productId}");
        return true;
    }

    /**
     * Get all categories
     *
     * @param array $filters Query filters
     * @return array Categories array
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getCategories(array $filters = []): array
    {
        $queryParams = $this->buildQueryParams($filters);
        $endpoint = empty($queryParams) ? '/categories' : "/categories?{$queryParams}";

        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get single category by ID
     *
     * @param int $categoryId PrestaShop category ID
     * @return array Category data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getCategory(int $categoryId): array
    {
        return $this->makeRequest('GET', "/categories/{$categoryId}");
    }

    /**
     * Delete category from PrestaShop
     *
     * FIX 2025-11-27: Physical category deletion from PrestaShop
     * This removes the category from ALL PrestaShop tables:
     * - ps_category (main category data)
     * - ps_category_lang (translations)
     * - ps_category_shop (shop associations)
     * - ps_category_group (group permissions)
     * - ps_category_product (product associations)
     *
     * The nested set model (nleft/nright) is automatically recalculated by PrestaShop API
     *
     * WARNING: This permanently deletes the category! Products in this category
     * will be moved to their default category.
     *
     * @param int $categoryId PrestaShop category ID
     * @return bool True on success
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function deleteCategory(int $categoryId): bool
    {
        \Log::info('[CATEGORY DELETE] Deleting category from PrestaShop via API', [
            'category_id' => $categoryId,
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
        ]);

        $this->makeRequest('DELETE', "/categories/{$categoryId}");

        \Log::info('[CATEGORY DELETE] Category deleted successfully', [
            'category_id' => $categoryId,
            'shop_id' => $this->shop->id,
        ]);

        return true;
    }

    /**
     * Get product stock
     *
     * @param int $productId PrestaShop product ID
     * @return array Stock data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getStock(int $productId): array
    {
        return $this->makeRequest('GET', "/stock_availables?filter[id_product]={$productId}");
    }

    /**
     * Update product stock
     *
     * FIX (2025-11-13): PrestaShop Web Service API requires XML for PUT requests
     *
     * @param int $stockId PrestaShop stock_available ID
     * @param int $quantity New quantity
     * @return array Updated stock data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function updateStock(int $stockId, int $quantity): array
    {
        // CRITICAL: Inject id for UPDATE operation
        $xmlBody = $this->arrayToXml([
            'stock_available' => [
                'id' => $stockId,
                'quantity' => $quantity
            ]
        ]);

        return $this->makeRequest('PUT', "/stock_availables/{$stockId}", [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);
    }

    /**
     * Get specific prices for product
     *
     * PROBLEM #4 - Task 16: PrestaShop Price Import
     * Fetches all specific_prices for a product (discounts, group prices, etc.)
     *
     * @param int $productId PrestaShop product ID
     * @return array Specific prices data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getSpecificPrices(int $productId): array
    {
        return $this->makeRequest('GET', "/specific_prices?filter[id_product]={$productId}&display=full");
    }

    /**
     * Get all price groups (customer groups) from PrestaShop
     *
     * Used for price mapping configuration in shop wizard.
     * Fetches all customer groups which can have specific prices.
     *
     * @return array Price groups data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getPriceGroups(): array
    {
        return $this->makeRequest('GET', '/groups?display=full');
    }

    /**
     * Create specific price
     *
     * PROBLEM #4 - Task 18: PrestaShop Price Sync
     * Creates a new specific_price entry (discount, group price)
     *
     * FIX (2025-11-13): PrestaShop Web Service API requires XML for POST requests
     *
     * @param array $priceData Specific price data
     * @return array Created specific price with ID
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function createSpecificPrice(array $priceData): array
    {
        $xmlBody = $this->arrayToXml(['specific_price' => $priceData]);

        return $this->makeRequest('POST', '/specific_prices', [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);
    }

    /**
     * Update specific price
     *
     * PROBLEM #4 - Task 18: PrestaShop Price Sync
     * Updates existing specific_price entry
     *
     * FIX (2025-11-14): Added ID injection for UPDATE operation
     * FIX (2025-11-13): PrestaShop Web Service API requires XML for PUT requests
     *
     * @param int $priceId PrestaShop specific_price ID
     * @param array $priceData Updated price data
     * @return array Updated specific price
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function updateSpecificPrice(int $priceId, array $priceData): array
    {
        // CRITICAL: Inject id for UPDATE operation
        $priceData = array_merge(['id' => $priceId], $priceData);

        $xmlBody = $this->arrayToXml(['specific_price' => $priceData]);

        return $this->makeRequest('PUT', "/specific_prices/{$priceId}", [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);
    }

    /**
     * Delete specific price
     *
     * PROBLEM #4 - Task 18: PrestaShop Price Sync
     * Deletes specific_price entry
     *
     * @param int $priceId PrestaShop specific_price ID
     * @return bool True on success
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function deleteSpecificPrice(int $priceId): bool
    {
        $this->makeRequest('DELETE', "/specific_prices/{$priceId}");
        return true;
    }

    // ===================================
    // ATTRIBUTE GROUPS API METHODS
    // ===================================

    /**
     * Get all attribute groups
     *
     * @param array $filters Query filters (limit, display, filter, sort)
     * @return array Attribute groups array
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getAttributeGroups(array $filters = []): array
    {
        $queryParams = $this->buildQueryParams($filters);
        $endpoint = empty($queryParams) ? '/product_options' : "/product_options?{$queryParams}";

        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get single attribute group by ID
     *
     * @param int $groupId PrestaShop attribute group ID
     * @return array Attribute group data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getAttributeGroup(int $groupId): array
    {
        return $this->makeRequest('GET', "/product_options/{$groupId}");
    }

    /**
     * Create new attribute group
     *
     * FIX (2025-11-13): PrestaShop Web Service API requires XML for POST requests
     *
     * @param array $groupData Attribute group data in PrestaShop format
     * @return array Created attribute group data with ID
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function createAttributeGroup(array $groupData): array
    {
        $xmlBody = $this->arrayToXml(['product_option' => $groupData]);

        return $this->makeRequest('POST', '/product_options', [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);
    }

    /**
     * Update existing attribute group
     *
     * FIX (2025-11-13): PrestaShop Web Service API requires XML for PUT requests
     *
     * @param int $groupId PrestaShop attribute group ID
     * @param array $groupData Updated attribute group data
     * @return array Updated attribute group data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function updateAttributeGroup(int $groupId, array $groupData): array
    {
        // CRITICAL: Inject id for UPDATE operation
        $groupData = array_merge(['id' => $groupId], $groupData);

        $xmlBody = $this->arrayToXml(['product_option' => $groupData]);

        return $this->makeRequest('PUT', "/product_options/{$groupId}", [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);
    }

    /**
     * Delete attribute group
     *
     * @param int $groupId PrestaShop attribute group ID
     * @return bool True on success
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function deleteAttributeGroup(int $groupId): bool
    {
        $this->makeRequest('DELETE', "/product_options/{$groupId}");
        return true;
    }

    // ===================================
    // ATTRIBUTE VALUES API METHODS
    // ===================================

    /**
     * Get all attribute values
     *
     * @param array $filters Query filters (limit, display, filter, sort)
     * @return array Attribute values array
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getAttributeValues(array $filters = []): array
    {
        $queryParams = $this->buildQueryParams($filters);
        $endpoint = empty($queryParams) ? '/product_option_values' : "/product_option_values?{$queryParams}";

        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get single attribute value by ID
     *
     * @param int $valueId PrestaShop attribute value ID
     * @return array Attribute value data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getAttributeValue(int $valueId): array
    {
        return $this->makeRequest('GET', "/product_option_values/{$valueId}");
    }

    /**
     * Create new attribute value
     *
     * FIX (2025-11-13): PrestaShop Web Service API requires XML for POST requests
     *
     * @param array $valueData Attribute value data in PrestaShop format
     * @return array Created attribute value data with ID
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function createAttributeValue(array $valueData): array
    {
        $xmlBody = $this->arrayToXml(['product_option_value' => $valueData]);

        return $this->makeRequest('POST', '/product_option_values', [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);
    }

    /**
     * Update existing attribute value
     *
     * FIX (2025-11-14): Added ID injection for UPDATE operation
     * FIX (2025-11-13): PrestaShop Web Service API requires XML for PUT requests
     *
     * @param int $valueId PrestaShop attribute value ID
     * @param array $valueData Updated attribute value data
     * @return array Updated attribute value data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function updateAttributeValue(int $valueId, array $valueData): array
    {
        // CRITICAL: Inject id for UPDATE operation
        $valueData = array_merge(['id' => $valueId], $valueData);

        $xmlBody = $this->arrayToXml(['product_option_value' => $valueData]);

        return $this->makeRequest('PUT', "/product_option_values/{$valueId}", [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);
    }

    /**
     * Delete attribute value
     *
     * @param int $valueId PrestaShop attribute value ID
     * @return bool True on success
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function deleteAttributeValue(int $valueId): bool
    {
        $this->makeRequest('DELETE', "/product_option_values/{$valueId}");
        return true;
    }

    // ============================================================
    // ALIASES: PrestaShop product_options / product_option_values
    // Used by PrestaShopImportService for variant import
    // ============================================================

    /**
     * Alias for getAttributeValue - matches PrestaShop API naming convention
     *
     * @param int $valueId PrestaShop product_option_value ID
     * @return array Product option value data
     */
    public function getProductOptionValue(int $valueId): array
    {
        return $this->getAttributeValue($valueId);
    }

    /**
     * Alias for getAttributeGroup - matches PrestaShop API naming convention
     *
     * @param int $groupId PrestaShop product_option (attribute group) ID
     * @return array Product option data
     */
    public function getProductOption(int $groupId): array
    {
        return $this->getAttributeGroup($groupId);
    }

    /**
     * Get products by category ID
     *
     * Fetches products that belong to a specific category using the PrestaShop API filter parameter.
     * Uses filter[id_category_default] to match products by their default category.
     *
     * @param int $categoryId Category ID
     * @param bool $includeSubcategories Include products from subcategories (not implemented in basic PS API)
     * @param int $limit Maximum number of products to fetch (default: 100)
     * @param int $offset Pagination offset (default: 0)
     * @return array Products array
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getProductsByCategory(int $categoryId, bool $includeSubcategories = false, int $limit = 100, int $offset = 0): array
    {
        try {
            \Log::info('PrestaShop8Client: Fetching products by category', [
                'category_id' => $categoryId,
                'include_subcategories' => $includeSubcategories,
                'limit' => $limit,
                'offset' => $offset,
                'shop_url' => $this->shop->url
            ]);

            // Build filters using PrestaShop API filter syntax
            $filters = [
                'filter[id_category_default]' => $categoryId,
                'display' => 'full',
                'limit' => $limit,
            ];

            if ($offset > 0) {
                $filters['limit'] = "$offset,$limit";
            }

            $response = $this->getProducts($filters);

            $products = [];
            if (isset($response['products'])) {
                $products = is_array($response['products']) ? $response['products'] : [$response['products']];
            }

            \Log::info('PrestaShop8Client: Products fetched successfully by category', [
                'category_id' => $categoryId,
                'products_count' => count($products)
            ]);

            return $products;

        } catch (\Exception $e) {
            \Log::error('PrestaShop8Client: Failed to fetch products by category', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
                'shop_url' => $this->shop->url
            ]);

            throw new \App\Exceptions\PrestaShopAPIException(
                "Failed to fetch products from category {$categoryId}: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get tax rule groups from PrestaShop
     *
     * FAZA 5.1 - Tax Rules UI Enhancement System
     * Implementation for PrestaShop 8.x
     *
     * Endpoint: GET /tax_rule_groups?display=full
     * Returns ONLY active tax rule groups
     *
     * @return array Standardized format: [['id' => 6, 'name' => 'PL Standard Rate (23%)', 'rate' => null, 'active' => true], ...]
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getTaxRuleGroups(): array
    {
        try {
            $queryParams = $this->buildQueryParams([
                'display' => 'full',
            ]);

            $response = $this->makeRequest('GET', "/tax_rule_groups?{$queryParams}");

            // PrestaShop API returns: {"tax_rule_groups": [{"id": 1, "name": {...}, "active": "1"}, ...]}
            $taxRuleGroups = [];

            if (!isset($response['tax_rule_groups']) || empty($response['tax_rule_groups'])) {
                \Log::warning('No tax rule groups found in PrestaShop', [
                    'shop_id' => $this->shop->id,
                    'shop_name' => $this->shop->name,
                ]);
                return [];
            }

            // Handle both single group (object) and multiple groups (array)
            $groups = is_array($response['tax_rule_groups']) ? $response['tax_rule_groups'] : [$response['tax_rule_groups']];

            foreach ($groups as $groupData) {
                $group = is_array($groupData) ? $groupData : (array) $groupData;

                // Filter: ONLY active groups
                if (($group['active'] ?? '0') !== '1') {
                    continue;
                }

                $groupId = (int) ($group['id'] ?? 0);

                // Extract name (handle multilang format: ['language' => [['id' => 1, 'value' => 'Text']]])
                $groupName = '';
                if (is_array($group['name'] ?? null)) {
                    // Multilang format
                    if (isset($group['name']['language'])) {
                        $languages = is_array($group['name']['language']) ? $group['name']['language'] : [$group['name']['language']];
                        $firstLang = is_array($languages) ? reset($languages) : $languages;
                        $groupName = is_array($firstLang) ? ($firstLang['value'] ?? '') : (string) $firstLang;
                    }
                } else {
                    // Simple string
                    $groupName = (string) ($group['name'] ?? '');
                }

                // Rate extraction from name (basic implementation)
                // Examples: "PL Standard Rate (23%)", "Reduced Rate (8%)", "Exempt (0%)"
                $rate = null;
                if (preg_match('/\((\d+(?:\.\d+)?)%\)/', $groupName, $matches)) {
                    $rate = (float) $matches[1];
                }

                $taxRuleGroups[] = [
                    'id' => $groupId,
                    'name' => $groupName,
                    'rate' => $rate,
                    'active' => true, // Already filtered for active only
                ];
            }

            \Log::info('Tax rule groups fetched successfully from PrestaShop', [
                'shop_id' => $this->shop->id,
                'shop_name' => $this->shop->name,
                'count' => count($taxRuleGroups),
            ]);

            return $taxRuleGroups;

        } catch (\App\Exceptions\PrestaShopAPIException $e) {
            \Log::error('Failed to fetch tax rule groups from PrestaShop', [
                'shop_id' => $this->shop->id,
                'shop_name' => $this->shop->name,
                'http_status' => $e->getHttpStatusCode(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // ===================================
    // MANUFACTURERS API METHODS
    // ===================================

    /**
     * Get all manufacturers from PrestaShop
     *
     * @param array $filters Query filters
     * @return array Manufacturers array
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getManufacturers(array $filters = []): array
    {
        $defaultFilters = ['display' => 'full'];
        $filters = array_merge($defaultFilters, $filters);

        $queryParams = $this->buildQueryParams($filters);
        $endpoint = empty($queryParams) ? '/manufacturers' : "/manufacturers?{$queryParams}";

        $response = $this->makeRequest('GET', $endpoint);

        // Handle PrestaShop response format
        if (isset($response['manufacturers']) && is_array($response['manufacturers'])) {
            return $response['manufacturers'];
        }

        return [];
    }

    /**
     * Get single manufacturer by ID with full details
     *
     * ETAP 07g: Manufacturer Sync System
     * Returns: name, description, short_description, meta_*, active, link_rewrite
     *
     * @param int $manufacturerId PrestaShop manufacturer ID
     * @return array|null Manufacturer data or null if not found
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getManufacturer(int $manufacturerId): ?array
    {
        try {
            $response = $this->makeRequest('GET', "/manufacturers/{$manufacturerId}");

            if (isset($response['manufacturer'])) {
                return $response['manufacturer'];
            }

            return $response;
        } catch (\App\Exceptions\PrestaShopAPIException $e) {
            if ($e->getHttpStatusCode() === 404) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Create new manufacturer in PrestaShop
     *
     * ETAP 07g: Manufacturer Sync System
     * Creates manufacturer with multilang support for description fields
     *
     * Required fields:
     * - name (string, NOT multilang!)
     * - active (0|1)
     *
     * Multilang fields (optional):
     * - description, short_description, meta_title, meta_description, meta_keywords
     *
     * @param array $manufacturerData Manufacturer data in PrestaShop format
     * @return array Created manufacturer with ID
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function createManufacturer(array $manufacturerData): array
    {
        // Ensure required fields
        if (!isset($manufacturerData['active'])) {
            $manufacturerData['active'] = 1;
        }

        $xmlBody = $this->arrayToXml(['manufacturer' => $manufacturerData]);

        Log::info('[MANUFACTURER API] Creating manufacturer', [
            'shop_id' => $this->shop->id,
            'name' => $manufacturerData['name'] ?? 'N/A',
        ]);

        $response = $this->makeRequest('POST', '/manufacturers', [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);

        Log::info('[MANUFACTURER API] Manufacturer created', [
            'shop_id' => $this->shop->id,
            'manufacturer_id' => $response['manufacturer']['id'] ?? null,
            'name' => $manufacturerData['name'] ?? 'N/A',
        ]);

        return $response;
    }

    /**
     * Update existing manufacturer in PrestaShop
     *
     * ETAP 07g: Manufacturer Sync System
     * Uses GET-MODIFY-PUT pattern for safe updates
     *
     * @param int $manufacturerId PrestaShop manufacturer ID
     * @param array $manufacturerData Updated manufacturer data
     * @return array Updated manufacturer data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function updateManufacturer(int $manufacturerId, array $manufacturerData): array
    {
        // CRITICAL: Inject id for UPDATE operation
        $manufacturerData = array_merge(['id' => $manufacturerId], $manufacturerData);

        $xmlBody = $this->arrayToXml(['manufacturer' => $manufacturerData]);

        Log::info('[MANUFACTURER API] Updating manufacturer', [
            'shop_id' => $this->shop->id,
            'manufacturer_id' => $manufacturerId,
        ]);

        $response = $this->makeRequest('PUT', "/manufacturers/{$manufacturerId}", [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);

        Log::info('[MANUFACTURER API] Manufacturer updated', [
            'shop_id' => $this->shop->id,
            'manufacturer_id' => $manufacturerId,
        ]);

        return $response;
    }

    /**
     * Delete manufacturer from PrestaShop
     *
     * ETAP 07g: Manufacturer Sync System
     * WARNING: This permanently removes the manufacturer from PrestaShop!
     *
     * @param int $manufacturerId PrestaShop manufacturer ID
     * @return bool True on success
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function deleteManufacturer(int $manufacturerId): bool
    {
        Log::info('[MANUFACTURER API] Deleting manufacturer', [
            'shop_id' => $this->shop->id,
            'manufacturer_id' => $manufacturerId,
        ]);

        $this->makeRequest('DELETE', "/manufacturers/{$manufacturerId}");

        Log::info('[MANUFACTURER API] Manufacturer deleted', [
            'shop_id' => $this->shop->id,
            'manufacturer_id' => $manufacturerId,
        ]);

        return true;
    }

    /**
     * Upload logo image for manufacturer
     *
     * ETAP 07g: Manufacturer Sync System
     * PrestaShop endpoint: POST /api/images/manufacturers/{id}
     * NOTE: Requires multipart/form-data (NOT XML!)
     *
     * @param int $manufacturerId PrestaShop manufacturer ID
     * @param string $imagePath Local file path to logo image
     * @param string $filename Original filename (default: logo.jpg)
     * @return bool True on success
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function uploadManufacturerImage(int $manufacturerId, string $imagePath, string $filename = 'logo.jpg'): bool
    {
        try {
            $url = $this->buildUrl("/images/manufacturers/{$manufacturerId}");

            Log::info('[MANUFACTURER API] Uploading logo', [
                'shop_id' => $this->shop->id,
                'manufacturer_id' => $manufacturerId,
                'file_path' => $imagePath,
                'file_size' => file_exists($imagePath) ? filesize($imagePath) : 0,
            ]);

            // PrestaShop Image API requires multipart/form-data
            $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->shop->api_key, '')
                ->timeout($this->timeout)
                ->attach('image', file_get_contents($imagePath), $filename)
                ->post($url);

            if (!$response->successful()) {
                Log::error('[MANUFACTURER API] Logo upload failed', [
                    'manufacturer_id' => $manufacturerId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \App\Exceptions\PrestaShopAPIException(
                    'Manufacturer logo upload failed: ' . $response->body(),
                    $response->status()
                );
            }

            Log::info('[MANUFACTURER API] Logo uploaded successfully', [
                'shop_id' => $this->shop->id,
                'manufacturer_id' => $manufacturerId,
            ]);

            return true;

        } catch (\App\Exceptions\PrestaShopAPIException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('[MANUFACTURER API] Logo upload exception', [
                'manufacturer_id' => $manufacturerId,
                'error' => $e->getMessage(),
            ]);

            throw new \App\Exceptions\PrestaShopAPIException(
                'Manufacturer logo upload exception: ' . $e->getMessage(),
                500,
                $e
            );
        }
    }

    /**
     * Download manufacturer logo from PrestaShop
     *
     * ETAP 07g: Manufacturer Sync System
     * PrestaShop endpoint: GET /api/images/manufacturers/{id}
     *
     * @param int $manufacturerId PrestaShop manufacturer ID
     * @return string|null Binary image data or null if not found
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function downloadManufacturerImage(int $manufacturerId): ?string
    {
        try {
            $url = $this->buildUrl("/images/manufacturers/{$manufacturerId}");

            Log::debug('[MANUFACTURER API] Downloading logo', [
                'shop_id' => $this->shop->id,
                'manufacturer_id' => $manufacturerId,
            ]);

            $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->shop->api_key, '')
                ->timeout($this->timeout)
                ->get($url);

            // 404 = no logo
            if ($response->status() === 404) {
                Log::debug('[MANUFACTURER API] No logo found', [
                    'manufacturer_id' => $manufacturerId,
                ]);
                return null;
            }

            if (!$response->successful()) {
                Log::error('[MANUFACTURER API] Logo download failed', [
                    'manufacturer_id' => $manufacturerId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $imageData = $response->body();

            Log::info('[MANUFACTURER API] Logo downloaded', [
                'shop_id' => $this->shop->id,
                'manufacturer_id' => $manufacturerId,
                'size' => strlen($imageData),
            ]);

            return $imageData;

        } catch (\Exception $e) {
            Log::error('[MANUFACTURER API] Logo download exception', [
                'manufacturer_id' => $manufacturerId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get manufacturer logo URL
     *
     * ETAP 07g: Manufacturer Sync System
     * Returns authenticated URL for manufacturer logo
     *
     * @param int $manufacturerId PrestaShop manufacturer ID
     * @return string Logo URL with API key authentication
     */
    public function getManufacturerImageUrl(int $manufacturerId): string
    {
        $baseUrl = rtrim($this->shop->url, '/');
        $apiPath = $this->getApiBasePath();

        return $baseUrl . $apiPath . "/images/manufacturers/{$manufacturerId}?ws_key=" . urlencode($this->shop->api_key);
    }

    /**
     * Check if manufacturer has logo in PrestaShop
     *
     * ETAP 07g: Manufacturer Sync System
     * Uses HEAD request to check logo existence without downloading
     *
     * @param int $manufacturerId PrestaShop manufacturer ID
     * @return bool True if logo exists
     */
    public function hasManufacturerImage(int $manufacturerId): bool
    {
        try {
            $url = $this->buildUrl("/images/manufacturers/{$manufacturerId}");

            $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->shop->api_key, '')
                ->timeout($this->timeout)
                ->head($url);

            return $response->successful();

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete manufacturer logo from PrestaShop
     *
     * ETAP 07g: Manufacturer Sync System
     *
     * @param int $manufacturerId PrestaShop manufacturer ID
     * @return bool True on success
     */
    public function deleteManufacturerImage(int $manufacturerId): bool
    {
        try {
            $this->makeRequest('DELETE', "/images/manufacturers/{$manufacturerId}");

            Log::info('[MANUFACTURER API] Logo deleted', [
                'shop_id' => $this->shop->id,
                'manufacturer_id' => $manufacturerId,
            ]);

            return true;

        } catch (\App\Exceptions\PrestaShopAPIException $e) {
            if ($e->getHttpStatusCode() === 404) {
                return true; // Already deleted
            }

            Log::error('[MANUFACTURER API] Logo delete failed', [
                'manufacturer_id' => $manufacturerId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    // ===================================
    // SUPPLIERS API METHODS (for Importer sync)
    // ETAP_08: Importer → PS Supplier Sync
    // ===================================

    /**
     * Get all suppliers from PrestaShop
     *
     * @param array $filters Query filters
     * @return array Suppliers array
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getSuppliers(array $filters = []): array
    {
        $defaultFilters = ['display' => 'full'];
        $filters = array_merge($defaultFilters, $filters);

        $queryParams = $this->buildQueryParams($filters);
        $endpoint = empty($queryParams) ? '/suppliers' : "/suppliers?{$queryParams}";

        $response = $this->makeRequest('GET', $endpoint);

        if (isset($response['suppliers']) && is_array($response['suppliers'])) {
            return $response['suppliers'];
        }

        return [];
    }

    /**
     * Get single supplier by ID
     *
     * @param int $supplierId PrestaShop supplier ID
     * @return array|null Supplier data or null if not found
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getSupplier(int $supplierId): ?array
    {
        try {
            $response = $this->makeRequest('GET', "/suppliers/{$supplierId}");

            if (isset($response['supplier'])) {
                return $response['supplier'];
            }

            return $response;
        } catch (\App\Exceptions\PrestaShopAPIException $e) {
            if ($e->getHttpStatusCode() === 404) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Create new supplier in PrestaShop
     *
     * ETAP_08: Importer Sync System
     * Supplier fields are IDENTICAL to manufacturer fields:
     * - name (string, NOT multilang)
     * - active (0|1)
     * - description, short_description, meta_title, meta_description, meta_keywords (multilang)
     * - link_rewrite (multilang)
     *
     * @param array $supplierData Supplier data in PrestaShop format
     * @return array Created supplier with ID
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function createSupplier(array $supplierData): array
    {
        if (!isset($supplierData['active'])) {
            $supplierData['active'] = 1;
        }

        $xmlBody = $this->arrayToXml(['supplier' => $supplierData]);

        Log::info('[SUPPLIER API] Creating supplier', [
            'shop_id' => $this->shop->id,
            'name' => $supplierData['name'] ?? 'N/A',
        ]);

        $response = $this->makeRequest('POST', '/suppliers', [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);

        Log::info('[SUPPLIER API] Supplier created', [
            'shop_id' => $this->shop->id,
            'supplier_id' => $response['supplier']['id'] ?? null,
            'name' => $supplierData['name'] ?? 'N/A',
        ]);

        return $response;
    }

    /**
     * Update existing supplier in PrestaShop
     *
     * @param int $supplierId PrestaShop supplier ID
     * @param array $supplierData Updated supplier data
     * @return array Updated supplier data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function updateSupplier(int $supplierId, array $supplierData): array
    {
        // CRITICAL: Inject id for UPDATE operation
        $supplierData = array_merge(['id' => $supplierId], $supplierData);

        $xmlBody = $this->arrayToXml(['supplier' => $supplierData]);

        Log::info('[SUPPLIER API] Updating supplier', [
            'shop_id' => $this->shop->id,
            'supplier_id' => $supplierId,
        ]);

        $response = $this->makeRequest('PUT', "/suppliers/{$supplierId}", [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);

        Log::info('[SUPPLIER API] Supplier updated', [
            'shop_id' => $this->shop->id,
            'supplier_id' => $supplierId,
        ]);

        return $response;
    }

    /**
     * Delete supplier from PrestaShop
     *
     * WARNING: This permanently removes the supplier from PrestaShop!
     *
     * @param int $supplierId PrestaShop supplier ID
     * @return bool True on success
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function deleteSupplier(int $supplierId): bool
    {
        Log::info('[SUPPLIER API] Deleting supplier', [
            'shop_id' => $this->shop->id,
            'supplier_id' => $supplierId,
        ]);

        $this->makeRequest('DELETE', "/suppliers/{$supplierId}");

        Log::info('[SUPPLIER API] Supplier deleted', [
            'shop_id' => $this->shop->id,
            'supplier_id' => $supplierId,
        ]);

        return true;
    }

    /**
     * Upload logo image for supplier
     *
     * PrestaShop endpoint: POST /api/images/suppliers/{id}
     * NOTE: Requires multipart/form-data (NOT XML!)
     *
     * @param int $supplierId PrestaShop supplier ID
     * @param string $imagePath Local file path to logo image
     * @param string $filename Original filename (default: logo.jpg)
     * @return bool True on success
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function uploadSupplierImage(int $supplierId, string $imagePath, string $filename = 'logo.jpg'): bool
    {
        try {
            $url = $this->buildUrl("/images/suppliers/{$supplierId}");

            Log::info('[SUPPLIER API] Uploading logo', [
                'shop_id' => $this->shop->id,
                'supplier_id' => $supplierId,
                'file_path' => $imagePath,
                'file_size' => file_exists($imagePath) ? filesize($imagePath) : 0,
            ]);

            $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->shop->api_key, '')
                ->timeout($this->timeout)
                ->attach('image', file_get_contents($imagePath), $filename)
                ->post($url);

            if (!$response->successful()) {
                Log::error('[SUPPLIER API] Logo upload failed', [
                    'supplier_id' => $supplierId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \App\Exceptions\PrestaShopAPIException(
                    'Supplier logo upload failed: ' . $response->body(),
                    $response->status()
                );
            }

            Log::info('[SUPPLIER API] Logo uploaded successfully', [
                'shop_id' => $this->shop->id,
                'supplier_id' => $supplierId,
            ]);

            return true;

        } catch (\App\Exceptions\PrestaShopAPIException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('[SUPPLIER API] Logo upload exception', [
                'supplier_id' => $supplierId,
                'error' => $e->getMessage(),
            ]);

            throw new \App\Exceptions\PrestaShopAPIException(
                'Supplier logo upload exception: ' . $e->getMessage(),
                500,
                $e
            );
        }
    }

    /**
     * Download supplier logo from PrestaShop
     *
     * PrestaShop endpoint: GET /api/images/suppliers/{id}
     *
     * @param int $supplierId PrestaShop supplier ID
     * @return string|null Binary image data or null if not found
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function downloadSupplierImage(int $supplierId): ?string
    {
        try {
            $url = $this->buildUrl("/images/suppliers/{$supplierId}");

            Log::debug('[SUPPLIER API] Downloading logo', [
                'shop_id' => $this->shop->id,
                'supplier_id' => $supplierId,
            ]);

            $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->shop->api_key, '')
                ->timeout($this->timeout)
                ->get($url);

            if ($response->status() === 404) {
                Log::debug('[SUPPLIER API] No logo found', [
                    'supplier_id' => $supplierId,
                ]);
                return null;
            }

            if (!$response->successful()) {
                Log::error('[SUPPLIER API] Logo download failed', [
                    'supplier_id' => $supplierId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $imageData = $response->body();

            Log::info('[SUPPLIER API] Logo downloaded', [
                'shop_id' => $this->shop->id,
                'supplier_id' => $supplierId,
                'size' => strlen($imageData),
            ]);

            return $imageData;

        } catch (\Exception $e) {
            Log::error('[SUPPLIER API] Logo download exception', [
                'supplier_id' => $supplierId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if supplier has logo in PrestaShop
     *
     * @param int $supplierId PrestaShop supplier ID
     * @return bool True if logo exists
     */
    public function hasSupplierImage(int $supplierId): bool
    {
        try {
            $url = $this->buildUrl("/images/suppliers/{$supplierId}");

            $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->shop->api_key, '')
                ->timeout($this->timeout)
                ->head($url);

            return $response->successful();

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete supplier logo from PrestaShop
     *
     * @param int $supplierId PrestaShop supplier ID
     * @return bool True on success
     */
    public function deleteSupplierImage(int $supplierId): bool
    {
        try {
            $this->makeRequest('DELETE', "/images/suppliers/{$supplierId}");

            Log::info('[SUPPLIER API] Logo deleted', [
                'shop_id' => $this->shop->id,
                'supplier_id' => $supplierId,
            ]);

            return true;

        } catch (\App\Exceptions\PrestaShopAPIException $e) {
            if ($e->getHttpStatusCode() === 404) {
                return true; // Already deleted
            }

            Log::error('[SUPPLIER API] Logo delete failed', [
                'supplier_id' => $supplierId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    // ===================================
    // PRODUCT IMAGES API METHODS
    // ETAP_07d: Media Sync System
    // ===================================

    /**
     * Get all images for a product
     *
     * @param int $productId PrestaShop product ID
     * @return array Images data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getProductImages(int $productId): array
    {
        try {
            // WORKAROUND: PrestaShop has a bug with JSON output for images endpoint
            // Error: "Undefined array key objectsNodeName" in WebserviceOutputJSON.php
            // Solution: Use XML format directly instead of JSON
            $url = $this->buildUrl("/images/products/{$productId}");

            $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->shop->api_key, '')
                ->timeout($this->timeout)
                ->get($url);

            // 404 = no images
            if ($response->status() === 404) {
                return [];
            }

            // Parse XML response
            $body = $response->body();
            $images = [];

            // Try XML parsing (PrestaShop default for images)
            $xml = @simplexml_load_string($body);
            if ($xml && isset($xml->image)) {
                // Single image or multiple images
                foreach ($xml->image as $image) {
                    $declination = $image->declination ?? null;
                    if ($declination) {
                        foreach ($declination as $decl) {
                            $attrs = $decl->attributes('xlink', true);
                            $href = (string) ($attrs['href'] ?? '');
                            // Extract image ID from URL like /api/images/products/9755/30621
                            if (preg_match('/\/(\d+)$/', $href, $matches)) {
                                $images[] = ['id' => (int) $matches[1]];
                            }
                        }
                    }
                }
            }

            \Log::debug('[IMAGE API] Fetched product images (XML)', [
                'product_id' => $productId,
                'shop_id' => $this->shop->id,
                'image_count' => count($images),
            ]);

            return $images;

        } catch (\Exception $e) {
            \Log::error('[IMAGE API] getProductImages exception', [
                'product_id' => $productId,
                'shop_id' => $this->shop->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Download single image from PrestaShop
     *
     * @param int $productId PrestaShop product ID
     * @param int $imageId PrestaShop image ID
     * @return string|null Binary image data or null
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function downloadProductImage(int $productId, int $imageId): ?string
    {
        try {
            $url = $this->buildUrl("/images/products/{$productId}/{$imageId}");

            $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->shop->api_key, '')
                ->timeout($this->timeout)
                ->get($url);

            if (!$response->successful()) {
                \Log::error('[IMAGE API] Failed to download image', [
                    'product_id' => $productId,
                    'image_id' => $imageId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            \Log::debug('[IMAGE API] Image downloaded', [
                'product_id' => $productId,
                'image_id' => $imageId,
                'size' => strlen($response->body()),
            ]);

            return $response->body();

        } catch (\Exception $e) {
            \Log::error('[IMAGE API] Download exception', [
                'product_id' => $productId,
                'image_id' => $imageId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Upload image to PrestaShop product
     *
     * NOTE: PrestaShop Image API requires multipart/form-data (NOT XML!)
     *
     * @param int $productId PrestaShop product ID
     * @param string $imagePath Local file path
     * @param string $filename Original filename
     * @return array|null Response with image ID or null on failure
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function uploadProductImage(int $productId, string $imagePath, string $filename = 'image.jpg'): ?array
    {
        try {
            $url = $this->buildUrl("/images/products/{$productId}");

            // PrestaShop Image API requires multipart/form-data
            $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->shop->api_key, '')
                ->timeout($this->timeout)
                ->attach('image', file_get_contents($imagePath), $filename)
                ->post($url);

            if (!$response->successful()) {
                \Log::error('[IMAGE API] Failed to upload image', [
                    'product_id' => $productId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \App\Exceptions\PrestaShopAPIException(
                    'Image upload failed: ' . $response->body(),
                    $response->status()
                );
            }

            // Parse response to get image ID
            $body = $response->body();
            $result = [];

            // Try JSON first
            $json = json_decode($body, true);
            if ($json && isset($json['image']['id'])) {
                $result = ['id' => (int) $json['image']['id']];
            } else {
                // Try XML
                $xml = @simplexml_load_string($body);
                if ($xml && isset($xml->image->id)) {
                    $result = ['id' => (int) $xml->image->id];
                }
            }

            \Log::info('[IMAGE API] Image uploaded successfully', [
                'product_id' => $productId,
                'image_id' => $result['id'] ?? null,
                'shop_id' => $this->shop->id,
            ]);

            return $result;

        } catch (\App\Exceptions\PrestaShopAPIException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('[IMAGE API] Upload exception', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            throw new \App\Exceptions\PrestaShopAPIException(
                'Image upload exception: ' . $e->getMessage(),
                500,
                $e
            );
        }
    }

    /**
     * Delete image from PrestaShop product
     *
     * @param int $productId PrestaShop product ID
     * @param int $imageId PrestaShop image ID
     * @return bool Success
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function deleteProductImage(int $productId, int $imageId): bool
    {
        try {
            $this->makeRequest('DELETE', "/images/products/{$productId}/{$imageId}");

            \Log::info('[IMAGE API] Image deleted', [
                'product_id' => $productId,
                'image_id' => $imageId,
                'shop_id' => $this->shop->id,
            ]);

            return true;

        } catch (\App\Exceptions\PrestaShopAPIException $e) {
            if ($e->getHttpStatusCode() === 404) {
                return true; // Already deleted
            }
            throw $e;
        }
    }

    /**
     * Delete ALL images from PrestaShop product
     *
     * ETAP_07d: Media Sync "Replace All" strategy
     * Deletes all existing images before uploading new ones
     *
     * @param int $productId PrestaShop product ID
     * @return int Number of images deleted
     */
    public function deleteAllProductImages(int $productId): int
    {
        try {
            $images = $this->getProductImages($productId);

            if (empty($images)) {
                \Log::debug('[IMAGE API] No images to delete', [
                    'product_id' => $productId,
                    'shop_id' => $this->shop->id,
                ]);
                return 0;
            }

            $deleted = 0;
            foreach ($images as $image) {
                $imageId = is_array($image) ? ($image['id'] ?? null) : (int) $image;

                if ($imageId) {
                    try {
                        $this->deleteProductImage($productId, $imageId);
                        $deleted++;
                    } catch (\Exception $e) {
                        \Log::warning('[IMAGE API] Failed to delete single image', [
                            'product_id' => $productId,
                            'image_id' => $imageId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            \Log::info('[IMAGE API] Deleted all product images', [
                'product_id' => $productId,
                'shop_id' => $this->shop->id,
                'deleted_count' => $deleted,
                'total_images' => count($images),
            ]);

            return $deleted;

        } catch (\Exception $e) {
            \Log::error('[IMAGE API] deleteAllProductImages failed', [
                'product_id' => $productId,
                'shop_id' => $this->shop->id,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Set specific image as product cover (default image)
     *
     * ETAP_07d: Media Sync - sets correct cover based on PPM is_primary
     *
     * FIX (2025-12-02): Use PATCH instead of PUT
     * PUT requires ALL product fields (including price), PATCH allows partial update
     *
     * @param int $productId PrestaShop product ID
     * @param int $imageId PrestaShop image ID to set as cover
     * @return bool Success
     */
    public function setProductImageCover(int $productId, int $imageId): bool
    {
        try {
            // PATCH allows partial update - only id_default_image field
            $productData = [
                'id' => $productId,
                'id_default_image' => $imageId,
            ];

            $xmlBody = $this->arrayToXml(['product' => $productData]);

            \Log::debug('[IMAGE API] Setting cover image via PATCH', [
                'product_id' => $productId,
                'image_id' => $imageId,
                'shop_id' => $this->shop->id,
                'xml' => $xmlBody,
            ]);

            // Use PATCH for partial update (not PUT which requires ALL fields)
            $this->makeRequest('PATCH', "/products/{$productId}", [], [
                'body' => $xmlBody,
                'headers' => ['Content-Type' => 'application/xml'],
            ]);

            \Log::info('[IMAGE API] Set product cover image', [
                'product_id' => $productId,
                'image_id' => $imageId,
                'shop_id' => $this->shop->id,
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('[IMAGE API] Failed to set cover image', [
                'product_id' => $productId,
                'image_id' => $imageId,
                'shop_id' => $this->shop->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get cover image ID for a product
     *
     * ETAP_07d: Import Modal - determines which image is marked as cover/primary
     *
     * @param int $productId PrestaShop product ID
     * @return int|null Cover image ID or null if not found
     */
    public function getProductCoverImageId(int $productId): ?int
    {
        try {
            // Get product data with associations to find cover image
            $product = $this->getProduct($productId);

            // PrestaShop stores cover image in associations -> images -> cover = 1
            if (isset($product['product']['associations']['images']['image'])) {
                $images = $product['product']['associations']['images']['image'];

                // Handle single image vs multiple images
                if (!isset($images[0])) {
                    $images = [$images];
                }

                foreach ($images as $image) {
                    // First image is usually the cover, but check for 'cover' attribute
                    if (isset($image['id'])) {
                        return (int) $image['id'];
                    }
                }
            }

            // Fallback: get first image from images endpoint
            $images = $this->getProductImages($productId);
            if (!empty($images)) {
                $firstImage = reset($images);
                return is_array($firstImage) ? ($firstImage['id'] ?? null) : (int) $firstImage;
            }

            return null;

        } catch (\Exception $e) {
            \Log::warning('[IMAGE API] Failed to get cover image ID', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get direct URL to product image
     *
     * ETAP_07d: Import Modal - builds URL for displaying image in modal
     *
     * PrestaShop Image URL format:
     * - With API key: {shop_url}/api/images/products/{product_id}/{image_id}?ws_key={key}
     * - Public (if enabled): {shop_url}/p/{id1}/{id2}/.../{image_id}.jpg
     *
     * @param int $productId PrestaShop product ID
     * @param int $imageId PrestaShop image ID
     * @param string $size Image size (default, large_default, home_default, etc.)
     * @return string Image URL with authentication
     */
    public function getProductImageUrl(int $productId, int $imageId, string $size = 'large_default'): string
    {
        $baseUrl = rtrim($this->shop->url, '/');
        $apiPath = $this->getApiBasePath();

        // Return authenticated API URL for image
        return $baseUrl . $apiPath . "/images/products/{$productId}/{$imageId}?ws_key=" . urlencode($this->shop->api_key);
    }

    /**
     * Build full URL for API endpoint
     *
     * @param string $endpoint API endpoint
     * @return string Full URL
     */
    protected function buildUrl(string $endpoint): string
    {
        $baseUrl = rtrim($this->shop->url, '/');
        $apiPath = $this->getApiBasePath();

        return $baseUrl . $apiPath . $endpoint;
    }

    // ===================================
    // PRODUCT FEATURES API METHODS
    // ETAP_07e: Vehicle Features System
    // ===================================

    /**
     * Get all product features
     *
     * PrestaShop endpoint: GET /api/product_features
     * Returns: ps_feature entries (feature types like "Color", "Size", "Engine Power")
     *
     * @param array $filters Query filters (limit, display, filter, sort)
     * @return array Product features array
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getProductFeatures(array $filters = []): array
    {
        $queryParams = $this->buildQueryParams($filters);
        $endpoint = empty($queryParams) ? '/product_features' : "/product_features?{$queryParams}";

        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get single product feature by ID
     *
     * @param int $featureId PrestaShop feature ID (ps_feature.id_feature)
     * @return array Feature data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getProductFeature(int $featureId): array
    {
        return $this->makeRequest('GET', "/product_features/{$featureId}");
    }

    /**
     * Create new product feature (feature type)
     *
     * PrestaShop endpoint: POST /api/product_features
     * NOTE: 'name' field MUST be multilang format!
     *
     * Example $featureData:
     * [
     *     'name' => [
     *         ['id' => 1, 'value' => 'Moc silnika'],   // Polish
     *         ['id' => 2, 'value' => 'Engine Power'], // English
     *     ]
     * ]
     *
     * @param array $featureData Feature data with multilang name
     * @return array Created feature with ID
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function createProductFeature(array $featureData): array
    {
        $xmlBody = $this->arrayToXml(['product_feature' => $featureData]);

        \Log::debug('[FEATURE API] Creating product feature', [
            'shop_id' => $this->shop->id,
            'feature_data' => $featureData,
        ]);

        $response = $this->makeRequest('POST', '/product_features', [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);

        \Log::info('[FEATURE API] Product feature created', [
            'shop_id' => $this->shop->id,
            'feature_id' => $response['product_feature']['id'] ?? null,
        ]);

        return $response;
    }

    /**
     * Update existing product feature
     *
     * @param int $featureId PrestaShop feature ID
     * @param array $featureData Updated feature data (multilang name)
     * @return array Updated feature data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function updateProductFeature(int $featureId, array $featureData): array
    {
        // CRITICAL: Inject id for UPDATE operation
        $featureData = array_merge(['id' => $featureId], $featureData);

        $xmlBody = $this->arrayToXml(['product_feature' => $featureData]);

        return $this->makeRequest('PUT', "/product_features/{$featureId}", [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);
    }

    /**
     * Delete product feature
     *
     * WARNING: This will delete all associated feature values too!
     *
     * @param int $featureId PrestaShop feature ID
     * @return bool True on success
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function deleteProductFeature(int $featureId): bool
    {
        $this->makeRequest('DELETE', "/product_features/{$featureId}");

        \Log::info('[FEATURE API] Product feature deleted', [
            'shop_id' => $this->shop->id,
            'feature_id' => $featureId,
        ]);

        return true;
    }

    // ===================================
    // PRODUCT FEATURE VALUES API METHODS
    // ETAP_07e: Vehicle Features System
    // ===================================

    /**
     * Get all product feature values
     *
     * PrestaShop endpoint: GET /api/product_feature_values
     * Returns: ps_feature_value entries (actual values like "1500W", "Red", "XL")
     *
     * Common filters:
     * - filter[id_feature] => 15 (get values for specific feature)
     * - display => 'full' (get all fields including multilang)
     *
     * @param array $filters Query filters
     * @return array Feature values array
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getProductFeatureValues(array $filters = []): array
    {
        $queryParams = $this->buildQueryParams($filters);
        $endpoint = empty($queryParams) ? '/product_feature_values' : "/product_feature_values?{$queryParams}";

        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get single product feature value by ID
     *
     * @param int $valueId PrestaShop feature value ID (ps_feature_value.id_feature_value)
     * @return array Feature value data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getProductFeatureValue(int $valueId): array
    {
        return $this->makeRequest('GET', "/product_feature_values/{$valueId}");
    }

    /**
     * Create new product feature value
     *
     * PrestaShop endpoint: POST /api/product_feature_values
     * NOTE: 'value' field MUST be multilang format!
     *
     * Example $valueData:
     * [
     *     'id_feature' => 15,  // Parent feature ID
     *     'value' => [
     *         ['id' => 1, 'value' => '1500W'],
     *         ['id' => 2, 'value' => '1500W'],
     *     ]
     * ]
     *
     * @param array $valueData Value data with id_feature and multilang value
     * @return array Created value with ID
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function createProductFeatureValue(array $valueData): array
    {
        $xmlBody = $this->arrayToXml(['product_feature_value' => $valueData]);

        \Log::debug('[FEATURE API] Creating product feature value', [
            'shop_id' => $this->shop->id,
            'feature_id' => $valueData['id_feature'] ?? null,
            'value' => $valueData['value'] ?? null,
        ]);

        $response = $this->makeRequest('POST', '/product_feature_values', [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);

        \Log::info('[FEATURE API] Product feature value created', [
            'shop_id' => $this->shop->id,
            'value_id' => $response['product_feature_value']['id'] ?? null,
            'feature_id' => $valueData['id_feature'] ?? null,
        ]);

        return $response;
    }

    /**
     * Update existing product feature value
     *
     * @param int $valueId PrestaShop feature value ID
     * @param array $valueData Updated value data (multilang value)
     * @return array Updated value data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function updateProductFeatureValue(int $valueId, array $valueData): array
    {
        // CRITICAL: Inject id for UPDATE operation
        $valueData = array_merge(['id' => $valueId], $valueData);

        $xmlBody = $this->arrayToXml(['product_feature_value' => $valueData]);

        return $this->makeRequest('PUT', "/product_feature_values/{$valueId}", [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);
    }

    /**
     * Delete product feature value
     *
     * @param int $valueId PrestaShop feature value ID
     * @return bool True on success
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function deleteProductFeatureValue(int $valueId): bool
    {
        $this->makeRequest('DELETE', "/product_feature_values/{$valueId}");

        \Log::info('[FEATURE API] Product feature value deleted', [
            'shop_id' => $this->shop->id,
            'value_id' => $valueId,
        ]);

        return true;
    }

    /**
     * Find product feature value by feature ID and value text
     *
     * HELPER METHOD for deduplication - prevents creating duplicate values
     *
     * Searches ps_feature_value for existing value with matching text.
     * Used by FeatureSyncService to avoid creating "1500W" twice.
     *
     * @param int $featureId PrestaShop feature ID (id_feature)
     * @param string $valueText Value text to search for (case-sensitive)
     * @return array|null Feature value data or null if not found
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function findProductFeatureValue(int $featureId, string $valueText): ?array
    {
        try {
            $response = $this->getProductFeatureValues([
                'filter[id_feature]' => $featureId,
                'display' => 'full',
            ]);

            if (!isset($response['product_feature_values'])) {
                return null;
            }

            // Handle single value vs array of values
            $allValues = $response['product_feature_values'];
            if (!isset($allValues[0]) && isset($allValues['id'])) {
                // Single value returned as object
                $allValues = [$allValues];
            }

            // Normalize value text for comparison
            $normalizedSearch = trim(strtolower($valueText));

            foreach ($allValues as $value) {
                // Extract value text from multilang structure
                $valueLang = null;

                if (isset($value['value'])) {
                    if (is_array($value['value'])) {
                        // Multilang: ['language' => [['id' => 1, 'value' => '...'], ...]]
                        if (isset($value['value']['language'])) {
                            $languages = is_array($value['value']['language'][0] ?? null)
                                ? $value['value']['language']
                                : [$value['value']['language']];
                            $valueLang = $languages[0]['value'] ?? ($languages[0] ?? null);
                        } elseif (isset($value['value'][0]['value'])) {
                            // Direct array format
                            $valueLang = $value['value'][0]['value'];
                        }
                    } else {
                        // Simple string
                        $valueLang = $value['value'];
                    }
                }

                if ($valueLang !== null) {
                    $normalizedValue = trim(strtolower((string) $valueLang));

                    if ($normalizedValue === $normalizedSearch) {
                        \Log::debug('[FEATURE API] Found existing feature value', [
                            'feature_id' => $featureId,
                            'value_text' => $valueText,
                            'value_id' => $value['id'] ?? null,
                        ]);

                        return $value;
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            \Log::warning('[FEATURE API] findProductFeatureValue failed', [
                'feature_id' => $featureId,
                'value_text' => $valueText,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get or create product feature value
     *
     * HELPER METHOD - combines findProductFeatureValue + createProductFeatureValue
     * for atomic "get or create" operations with deduplication.
     *
     * @param int $featureId PrestaShop feature ID
     * @param string $valueText Value text (will be used for all languages)
     * @param int $defaultLangId Default language ID for multilang (default: 1)
     * @return int PrestaShop feature value ID
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getOrCreateProductFeatureValue(int $featureId, string $valueText, int $defaultLangId = 1): int
    {
        // Try to find existing
        $existing = $this->findProductFeatureValue($featureId, $valueText);

        if ($existing && isset($existing['id'])) {
            return (int) $existing['id'];
        }

        // Create new
        $response = $this->createProductFeatureValue([
            'id_feature' => $featureId,
            'value' => [
                ['id' => $defaultLangId, 'value' => $valueText],
            ],
        ]);

        return (int) ($response['product_feature_value']['id'] ?? 0);
    }

    /*
    |--------------------------------------------------------------------------
    | COMBINATIONS (VARIANTS) API - ETAP_05c
    |--------------------------------------------------------------------------
    */

    /**
     * Get all combinations (variants) for a product
     *
     * @param int $productId PrestaShop product ID
     * @return array Combinations array
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getCombinations(int $productId): array
    {
        $endpoint = "/combinations?filter[id_product]={$productId}&display=full";

        $response = $this->makeRequest('GET', $endpoint);

        // Handle JSON response (flat array under 'combinations')
        // BasePrestaShopClient uses Output-Format: JSON header
        if (isset($response['combinations'])) {
            $combinations = $response['combinations'];

            // JSON format: combinations is already a flat array [0 => {...}, 1 => {...}]
            if (isset($combinations[0]) || empty($combinations)) {
                return $combinations;
            }

            // XML-like format with nested 'combination' key (legacy/fallback)
            if (isset($combinations['combination'])) {
                $combs = $combinations['combination'];
                // Single combination has 'id' directly
                if (isset($combs['id'])) {
                    return [$combs];
                }
                return $combs;
            }
        }

        return [];
    }

    /**
     * Get single combination by ID
     *
     * @param int $combinationId PrestaShop combination ID (ps_product_attribute.id)
     * @return array|null Combination data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getCombination(int $combinationId): ?array
    {
        try {
            $response = $this->makeRequest('GET', "/combinations/{$combinationId}");

            if (isset($response['combination'])) {
                return $response['combination'];
            }

            return null;
        } catch (\Exception $e) {
            \Log::warning('[PrestaShop8Client] getCombination failed', [
                'combination_id' => $combinationId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create new combination (variant) for product
     *
     * @param int $productId PrestaShop product ID
     * @param array $combinationData Combination data
     * @return array Created combination with ID
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function createCombination(int $productId, array $combinationData): array
    {
        // Ensure required fields
        $combinationData['id_product'] = $productId;

        if (!isset($combinationData['minimal_quantity'])) {
            $combinationData['minimal_quantity'] = 1;
        }

        $xmlBody = $this->arrayToXml(['combination' => $combinationData]);

        return $this->makeRequest('POST', '/combinations', [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);
    }

    /**
     * Update existing combination
     *
     * Follows GET-MODIFY-PUT pattern
     *
     * @param int $combinationId PrestaShop combination ID
     * @param array $updates Fields to update
     * @return array Updated combination
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function updateCombination(int $combinationId, array $updates): array
    {
        // GET existing combination
        $existing = $this->getCombination($combinationId);

        if (!$existing) {
            throw new \App\Exceptions\PrestaShopAPIException(
                "Combination {$combinationId} not found"
            );
        }

        // MERGE updates with existing
        $merged = array_merge($existing, $updates);
        $merged['id'] = $combinationId;

        // Remove read-only fields
        unset($merged['associations']);

        $xmlBody = $this->arrayToXml(['combination' => $merged]);

        return $this->makeRequest('PUT', "/combinations/{$combinationId}", [], [
            'body' => $xmlBody,
            'headers' => ['Content-Type' => 'application/xml'],
        ]);
    }

    /**
     * Delete combination
     *
     * @param int $combinationId PrestaShop combination ID
     * @return bool Success
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function deleteCombination(int $combinationId): bool
    {
        try {
            $this->makeRequest('DELETE', "/combinations/{$combinationId}");
            return true;
        } catch (\Exception $e) {
            \Log::error('[PrestaShop8Client] deleteCombination failed', [
                'combination_id' => $combinationId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Set combination images
     *
     * @param int $combinationId
     * @param array $imageIds Array of PrestaShop image IDs
     * @return bool Success
     */
    public function setCombinationImages(int $combinationId, array $imageIds): bool
    {
        try {
            $existing = $this->getCombination($combinationId);

            if (!$existing) {
                return false;
            }

            $associations = [
                'images' => [
                    'image' => array_map(fn($id) => ['id' => $id], $imageIds),
                ],
            ];

            // FIX 2025-12-08: PrestaShop API wymaga id_product i minimal_quantity w PUT request!
            $combinationData = [
                'id' => $combinationId,
                'id_product' => $existing['id_product'] ?? null,
                'minimal_quantity' => $existing['minimal_quantity'] ?? 1,
                'associations' => $associations,
            ];

            $xmlBody = $this->arrayToXml(['combination' => $combinationData]);

            $this->makeRequest('PUT', "/combinations/{$combinationId}", [], [
                'body' => $xmlBody,
                'headers' => ['Content-Type' => 'application/xml'],
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('[PrestaShop8Client] setCombinationImages failed', [
                'combination_id' => $combinationId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Set combination attribute values
     *
     * @param int $combinationId
     * @param array $attributeValueIds Array of ps_attribute.id values
     * @return bool Success
     */
    public function setCombinationAttributes(int $combinationId, array $attributeValueIds): bool
    {
        try {
            $existing = $this->getCombination($combinationId);

            if (!$existing) {
                return false;
            }

            // FIX 2025-12-08: buildXmlFromArray automatycznie singularyzuje 'product_option_values' → 'product_option_value'
            // NIE podawaj 'product_option_value' jako zagnieżdżonego klucza!
            $associations = [
                'product_option_values' => array_map(fn($id) => ['id' => $id], $attributeValueIds),
            ];

            // FIX 2025-12-08: PrestaShop API wymaga id_product i minimal_quantity w PUT request!
            $combinationData = [
                'id' => $combinationId,
                'id_product' => $existing['id_product'] ?? null,
                'minimal_quantity' => $existing['minimal_quantity'] ?? 1,
                'associations' => $associations,
            ];

            $xmlBody = $this->arrayToXml(['combination' => $combinationData]);

            \Log::info('[PrestaShop8Client] setCombinationAttributes', [
                'combination_id' => $combinationId,
                'id_product' => $existing['id_product'] ?? 'MISSING',
                'attribute_value_ids' => $attributeValueIds,
                'xml_body' => $xmlBody, // DEBUG: show generated XML
            ]);

            $this->makeRequest('PUT', "/combinations/{$combinationId}", [], [
                'body' => $xmlBody,
                'headers' => ['Content-Type' => 'application/xml'],
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('[PrestaShop8Client] setCombinationAttributes failed', [
                'combination_id' => $combinationId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
