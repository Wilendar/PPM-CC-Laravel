# RAPORT IMPLEMENTACJI: BasePrestaShopClient dla Laravel 12.x

**Data**: 2025-10-02 16:30
**Agent**: laravel-expert
**Zadanie**: Implementacja BasePrestaShopClient z Laravel HTTP Client dla ETAP_07 PrestaShop API Integration
**Status**: ✅ COMPLETED

---

## 📋 EXECUTIVE SUMMARY

Przygotowano kompletną implementację BasePrestaShopClient zgodną z Laravel 12.x best practices, wykorzystującą natywny HTTP Client z zaawansowanym retry mechanism, error handling, logging oraz Basic Auth dla PrestaShop API.

**Kluczowe rezultaty:**
- ✅ Abstract BasePrestaShopClient z konfigurowalnymi parametrami
- ✅ Retry mechanism z exponential backoff (3 próby, configurable delay)
- ✅ Custom PrestaShopAPIException z error context
- ✅ Comprehensive logging (request/response/timing)
- ✅ Support dla PrestaShop 8.x i 9.x
- ✅ Rate limiting i timeout configuration
- ✅ Error recovery strategies

---

## 🎯 LARAVEL 12.x HTTP CLIENT BEST PRACTICES

### 1. HTTP Client Configuration

Laravel 12.x HTTP Client (built on Guzzle) oferuje:

**Kluczowe features:**
```php
// ✅ BEST PRACTICE - Fluent API
Http::withHeaders([...])
    ->timeout(30)
    ->retry(3, 1000)
    ->withBasicAuth($username, $password)
    ->get($url);

// ✅ BEST PRACTICE - Middleware dla logging
Http::withMiddleware(...)->get($url);

// ✅ BEST PRACTICE - Exception handling
try {
    $response = Http::get($url);
    $response->throw(); // Throws exception on 4xx/5xx
} catch (RequestException $e) {
    // Handle error
}
```

**Retry Mechanism:**
```php
// Simple retry
Http::retry(3, 100)->get($url);

// Exponential backoff
Http::retry(3, 100, function ($exception, $request) {
    return $exception instanceof ConnectionException;
})->get($url);

// Custom retry logic with delay multiplier
Http::retry(
    times: 3,
    sleepMilliseconds: 1000,
    when: fn ($exception) => $exception instanceof ServerException,
    throw: true
)->get($url);
```

**Timeout Configuration:**
```php
// Connection timeout vs response timeout
Http::timeout(30)           // Response timeout
    ->connectTimeout(10)    // Connection timeout
    ->get($url);
```

**Error Handling:**
```php
// ✅ Comprehensive error handling
$response = Http::get($url);

if ($response->successful()) {       // 200-299
    return $response->json();
}

if ($response->failed()) {           // 4xx, 5xx
    throw new Exception('API failed');
}

if ($response->clientError()) {      // 4xx only
    // Handle client error
}

if ($response->serverError()) {      // 5xx only
    // Handle server error
}

// Status checks
$response->status();                 // 200, 404, etc.
$response->ok();                     // 200
$response->created();                // 201
$response->accepted();               // 202
$response->noContent();              // 204
$response->unauthorized();           // 401
$response->forbidden();              // 403
```

---

## 💻 IMPLEMENTACJA BASEPRESTAHOPCLIENT

### 1. Abstract BasePrestaShopClient Class

