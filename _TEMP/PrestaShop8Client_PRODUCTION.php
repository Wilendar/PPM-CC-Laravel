<?php

namespace App\Services\PrestaShop;

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
     * Create new product
     *
     * @param array $productData Product data in PrestaShop format
     * @return array Created product data with ID
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function createProduct(array $productData): array
    {
        return $this->makeRequest('POST', '/products', ['product' => $productData]);
    }

    /**
     * Update existing product
     *
     * @param int $productId PrestaShop product ID
     * @param array $productData Updated product data
     * @return array Updated product data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function updateProduct(int $productId, array $productData): array
    {
        return $this->makeRequest('PUT', "/products/{$productId}", ['product' => $productData]);
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
     * @param int $stockId PrestaShop stock_available ID
     * @param int $quantity New quantity
     * @return array Updated stock data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function updateStock(int $stockId, int $quantity): array
    {
        return $this->makeRequest('PUT', "/stock_availables/{$stockId}", [
            'stock_available' => ['quantity' => $quantity]
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
     * @param array $priceData Specific price data
     * @return array Created specific price with ID
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function createSpecificPrice(array $priceData): array
    {
        return $this->makeRequest('POST', '/specific_prices', ['specific_price' => $priceData]);
    }

    /**
     * Update specific price
     *
     * PROBLEM #4 - Task 18: PrestaShop Price Sync
     * Updates existing specific_price entry
     *
     * @param int $priceId PrestaShop specific_price ID
     * @param array $priceData Updated price data
     * @return array Updated specific price
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function updateSpecificPrice(int $priceId, array $priceData): array
    {
        return $this->makeRequest('PUT', "/specific_prices/{$priceId}", ['specific_price' => $priceData]);
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
     * @param array $groupData Attribute group data in PrestaShop format
     * @return array Created attribute group data with ID
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function createAttributeGroup(array $groupData): array
    {
        return $this->makeRequest('POST', '/product_options', ['product_option' => $groupData]);
    }

    /**
     * Update existing attribute group
     *
     * @param int $groupId PrestaShop attribute group ID
     * @param array $groupData Updated attribute group data
     * @return array Updated attribute group data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function updateAttributeGroup(int $groupId, array $groupData): array
    {
        return $this->makeRequest('PUT', "/product_options/{$groupId}", ['product_option' => $groupData]);
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
     * @param array $valueData Attribute value data in PrestaShop format
     * @return array Created attribute value data with ID
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function createAttributeValue(array $valueData): array
    {
        return $this->makeRequest('POST', '/product_option_values', ['product_option_value' => $valueData]);
    }

    /**
     * Update existing attribute value
     *
     * @param int $valueId PrestaShop attribute value ID
     * @param array $valueData Updated attribute value data
     * @return array Updated attribute value data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function updateAttributeValue(int $valueId, array $valueData): array
    {
        return $this->makeRequest('PUT', "/product_option_values/{$valueId}", ['product_option_value' => $valueData]);
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
}
