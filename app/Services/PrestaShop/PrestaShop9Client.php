<?php

namespace App\Services\PrestaShop;

/**
 * PrestaShop 9.x API Client
 *
 * Implements BasePrestaShopClient for PrestaShop version 9.x
 * Includes v9-specific enhancements and endpoints
 */
class PrestaShop9Client extends BasePrestaShopClient
{
    public function getVersion(): string
    {
        return '9';
    }

    protected function getApiBasePath(): string
    {
        return '/api/v1'; // PrestaShop 9.x uses /api/v1
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
     * Get products with variants (v9 feature)
     *
     * @param array $filters Query filters
     * @return array Products with variant data
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getProductsWithVariants(array $filters = []): array
    {
        $filters['include_variants'] = 'true'; // v9 feature
        $queryParams = $this->buildQueryParams($filters);

        return $this->makeRequest('GET', "/products?{$queryParams}");
    }

    /**
     * Bulk update products (v9 feature)
     *
     * @param array $products Array of products to update
     * @return array Bulk operation result
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function bulkUpdateProducts(array $products): array
    {
        return $this->makeRequest('POST', '/products/bulk', ['products' => $products]);
    }

    /**
     * Get product performance metrics (v9 feature)
     *
     * @param int $productId PrestaShop product ID
     * @return array Performance metrics (views, sales, conversion rate)
     * @throws \App\Exceptions\PrestaShopAPIException
     */
    public function getProductPerformanceMetrics(int $productId): array
    {
        return $this->makeRequest('GET', "/products/{$productId}/metrics");
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
}