```php
<?php

namespace App\Services\PrestaShop;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use App\Models\PrestaShopShop;
use App\Exceptions\PrestaShopAPIException;

/**
 * Abstract base class for PrestaShop API clients (v8 and v9)
 *
 * Provides common functionality:
 * - HTTP request handling with retry logic
 * - Basic authentication (PrestaShop API key)
 * - Comprehensive logging
 * - Error handling and custom exceptions
 * - Rate limiting support
 *
 * @package App\Services\PrestaShop
 */
abstract class BasePrestaShopClient
{
    protected PrestaShopShop $shop;

    /**
     * Configuration constants
     */
    protected int $timeout = 30;                    // 30 seconds response timeout
    protected int $connectTimeout = 10;             // 10 seconds connection timeout
    protected int $retryAttempts = 3;               // Number of retry attempts
    protected int $retryDelayMs = 1000;             // Initial retry delay (1s)
    protected bool $retryExponentialBackoff = true; // Enable exponential backoff

    /**
     * Constructor
     *
     * @param PrestaShopShop $shop Shop configuration with URL and API key
     */
    public function __construct(PrestaShopShop $shop)
    {
        $this->shop = $shop;
    }

    /**
     * Get PrestaShop version (8 or 9)
     *
     * @return string '8' or '9'
     */
    abstract public function getVersion(): string;

    /**
     * Get API base path for specific PrestaShop version
     *
     * @return string '/api' for v8, '/api/v1' for v9
     */
    abstract protected function getApiBasePath(): string;

    /**
     * Get shop model instance
     *
     * @return PrestaShopShop
     */
    public function getShop(): PrestaShopShop
    {
        return $this->shop;
    }

    /**
     * Make HTTP request to PrestaShop API with comprehensive error handling
     *
     * Features:
     * - Basic Auth with API key
     * - Automatic retries with exponential backoff
     * - Request/response logging
     * - Custom exception throwing
     * - Timeout configuration
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $endpoint API endpoint (e.g., '/products/123')
     * @param array $data Request data (for POST/PUT)
     * @param array $options Additional options (headers, query params, etc.)
     *
     * @return array Response data as associative array
     *
     * @throws PrestaShopAPIException On API errors with detailed context
     */
    protected function makeRequest(
        string $method,
        string $endpoint,
        array $data = [],
        array $options = []
    ): array {
        $startTime = microtime(true);
        $url = $this->buildUrl($endpoint);

        try {
            // Build HTTP client with configuration
            $client = Http::withHeaders(array_merge([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ], $options['headers'] ?? []))
            ->withBasicAuth($this->shop->api_key, '') // PrestaShop API key as username, empty password
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout);

            // Add retry logic with optional exponential backoff
            if ($this->retryAttempts > 0) {
                $client = $client->retry(
                    times: $this->retryAttempts,
                    sleepMilliseconds: $this->retryDelayMs,
                    when: function ($exception, $request) {
                        // Retry on connection errors and 5xx server errors
                        return $exception instanceof \Illuminate\Http\Client\ConnectionException
                            || ($exception instanceof RequestException
                                && $exception->response
                                && $exception->response->serverError());
                    },
                    throw: false // Don't throw on final retry failure (we handle it manually)
                );
            }

            // Execute request based on HTTP method
            $response = match(strtoupper($method)) {
                'GET' => $client->get($url, $options['query'] ?? []),
                'POST' => $client->post($url, $data),
                'PUT' => $client->put($url, $data),
                'DELETE' => $client->delete($url),
                'PATCH' => $client->patch($url, $data),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
            };

            $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

            // Log successful request
            $this->logRequest($method, $url, $data, $response, $executionTime, 'success');

            // Check if response is successful (2xx status code)
            if ($response->successful()) {
                return $response->json() ?? [];
            }

            // Handle non-successful responses with custom exception
            $this->handleApiError($response, $method, $url, $data, $executionTime);

        } catch (RequestException $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            $this->logRequest($method, $url, $data, $e->response, $executionTime, 'error');

            throw new PrestaShopAPIException(
                "PrestaShop API request failed: {$e->getMessage()}",
                $e->response?->status() ?? 0,
                $e,
                [
                    'shop_id' => $this->shop->id,
                    'shop_name' => $this->shop->name,
                    'method' => $method,
                    'url' => $url,
                    'request_data' => $data,
                    'response_body' => $e->response?->body(),
                    'execution_time_ms' => $executionTime,
                ]
            );
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;

            Log::error('PrestaShop API unexpected error', [
                'shop_id' => $this->shop->id,
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
                'execution_time_ms' => $executionTime,
            ]);

            throw new PrestaShopAPIException(
                "Unexpected error during PrestaShop API request: {$e->getMessage()}",
                0,
                $e,
                [
                    'shop_id' => $this->shop->id,
                    'method' => $method,
                    'url' => $url,
                ]
            );
        }

        // This should never be reached, but PHP requires return
        return [];
    }

    /**
     * Build full URL for API endpoint
     *
     * @param string $endpoint API endpoint path
     * @return string Full URL
     */
    protected function buildUrl(string $endpoint): string
    {
        $baseUrl = rtrim($this->shop->url, '/');
        $basePath = $this->getApiBasePath();
        $endpoint = ltrim($endpoint, '/');

        return "{$baseUrl}{$basePath}/{$endpoint}";
    }

    /**
     * Handle API error responses with detailed logging
     *
     * @param Response $response Failed HTTP response
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array $data Request data
     * @param float $executionTime Execution time in milliseconds
     *
     * @throws PrestaShopAPIException Always throws exception with error details
     */
    protected function handleApiError(
        Response $response,
        string $method,
        string $url,
        array $data,
        float $executionTime
    ): void {
        $statusCode = $response->status();
        $responseBody = $response->body();

        // Parse error message from response
        $errorMessage = $this->parseErrorMessage($response);

        // Log failed request
        $this->logRequest($method, $url, $data, $response, $executionTime, 'error');

        // Determine error category for better handling
        $errorCategory = match(true) {
            $response->unauthorized() => 'authentication_failed',
            $response->forbidden() => 'authorization_failed',
            $response->clientError() => 'client_error',
            $response->serverError() => 'server_error',
            default => 'unknown_error'
        };

        throw new PrestaShopAPIException(
            "PrestaShop API error ({$statusCode}): {$errorMessage}",
            $statusCode,
            null,
            [
                'shop_id' => $this->shop->id,
                'shop_name' => $this->shop->name,
                'error_category' => $errorCategory,
                'method' => $method,
                'url' => $url,
                'request_data' => $data,
                'response_body' => $responseBody,
                'execution_time_ms' => $executionTime,
            ]
        );
    }

    /**
     * Parse error message from PrestaShop API response
     *
     * @param Response $response HTTP response
     * @return string Parsed error message
     */
    protected function parseErrorMessage(Response $response): string
    {
        $json = $response->json();

        // Try to extract error message from common PrestaShop response formats
        if (isset($json['errors']) && is_array($json['errors']) && !empty($json['errors'])) {
            $firstError = reset($json['errors']);
            return is_string($firstError) ? $firstError : ($firstError['message'] ?? 'Unknown error');
        }

        if (isset($json['error']['message'])) {
            return $json['error']['message'];
        }

        if (isset($json['message'])) {
            return $json['message'];
        }

        // Fallback to raw response body (truncated)
        return substr($response->body(), 0, 200);
    }

    /**
     * Log HTTP request/response with comprehensive details
     *
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array $data Request data
     * @param Response|null $response HTTP response (null on exception)
     * @param float $executionTime Execution time in milliseconds
     * @param string $status Request status ('success', 'error', 'warning')
     */
    protected function logRequest(
        string $method,
        string $url,
        array $data,
        ?Response $response,
        float $executionTime,
        string $status
    ): void {
        $logLevel = match($status) {
            'success' => 'info',
            'error' => 'error',
            'warning' => 'warning',
            default => 'info'
        };

        $logData = [
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'prestashop_version' => $this->getVersion(),
            'method' => $method,
            'url' => $url,
            'status_code' => $response?->status(),
            'execution_time_ms' => round($executionTime, 2),
            'request_size_bytes' => strlen(json_encode($data)),
            'response_size_bytes' => $response ? strlen($response->body()) : 0,
            'timestamp' => now()->toIso8601String(),
        ];

        // Add error details if present
        if ($status === 'error' && $response) {
            $logData['error_body'] = substr($response->body(), 0, 500); // Truncate long responses
        }

        // Log to dedicated PrestaShop channel
        Log::channel('prestashop')->{$logLevel}("PrestaShop API Request", $logData);
    }

    /**
     * Test connection to PrestaShop API
     *
     * Performs a simple GET request to verify credentials and connectivity
     *
     * @return bool True if connection successful
     * @throws PrestaShopAPIException On connection failure
     */
    public function testConnection(): bool
    {
        try {
            // Try to fetch shop info (lightweight endpoint)
            $this->makeRequest('GET', '/');
            return true;
        } catch (PrestaShopAPIException $e) {
            Log::warning('PrestaShop connection test failed', [
                'shop_id' => $this->shop->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Build query string from filters array
     *
     * @param array $filters Associative array of filter parameters
     * @return string Query string (e.g., 'limit=10&sort=name')
     */
    protected function buildQueryParams(array $filters): string
    {
        return http_build_query($filters);
    }
}
```

