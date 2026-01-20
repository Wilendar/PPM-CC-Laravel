<?php

namespace App\Services\ERP\SubiektGT;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\ConnectionException;
use App\Exceptions\SubiektApiException;

/**
 * SubiektRestApiClient
 *
 * HTTP client for connecting to Subiekt GT REST API Wrapper.
 * The wrapper runs on EXEA Windows Server and provides HTTPS access
 * to Subiekt GT database.
 *
 * Usage:
 * $client = new SubiektRestApiClient([
 *     'base_url' => 'https://api.example.com',
 *     'api_key' => 'your-api-key',
 * ]);
 * $products = $client->getProducts(['page' => 1, 'page_size' => 100]);
 *
 * @package App\Services\ERP\SubiektGT
 * @version 1.0.0
 */
class SubiektRestApiClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;
    protected int $connectTimeout;
    protected int $retryTimes;
    protected int $retryDelay;
    protected bool $verifySsl;

    /**
     * Constructor
     *
     * @param array $config Client configuration
     */
    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['base_url'] ?? '', '/');
        $this->apiKey = $config['api_key'] ?? '';
        $this->timeout = $config['timeout'] ?? 30;
        $this->connectTimeout = $config['connect_timeout'] ?? 10;
        $this->retryTimes = $config['retry_times'] ?? 3;
        $this->retryDelay = $config['retry_delay'] ?? 100; // milliseconds
        $this->verifySsl = $config['verify_ssl'] ?? true;

        if (empty($this->baseUrl)) {
            throw new \InvalidArgumentException('Subiekt REST API base_url is required');
        }

        if (empty($this->apiKey)) {
            throw new \InvalidArgumentException('Subiekt REST API api_key is required');
        }
    }

    /**
     * Make HTTP request to the API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $params Query parameters or body data
     * @return array Response data
     * @throws SubiektApiException
     */
    protected function request(string $method, string $endpoint, array $params = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        try {
            $http = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->retry($this->retryTimes, $this->retryDelay, function ($exception, $request) {
                    // Only retry on connection errors or 5xx responses
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }
                    if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                        $status = $exception->response->status();
                        return $status >= 500 && $status < 600;
                    }
                    return false;
                });

            // Disable SSL verification if configured (not recommended for production)
            if (!$this->verifySsl) {
                $http = $http->withoutVerifying();
            }

            // Make the request
            $response = match (strtoupper($method)) {
                'GET' => $http->get($url, $params),
                'POST' => $http->post($url, $params),
                'PUT' => $http->put($url, $params),
                'PATCH' => $http->patch($url, $params),
                'DELETE' => $http->delete($url, $params),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            return $this->handleResponse($response, $endpoint);

        } catch (ConnectionException $e) {
            Log::error('SubiektRestApiClient: Connection failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new SubiektApiException(
                'Failed to connect to Subiekt GT API: ' . $e->getMessage(),
                503,
                $e
            );
        } catch (\Exception $e) {
            Log::error('SubiektRestApiClient: Request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new SubiektApiException(
                'Subiekt GT API request failed: ' . $e->getMessage(),
                500,
                $e
            );
        }
    }

    /**
     * Handle API response
     *
     * @param Response $response HTTP response
     * @param string $endpoint Endpoint for logging
     * @return array Parsed response data
     * @throws SubiektApiException
     */
    protected function handleResponse(Response $response, string $endpoint): array
    {
        $statusCode = $response->status();
        $data = $response->json() ?? [];

        // Check for API-level errors
        if (!$response->successful()) {
            // Error can be string or object depending on API version
            $errorMessage = is_string($data['error'] ?? null)
                ? $data['error']
                : ($data['error']['message'] ?? 'Unknown API error');

            Log::warning('SubiektRestApiClient: API error', [
                'endpoint' => $endpoint,
                'status' => $statusCode,
                'error' => $errorMessage,
            ]);

            throw new SubiektApiException(
                "Subiekt GT API error ({$statusCode}): {$errorMessage}",
                $statusCode
            );
        }

        // Check for success flag in response
        if (isset($data['success']) && $data['success'] === false) {
            // Error can be string or object depending on API version
            $errorMessage = is_string($data['error'] ?? null)
                ? $data['error']
                : ($data['error']['message'] ?? 'API returned success=false');

            throw new SubiektApiException(
                "Subiekt GT API error: {$errorMessage}",
                is_array($data['error'] ?? null) ? ($data['error']['code'] ?? 400) : 400
            );
        }

        return $data;
    }

    // ==========================================
    // HEALTH & CONNECTION
    // ==========================================

    /**
     * Test connection to the API
     *
     * @return array Health check result
     */
    public function healthCheck(): array
    {
        return $this->request('GET', '/api/health');
    }

    /**
     * Get database statistics
     *
     * Note: Stats are included in health endpoint in our API
     *
     * @return array Statistics
     */
    public function getStats(): array
    {
        $health = $this->healthCheck();

        return [
            'success' => true,
            'total_products' => $health['products_count'] ?? 0,
            'active_products' => $health['products_count'] ?? 0,
            'database' => $health['database'] ?? 'unknown',
            'server_version' => $health['server_version'] ?? 'unknown',
            'response_time_ms' => $health['response_time_ms'] ?? null,
        ];
    }

    /**
     * Test connection and return simplified result
     *
     * @return array ['success' => bool, 'message' => string, 'response_time' => float]
     */
    public function testConnection(): array
    {
        $startTime = microtime(true);

        try {
            $health = $this->healthCheck();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => ($health['status'] ?? '') === 'ok',
                'message' => 'Polaczenie z Subiekt GT REST API pomyslne',
                'response_time' => $responseTime,
                'details' => [
                    'database' => $health['database'] ?? 'unknown',
                    'server_version' => $health['server_version'] ?? 'unknown',
                    'api_response_time' => $health['response_time_ms'] ?? null,
                ],
            ];
        } catch (SubiektApiException $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => false,
                'message' => 'Blad polaczenia: ' . $e->getMessage(),
                'response_time' => $responseTime,
                'details' => [
                    'error_code' => $e->getCode(),
                ],
            ];
        }
    }

    // ==========================================
    // PRODUCTS
    // ==========================================

    /**
     * Get products with pagination
     *
     * @param array $params Query parameters
     *   - page: int (default: 1)
     *   - pageSize: int (default: 100, max: 500)
     *   - priceLevel: int (default: 0, range: 0-10, maps to tc_CenaNetto0..10)
     *   - warehouseId: int (default: 1)
     *   - sku: string (filter by SKU, LIKE)
     *   - name: string (filter by name, LIKE)
     * @return array Products with pagination info
     */
    public function getProducts(array $params = []): array
    {
        // Map legacy parameter names to new API format
        $apiParams = [];

        if (isset($params['page'])) {
            $apiParams['page'] = $params['page'];
        }
        if (isset($params['page_size'])) {
            $apiParams['pageSize'] = $params['page_size'];
        }
        if (isset($params['pageSize'])) {
            $apiParams['pageSize'] = $params['pageSize'];
        }
        if (isset($params['price_type_id'])) {
            // Map price_type_id to priceLevel (0-based)
            $apiParams['priceLevel'] = max(0, (int)$params['price_type_id'] - 1);
        }
        if (isset($params['priceLevel'])) {
            $apiParams['priceLevel'] = $params['priceLevel'];
        }
        if (isset($params['warehouse_id'])) {
            $apiParams['warehouseId'] = $params['warehouse_id'];
        }
        if (isset($params['warehouseId'])) {
            $apiParams['warehouseId'] = $params['warehouseId'];
        }
        if (isset($params['sku'])) {
            $apiParams['sku'] = $params['sku'];
        }
        if (isset($params['name'])) {
            $apiParams['name'] = $params['name'];
        }

        return $this->request('GET', '/api/products', $apiParams);
    }

    /**
     * Get single product by ID
     *
     * @param int $productId Subiekt GT product ID
     * @param int|null $priceLevel Price level (0-10, maps to tc_CenaNetto0..10)
     * @param int|null $warehouseId Warehouse ID
     * @return array Product data
     */
    public function getProductById(int $productId, ?int $priceLevel = null, ?int $warehouseId = null): array
    {
        $params = array_filter([
            'priceLevel' => $priceLevel,
            'warehouseId' => $warehouseId,
        ]);

        return $this->request('GET', "/api/products/{$productId}", $params);
    }

    /**
     * Get single product by SKU
     *
     * @param string $sku Product SKU
     * @param int|null $priceLevel Price level (0-10, maps to tc_CenaNetto0..10)
     * @param int|null $warehouseId Warehouse ID
     * @return array Product data
     */
    public function getProductBySku(string $sku, ?int $priceLevel = null, ?int $warehouseId = null): array
    {
        $encodedSku = rawurlencode($sku);
        $params = array_filter([
            'priceLevel' => $priceLevel,
            'warehouseId' => $warehouseId,
        ]);

        return $this->request('GET', "/api/products/sku/{$encodedSku}", $params);
    }

    /**
     * Get all products (handles pagination automatically)
     *
     * @param array $filters Filters (sku, name, modified_since, etc.)
     * @param int $pageSize Items per page
     * @return \Generator Yields products one by one
     */
    public function getAllProducts(array $filters = [], int $pageSize = 100): \Generator
    {
        $page = 1;
        $filters['page_size'] = $pageSize;

        do {
            $filters['page'] = $page;
            $response = $this->getProducts($filters);

            $products = $response['data'] ?? [];
            foreach ($products as $product) {
                yield $product;
            }

            $pagination = $response['pagination'] ?? [];
            $hasNext = $pagination['has_next'] ?? false;
            $page++;

        } while ($hasNext);
    }

    // ==========================================
    // STOCK
    // ==========================================

    /**
     * Get stock levels with pagination
     *
     * @param array $params Query parameters
     *   - page: int
     *   - page_size: int
     *   - warehouse_id: int (filter by warehouse)
     * @return array Stock data with pagination
     */
    public function getStock(array $params = []): array
    {
        return $this->request('GET', '/api/stock', $params);
    }

    /**
     * Get stock for single product
     *
     * @param int $productId Product ID
     * @return array Stock per warehouse
     */
    public function getProductStock(int $productId): array
    {
        return $this->request('GET', "/api/stock/{$productId}");
    }

    /**
     * Get stock for product by SKU
     *
     * @param string $sku Product SKU
     * @return array Stock per warehouse
     */
    public function getProductStockBySku(string $sku): array
    {
        $encodedSku = rawurlencode($sku);
        return $this->request('GET', "/api/stock/sku/{$encodedSku}");
    }

    // ==========================================
    // PRICES
    // ==========================================

    /**
     * Get prices for single product
     *
     * @param int $productId Product ID
     * @return array Prices for all price types
     */
    public function getProductPrices(int $productId): array
    {
        return $this->request('GET', "/api/prices/{$productId}");
    }

    /**
     * Get prices for product by SKU
     *
     * @param string $sku Product SKU
     * @return array Prices for all price types
     */
    public function getProductPricesBySku(string $sku): array
    {
        $encodedSku = rawurlencode($sku);
        return $this->request('GET', "/api/prices/sku/{$encodedSku}");
    }

    // ==========================================
    // REFERENCE DATA
    // ==========================================

    /**
     * Get all warehouses
     *
     * @param bool $useCache Use cached result if available
     * @return array Warehouses list
     */
    public function getWarehouses(bool $useCache = true): array
    {
        $cacheKey = 'subiekt_api_warehouses';

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = $this->request('GET', '/api/warehouses');

        if ($useCache) {
            Cache::put($cacheKey, $response, now()->addHours(1));
        }

        return $response;
    }

    /**
     * Get all price levels (0-10, mapping to tc_CenaNetto0..tc_CenaNetto10)
     *
     * @param bool $useCache Use cached result if available
     * @return array Price levels list
     */
    public function getPriceLevels(bool $useCache = true): array
    {
        $cacheKey = 'subiekt_api_price_levels';

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = $this->request('GET', '/api/price-levels');

        if ($useCache) {
            Cache::put($cacheKey, $response, now()->addHours(1));
        }

        return $response;
    }

    /**
     * Get all price types (alias for getPriceLevels for backward compatibility)
     *
     * @param bool $useCache Use cached result if available
     * @return array Price levels list
     */
    public function getPriceTypes(bool $useCache = true): array
    {
        return $this->getPriceLevels($useCache);
    }

    /**
     * Get all VAT rates
     *
     * @param bool $useCache Use cached result if available
     * @return array VAT rates list
     */
    public function getVatRates(bool $useCache = true): array
    {
        $cacheKey = 'subiekt_api_vat_rates';

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = $this->request('GET', '/api/vat-rates');

        if ($useCache) {
            Cache::put($cacheKey, $response, now()->addHours(1));
        }

        return $response;
    }

    /**
     * Get all manufacturers
     *
     * @param bool $useCache Use cached result if available
     * @return array Manufacturers list
     */
    public function getManufacturers(bool $useCache = true): array
    {
        $cacheKey = 'subiekt_api_manufacturers';

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = $this->request('GET', '/api/manufacturers');

        if ($useCache) {
            Cache::put($cacheKey, $response, now()->addHours(1));
        }

        return $response;
    }

    /**
     * Get all product groups
     *
     * @param bool $useCache Use cached result if available
     * @return array Product groups list
     */
    public function getProductGroups(bool $useCache = true): array
    {
        $cacheKey = 'subiekt_api_product_groups';

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = $this->request('GET', '/api/product-groups');

        if ($useCache) {
            Cache::put($cacheKey, $response, now()->addHours(1));
        }

        return $response;
    }

    /**
     * Get all measurement units
     *
     * @param bool $useCache Use cached result if available
     * @return array Units list
     */
    public function getUnits(bool $useCache = true): array
    {
        $cacheKey = 'subiekt_api_units';

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = $this->request('GET', '/api/units');

        if ($useCache) {
            Cache::put($cacheKey, $response, now()->addHours(1));
        }

        return $response;
    }

    /**
     * Clear cached reference data
     */
    public function clearCache(): void
    {
        $cacheKeys = [
            'subiekt_api_warehouses',
            'subiekt_api_price_types',
            'subiekt_api_price_levels',
            'subiekt_api_vat_rates',
            'subiekt_api_manufacturers',
            'subiekt_api_product_groups',
            'subiekt_api_units',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }
}
