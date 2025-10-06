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
}