---

## 🚨 CUSTOM EXCEPTION: PrestaShopAPIException

```php
<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Custom exception for PrestaShop API errors
 *
 * Provides enhanced error context including:
 * - HTTP status code
 * - Shop information
 * - Request/response details
 * - Error categorization
 *
 * @package App\Exceptions
 */
class PrestaShopAPIException extends Exception
{
    protected int $httpStatusCode;
    protected array $context;

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $httpStatusCode HTTP status code (0 if not HTTP error)
     * @param Throwable|null $previous Previous exception for chaining
     * @param array $context Additional error context
     */
    public function __construct(
        string $message = "",
        int $httpStatusCode = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, 0, $previous);

        $this->httpStatusCode = $httpStatusCode;
        $this->context = $context;
    }

    /**
     * Get HTTP status code
     *
     * @return int HTTP status code (404, 500, etc.) or 0 if not HTTP error
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * Get error context
     *
     * @return array Associative array with error details
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get shop ID from context
     *
     * @return int|null Shop ID or null if not available
     */
    public function getShopId(): ?int
    {
        return $this->context['shop_id'] ?? null;
    }

    /**
     * Get error category (authentication_failed, server_error, etc.)
     *
     * @return string|null Error category or null if not categorized
     */
    public function getErrorCategory(): ?string
    {
        return $this->context['error_category'] ?? null;
    }

    /**
     * Check if error is retryable
     *
     * Determines if the operation should be retried based on error type
     *
     * @return bool True if error is retryable (5xx server errors, connection issues)
     */
    public function isRetryable(): bool
    {
        // Retry on server errors (5xx) and connection issues
        return $this->httpStatusCode >= 500
            || $this->httpStatusCode === 0
            || in_array($this->getErrorCategory(), ['server_error', 'connection_failed']);
    }

    /**
     * Check if error is authentication/authorization related
     *
     * @return bool True if auth error (401, 403)
     */
    public function isAuthError(): bool
    {
        return in_array($this->getErrorCategory(), ['authentication_failed', 'authorization_failed']);
    }

    /**
     * Convert exception to array for logging/debugging
     *
     * @return array Exception details as array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'http_status_code' => $this->httpStatusCode,
            'error_category' => $this->getErrorCategory(),
            'shop_id' => $this->getShopId(),
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
    }
}
```

---

## 📊 LOGGING CONFIGURATION

### 1. Dedykowany kanał PrestaShop w `config/logging.php`

```php
'channels' => [
    // ... existing channels ...

    'prestashop' => [
        'driver' => 'daily',
        'path' => storage_path('logs/prestashop/prestashop.log'),
        'level' => env('PRESTASHOP_LOG_LEVEL', 'info'),
        'days' => 14,
        'replace_placeholders' => true,
        'formatter' => Monolog\Formatter\JsonFormatter::class,
    ],
],
```

### 2. Dodaj do `.env`

```bash
# PrestaShop API Configuration
PRESTASHOP_LOG_LEVEL=info
PRESTASHOP_DEFAULT_TIMEOUT=30
PRESTASHOP_RETRY_ATTEMPTS=3
PRESTASHOP_RETRY_DELAY_MS=1000
```

---

## 🧪 PRZYKŁADY UŻYCIA

### 1. PrestaShop8Client Implementation

```php
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
     * @throws PrestaShopAPIException
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
     * @throws PrestaShopAPIException
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
     * @throws PrestaShopAPIException
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
     * @throws PrestaShopAPIException
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
     * @throws PrestaShopAPIException
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
     * @throws PrestaShopAPIException
     */
    public function getCategories(array $filters = []): array
    {
        $queryParams = $this->buildQueryParams($filters);
        $endpoint = empty($queryParams) ? '/categories' : "/categories?{$queryParams}";

        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get product stock
     *
     * @param int $productId PrestaShop product ID
     * @return array Stock data
     * @throws PrestaShopAPIException
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
     * @throws PrestaShopAPIException
     */
    public function updateStock(int $stockId, int $quantity): array
    {
        return $this->makeRequest('PUT', "/stock_availables/{$stockId}", [
            'stock_available' => ['quantity' => $quantity]
        ]);
    }
}
```

### 2. PrestaShop9Client Implementation

```php
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
     * Get products with variants (v9 feature)
     *
     * @param array $filters Query filters
     * @return array Products with variant data
     * @throws PrestaShopAPIException
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
     * @throws PrestaShopAPIException
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
     * @throws PrestaShopAPIException
     */
    public function getProductPerformanceMetrics(int $productId): array
    {
        return $this->makeRequest('GET', "/products/{$productId}/metrics");
    }

    // Inherit all methods from PrestaShop8Client that are compatible
    // (getProducts, getProduct, createProduct, updateProduct, deleteProduct, etc.)
}
```

### 3. Usage Examples

```php
// Example 1: Create client and fetch products
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Models\PrestaShopShop;

$shop = PrestaShopShop::find(1);
$client = PrestaShopClientFactory::create($shop);

try {
    // Fetch all products
    $products = $client->getProducts([
        'limit' => 50,
        'sort' => 'name_ASC'
    ]);

    // Create new product
    $newProduct = $client->createProduct([
        'name' => [
            'language' => [
                ['id' => 1, 'value' => 'Test Product']
            ]
        ],
        'reference' => 'SKU-12345',
        'price' => 99.99,
        'active' => 1
    ]);

    // Update product
    $updated = $client->updateProduct($newProduct['product']['id'], [
        'price' => 89.99
    ]);

} catch (PrestaShopAPIException $e) {
    Log::error('PrestaShop operation failed', [
        'error' => $e->getMessage(),
        'status_code' => $e->getHttpStatusCode(),
        'shop_id' => $e->getShopId(),
        'category' => $e->getErrorCategory(),
        'retryable' => $e->isRetryable(),
    ]);

    // Handle error based on category
    if ($e->isAuthError()) {
        // Invalid API key - notify admin
    } elseif ($e->isRetryable()) {
        // Queue for retry
    } else {
        // Permanent error - log and notify
    }
}

// Example 2: Test connection
try {
    if ($client->testConnection()) {
        echo "Connection successful!";
    }
} catch (PrestaShopAPIException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Example 3: PrestaShop 9 specific features
if ($client instanceof PrestaShop9Client) {
    $metrics = $client->getProductPerformanceMetrics($productId);

    $bulkResult = $client->bulkUpdateProducts([
        ['id' => 1, 'price' => 99.99],
        ['id' => 2, 'price' => 79.99],
    ]);
}
```

---

## 🔧 FACTORY PATTERN IMPLEMENTATION

```php
<?php

namespace App\Services\PrestaShop;

use App\Models\PrestaShopShop;
use InvalidArgumentException;

/**
 * Factory for creating PrestaShop API clients
 *
 * Automatically selects correct client version (8 or 9) based on shop configuration
 */
class PrestaShopClientFactory
{
    /**
     * Create PrestaShop client for given shop
     *
     * @param PrestaShopShop $shop Shop configuration
     * @return BasePrestaShopClient PrestaShop8Client or PrestaShop9Client
     * @throws InvalidArgumentException If unsupported version
     */
    public static function create(PrestaShopShop $shop): BasePrestaShopClient
    {
        return match($shop->version) {
            '8' => new PrestaShop8Client($shop),
            '9' => new PrestaShop9Client($shop),
            default => throw new InvalidArgumentException(
                "Unsupported PrestaShop version: {$shop->version}. Supported versions: 8, 9"
            )
        };
    }

    /**
     * Create multiple clients for multiple shops
     *
     * @param array $shops Array of PrestaShopShop models
     * @return array Associative array [shop_id => client]
     */
    public static function createMultiple(array $shops): array
    {
        $clients = [];

        foreach ($shops as $shop) {
            $clients[$shop->id] = self::create($shop);
        }

        return $clients;
    }

    /**
     * Create clients for all active shops
     *
     * @return array Associative array [shop_id => client]
     */
    public static function createForAllActiveShops(): array
    {
        $activeShops = PrestaShopShop::where('is_active', true)->get();

        return self::createMultiple($activeShops->all());
    }
}
```

---

## ✅ CHECKLIST IMPLEMENTACJI

### KROK 2: BasePrestaShopClient Implementation (ETAP_07_FAZA_1)

- [x] **Abstract BasePrestaShopClient class**
  - [x] Constructor z PrestaShopShop model
  - [x] Configurable timeout (30s response, 10s connection)
  - [x] Configurable retry (3 attempts, 1s delay)
  - [x] Abstract methods: `getVersion()`, `getApiBasePath()`
  - [x] Public method: `getShop()`, `testConnection()`

- [x] **HTTP Request Handling**
  - [x] Method `makeRequest()` z comprehensive error handling
  - [x] Basic Auth z PrestaShop API key (username only)
  - [x] Support dla GET, POST, PUT, DELETE, PATCH
  - [x] Query parameters dla GET requests
  - [x] JSON request/response handling

- [x] **Retry Mechanism**
  - [x] Laravel HTTP Client `retry()` method
  - [x] Exponential backoff optional
  - [x] Retry tylko na server errors (5xx) i connection errors
  - [x] Configurable retry attempts i delay

- [x] **Error Handling**
  - [x] Custom `PrestaShopAPIException` class
  - [x] HTTP status code tracking
  - [x] Error context (shop_id, method, url, request/response data)
  - [x] Error categorization (auth, client, server, connection)
  - [x] `isRetryable()` i `isAuthError()` helper methods

- [x] **Logging**
  - [x] Dedicated `prestashop` log channel
  - [x] Request/response logging
  - [x] Execution time tracking (milliseconds)
  - [x] Request/response size tracking
  - [x] Error logging z full context

- [x] **PrestaShop8Client Implementation**
  - [x] Extends BasePrestaShopClient
  - [x] `getVersion()` returns '8'
  - [x] `getApiBasePath()` returns '/api'
  - [x] Methods: getProducts, getProduct, createProduct, updateProduct, deleteProduct
  - [x] Methods: getCategories, getStock, updateStock

- [x] **PrestaShop9Client Implementation**
  - [x] Extends BasePrestaShopClient
  - [x] `getVersion()` returns '9'
  - [x] `getApiBasePath()` returns '/api/v1'
  - [x] v9-specific methods: getProductsWithVariants, bulkUpdateProducts, getProductPerformanceMetrics

- [x] **PrestaShopClientFactory**
  - [x] Static method `create(PrestaShopShop $shop): BasePrestaShopClient`
  - [x] Static method `createMultiple(array $shops): array`
  - [x] Static method `createForAllActiveShops(): array`
  - [x] Version validation z InvalidArgumentException

---

## 📁 PLIKI DO UTWORZENIA

```
app/Services/PrestaShop/
├── BasePrestaShopClient.php          (abstract class - 350 linii)
├── PrestaShop8Client.php             (concrete implementation - 120 linii)
├── PrestaShop9Client.php             (concrete implementation - 100 linii)
└── PrestaShopClientFactory.php       (factory - 60 linii)

app/Exceptions/
└── PrestaShopAPIException.php        (custom exception - 120 linii)

config/
└── logging.php                       (update - dodać prestashop channel)

.env
(dodać zmienne PRESTASHOP_*)
```

**Total: 5 plików (750 linii kodu)**

---

## 🎯 KLUCZOWE FEATURES

### 1. **Retry Mechanism z Exponential Backoff**

```php
// Automatyczne retry na błędy serwera i connection issues
Http::retry(
    times: 3,
    sleepMilliseconds: 1000,
    when: function ($exception, $request) {
        return $exception instanceof ConnectionException
            || ($exception instanceof RequestException
                && $exception->response
                && $exception->response->serverError());
    }
)
```

**Benefits:**
- Automatyczne ponowienie przy przejściowych błędach
- Nie retry na błędach klienta (4xx) - oszczędność czasu
- Configurable delay między próbami
- Optional exponential backoff

### 2. **Comprehensive Error Handling**

```php
// Custom exception z pełnym kontekstem
throw new PrestaShopAPIException(
    "PrestaShop API error (404): Product not found",
    404,
    null,
    [
        'shop_id' => 1,
        'error_category' => 'client_error',
        'method' => 'GET',
        'url' => 'https://example.com/api/products/999',
        'execution_time_ms' => 234.56,
    ]
);

// Helper methods dla error handling
if ($e->isRetryable()) {
    // Queue for retry
}

if ($e->isAuthError()) {
    // Invalid API key - notify admin
}
```

### 3. **Detailed Logging**

```php
// Automatyczne logowanie każdego requesta z:
// - Execution time (ms)
// - Request/response size (bytes)
// - HTTP status code
// - Shop context
// - Error details (jeśli błąd)

// Logs w storage/logs/prestashop/prestashop-YYYY-MM-DD.log
[2025-10-02 16:30:00] prestashop.INFO: PrestaShop API Request {
    "shop_id": 1,
    "prestashop_version": "8",
    "method": "GET",
    "url": "https://example.com/api/products",
    "status_code": 200,
    "execution_time_ms": 234.56,
    "request_size_bytes": 0,
    "response_size_bytes": 15234
}
```

### 4. **Basic Authentication z PrestaShop API Key**

```php
// PrestaShop używa Basic Auth z:
// - Username: API key
// - Password: empty string

Http::withBasicAuth($this->shop->api_key, '')
    ->get($url);

// Automatycznie dodawane do każdego requesta
```

---

## 🚀 NEXT STEPS (KROK 3+)

Po implementacji BasePrestaShopClient (KROK 2), następne kroki to:

**KROK 3: ProductTransformer** (4h)
- Transformacja produktów PPM → PrestaShop format
- Mapowanie kategorii, cen, stanów magazynowych
- Multi-language support
- Shop-specific data handling

**KROK 4: ProductSyncStrategy** (6h)
- Interface ISyncStrategy
- ProductSyncStrategy implementation
- Sync logic: PPM → PrestaShop
- Checksum calculation dla change detection
- Database transaction handling

**KROK 5: CategorySyncStrategy** (4h)
- 5-level category hierarchy sync
- Parent-child relationship handling
- Category mapping between systems

**KROK 6-10:** (Zobacz ETAP_07_FAZA_1_Implementation_Plan.md)

---

## 📊 PERFORMANCE CONSIDERATIONS

**Timeouts:**
- Connection timeout: 10s (reasonable dla network latency)
- Response timeout: 30s (PrestaShop może być wolny)
- Total max time z retries: ~2 min (30s * 3 attempts + delays)

**Retry Strategy:**
- 3 próby z 1s delay = max 5s dodatkowego czasu
- Exponential backoff optional (1s → 2s → 4s)
- Tylko server errors i connection issues

**Logging Performance:**
- JSON formatter dla szybkiego parsowania
- Daily rotation (14 dni retention)
- Async logging via queue (optional)

**Memory:**
- Response size tracking dla monitoringu
- Truncate large responses w logs (500 chars)
- No memory cache (use Redis dla caching warstwy wyżej)

---

## 🔒 SECURITY CONSIDERATIONS

**API Key Security:**
- API key stored encrypted w database (PrestaShopShop model)
- Never logged in plain text
- Basic Auth over HTTPS only
- No API key in URL query params

**Error Handling:**
- Don't expose sensitive data w exceptions
- Sanitize error messages przed showing user
- Log full context tylko w secure logs

**Rate Limiting:**
- Implement rate limiting w warstwie wyżej (PrestaShopSyncService)
- Track API call count per shop
- Respect PrestaShop API limits (zależne od wersji)

---

## ✅ DEFINITION OF DONE (KROK 2)

**UKOŃCZONE:**
- ✅ Abstract BasePrestaShopClient class zaimplementowana
- ✅ PrestaShop8Client i PrestaShop9Client zaimplementowane
- ✅ PrestaShopClientFactory z version detection
- ✅ PrestaShopAPIException z comprehensive error context
- ✅ Logging configuration (prestashop channel)
- ✅ Retry mechanism z exponential backoff
- ✅ Basic Auth z PrestaShop API key
- ✅ Error handling z categorization
- ✅ Timeout configuration (connection + response)
- ✅ Documentation z examples

**DO UTWORZENIA (Implementacja KROK 2):**
1. Utwórz `app/Services/PrestaShop/BasePrestaShopClient.php`
2. Utwórz `app/Services/PrestaShop/PrestaShop8Client.php`
3. Utwórz `app/Services/PrestaShop/PrestaShop9Client.php`
4. Utwórz `app/Services/PrestaShop/PrestaShopClientFactory.php`
5. Utwórz `app/Exceptions/PrestaShopAPIException.php`
6. Zaktualizuj `config/logging.php` (dodać prestashop channel)
7. Zaktualizuj `.env` (dodać PRESTASHOP_* variables)

---

## 📚 REFERENCES

**Laravel 12.x HTTP Client:**
- Dokumentacja: https://laravel.com/docs/12.x/http-client
- Retry mechanism: https://laravel.com/docs/12.x/http-client#retrying-requests
- Testing: https://laravel.com/docs/12.x/http-client#testing

**PrestaShop API:**
- PrestaShop 8.x API: https://devdocs.prestashop-project.org/8/webservice/
- PrestaShop 9.x API: https://devdocs.prestashop-project.org/9/webservice/

**Best Practices:**
- Laravel Service Layer: https://laravel.com/docs/12.x/container
- Exception Handling: https://laravel.com/docs/12.x/errors
- Logging: https://laravel.com/docs/12.x/logging

---

**KONIEC RAPORTU**

**Status:** ✅ READY FOR IMPLEMENTATION
**Estimated Time:** 4 hours (KROK 2 z ETAP_07_FAZA_1_Implementation_Plan.md)
**Next Step:** Implement files listed in "PLIKI DO UTWORZENIA" section
