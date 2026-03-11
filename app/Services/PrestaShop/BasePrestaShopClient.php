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
    public function makeRequest(
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
                'Output-Format' => 'JSON', // CRITICAL: PrestaShop header for JSON output
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
            // Note: For GET, we already built query params into URL, so don't pass additional query array
            // SPECIAL CASE: If $options['body'] exists, send raw body (for XML/custom formats)
            if (isset($options['body'])) {
                $rawBody = $options['body'];
                $contentType = $options['headers']['Content-Type'] ?? 'application/xml';

                $response = match(strtoupper($method)) {
                    'POST' => $client->withBody($rawBody, $contentType)->post($url),
                    'PUT' => $client->withBody($rawBody, $contentType)->put($url),
                    'PATCH' => $client->withBody($rawBody, $contentType)->patch($url),
                    default => throw new \InvalidArgumentException("Raw body only supported for POST/PUT/PATCH, got: {$method}")
                };
            } else {
                // Normal JSON handling
                $response = match(strtoupper($method)) {
                    'GET' => $client->get($url),
                    'POST' => $client->post($url, $data),
                    'PUT' => $client->put($url, $data),
                    'DELETE' => $client->delete($url),
                    'PATCH' => $client->patch($url, $data),
                    default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
                };
            }

            $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

            // Log successful request
            $this->logRequest($method, $url, $data, $response, $executionTime, 'success');

            // Check if response is successful (2xx status code)
            if ($response->successful()) {
                // ETAP_07 FIX (2025-11-13): Detect HTML error pages before parsing as JSON/XML
                $contentType = $response->header('Content-Type');
                $body = $response->body();

                // If PrestaShop returns HTML error page instead of XML/JSON (happens on 500 errors)
                if (str_contains($contentType ?? '', 'text/html') ||
                    (stripos($body, '<!DOCTYPE') === 0) ||
                    (stripos($body, '<html') === 0)) {

                    Log::warning('PrestaShop returned HTML error page instead of XML/JSON', [
                        'shop_id' => $this->shop->id,
                        'method' => $method,
                        'url' => $url,
                        'content_type' => $contentType,
                        'body_preview' => substr($body, 0, 500),
                    ]);

                    throw new PrestaShopAPIException(
                        "PrestaShop returned HTML error page (likely internal server error). Check PrestaShop logs for details.",
                        500,
                        null,
                        [
                            'shop_id' => $this->shop->id,
                            'shop_name' => $this->shop->name,
                            'method' => $method,
                            'url' => $url,
                            'content_type' => $contentType,
                            'html_preview' => substr($body, 0, 1000),
                        ]
                    );
                }

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
        } catch (PrestaShopAPIException $e) {
            // FIX 2025-12-16: Don't wrap PrestaShopAPIException again - re-throw as-is
            // This allows deprecation warnings and other API errors to propagate correctly
            throw $e;
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

        // FIX (2025-12-02): Ensure basePath has leading slash
        if (!str_starts_with($basePath, '/')) {
            $basePath = '/' . $basePath;
        }

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

        // FIX (2025-12-05): Handle deprecation warnings gracefully
        // PrestaShop modules using deprecated hooks cause 500 errors even when operation succeeds
        // Error #16384 = E_USER_DEPRECATED
        if ($this->isDeprecationWarning($responseBody, $errorMessage)) {
            Log::warning('[PrestaShop API] Deprecation warning detected (operation may have succeeded)', [
                'shop_id' => $this->shop->id,
                'method' => $method,
                'url' => $url,
                'deprecation_message' => $errorMessage,
            ]);

            // For PUT/POST operations, the data might have been saved despite the warning
            // We'll throw a special exception that can be caught and handled
            throw new PrestaShopAPIException(
                "PrestaShop deprecation warning (operation may have succeeded): {$errorMessage}",
                $statusCode,
                null,
                [
                    'shop_id' => $this->shop->id,
                    'shop_name' => $this->shop->name,
                    'error_category' => 'deprecation_warning',
                    'is_deprecation' => true,
                    'method' => $method,
                    'url' => $url,
                    'request_data' => $data,
                    'response_body' => $responseBody,
                    'execution_time_ms' => $executionTime,
                ]
            );
        }

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
     * Check if error is a deprecation warning (not a real failure)
     * PrestaShop modules using deprecated hooks cause 500 errors but operation may succeed
     *
     * @param string $responseBody Response body
     * @param string $errorMessage Parsed error message
     * @return bool True if this is a deprecation warning
     */
    protected function isDeprecationWarning(string $responseBody, string $errorMessage): bool
    {
        // Error #16384 is E_USER_DEPRECATED in PHP
        $deprecationPatterns = [
            'error #16384',
            'E_USER_DEPRECATED',
            'is deprecated',
            'please use',
            'deprecated, please use',
        ];

        $combined = strtolower($responseBody . ' ' . $errorMessage);

        foreach ($deprecationPatterns as $pattern) {
            if (str_contains($combined, strtolower($pattern))) {
                return true;
            }
        }

        return false;
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

        // FIXED: Use default log channel (prestashop channel not configured)
        Log::{$logLevel}("PrestaShop API Request", $logData);
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
     * CRITICAL FIX: Always adds output_format=JSON to force JSON response
     * PrestaShop API returns XML by default, even with Accept: application/json header
     *
     * @param array $filters Associative array of filter parameters
     * @return string Query string (e.g., 'output_format=JSON&limit=10&sort=name')
     */
    protected function buildQueryParams(array $filters): string
    {
        // CRITICAL: Force JSON output format
        // PrestaShop ignores Accept header and returns XML by default
        $filters['output_format'] = 'JSON';

        return http_build_query($filters);
    }

    /**
     * Get specific prices (price rules/discounts) for product
     *
     * PrestaShop API endpoint: GET /specific_prices?filter[id_product]={id}&display=full
     *
     * Returns specific_prices data including:
     * - reduction: Discount amount (0.15 = 15% or 5.00 = 5 PLN)
     * - reduction_type: "percentage" or "amount"
     * - id_group: Customer group ID (0 = all groups)
     * - id_shop: Shop ID
     * - price: Override price (or -1 = use base price)
     * - from/to: Date validity range
     *
     * Used by PrestaShopPriceImporter to import prices from PrestaShop.
     *
     * @param int $productId PrestaShop product ID
     * @return array Specific prices data (returns empty array on error/404)
     */
    public function getSpecificPrices(int $productId): array
    {
        try {
            $queryParams = $this->buildQueryParams([
                'filter[id_product]' => $productId,
                'display' => 'full',
            ]);

            $response = $this->makeRequest('GET', "specific_prices?{$queryParams}");

            // PrestaShop API returns: {"specific_prices": [{"id": 1, ...}, ...]}
            return $response;

        } catch (PrestaShopAPIException $e) {
            // Graceful handling: If product has no specific prices, PrestaShop returns 404
            // This is expected behavior, not an error
            if ($e->isNotFound()) {
                Log::info('No specific prices found for product', [
                    'product_id' => $productId,
                    'shop_id' => $this->shop->id,
                ]);
                return ['specific_prices' => []];
            }

            // For other errors, log warning and return empty array
            Log::warning('Failed to fetch specific prices', [
                'product_id' => $productId,
                'shop_id' => $this->shop->id,
                'http_status' => $e->getHttpStatusCode(),
                'error' => $e->getMessage(),
            ]);

            return ['specific_prices' => []];
        }
    }

    /**
     * Get tax rule groups from PrestaShop
     *
     * PrestaShop API endpoint: GET /tax_rule_groups?display=full
     *
     * FAZA 5.1 - Tax Rules UI Enhancement System
     * Used by AddShop/EditShop forms to populate tax rules dropdown
     *
     * Returns tax_rule_groups data including:
     * - id: Tax rule group ID (e.g., 6)
     * - name: Tax rule group name (e.g., "PL Standard Rate (23%)")
     * - active: Active status (1 or 0)
     * - rate: Tax rate (extracted from name if possible, null otherwise)
     *
     * Note: Rate extraction is basic (from group name). For accurate rates,
     * you would need to fetch /tax_rules and join with /taxes table.
     * For UI selection, group name is sufficient.
     *
     * @return array Tax rule groups in standardized format
     * @throws PrestaShopAPIException
     */
    abstract public function getTaxRuleGroups(): array;

    /**
     * Convert array to PrestaShop XML format
     *
     * CRITICAL FIX (2025-11-14): Proper PrestaShop XML implementation
     * COMPLIANCE: prestashop-xml-integration skill
     *
     * PREVIOUS ISSUE: Basic implementation missing:
     * - CDATA wrapping (used htmlspecialchars instead)
     * - PrestaShop namespace (xmlns:xlink)
     * - Multilang fields support [['id' => 1, 'value' => '...']]
     * - Singularization (categories → category)
     *
     * CURRENT IMPLEMENTATION:
     * - ✅ CDATA wrapping for all values
     * - ✅ Proper namespace: <prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
     * - ✅ Multilang fields: [['id' => 1, 'value' => 'Text']]
     * - ✅ Indexed arrays with singularization
     * - ✅ Nested associative arrays
     *
     * @param array $data Data to convert (e.g., ['product' => [...]])
     * @return string XML string compliant with PrestaShop Web Services API
     */
    public function arrayToXml(array $data): string
    {
        // Create root element with PrestaShop namespace
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<prestashop xmlns:xlink="http://www.w3.org/1999/xlink"></prestashop>'
        );

        // Build XML from array recursively
        $this->buildXmlFromArray($data, $xml);

        return $xml->asXML();
    }

    /**
     * Recursively build XML from array
     *
     * Handles:
     * - Multilang fields: [['id' => 1, 'value' => 'Text']]
     * - Indexed arrays: [['id' => 1], ['id' => 2]] → singularized element names
     * - Nested associative arrays
     * - Simple values: wrapped in CDATA
     *
     * @param array $data Data to convert
     * @param \SimpleXMLElement $xml Parent XML element
     * @return void
     */
    protected function buildXmlFromArray(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue; // Skip null values
            }

            if (is_array($value)) {
                // Multilang field: [['id' => 1, 'value' => 'Text']]
                if ($this->isMultilangField($value)) {
                    $fieldElement = $xml->addChild($key);
                    foreach ($value as $langData) {
                        $langElement = $fieldElement->addChild('language');
                        $langElement->addAttribute('id', $langData['id']);
                        $this->addCDataChild($langElement, $langData['value']);
                    }
                }
                // Indexed array: [['id' => 1], ['id' => 2]]
                elseif ($this->isIndexedArray($value)) {
                    $containerElement = $xml->addChild($key);
                    $singularKey = $this->singularize($key);
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $itemElement = $containerElement->addChild($singularKey);
                            $this->buildXmlFromArray($item, $itemElement);
                        } else {
                            $this->addCDataChild($containerElement->addChild($singularKey), $item);
                        }
                    }
                }
                // Nested associative array
                else {
                    $childElement = $xml->addChild($key);
                    $this->buildXmlFromArray($value, $childElement);
                }
            }
            // Simple values - wrap in CDATA
            else {
                $this->addCDataChild($xml->addChild($key), $value);
            }
        }
    }

    /**
     * Add CDATA child to XML element
     *
     * PrestaShop requires CDATA wrapping for ALL text values
     *
     * @param \SimpleXMLElement $element XML element
     * @param mixed $value Value to wrap in CDATA
     * @return void
     */
    protected function addCDataChild(\SimpleXMLElement $element, $value): void
    {
        $node = dom_import_simplexml($element);
        $doc = $node->ownerDocument;
        $node->appendChild($doc->createCDATASection((string) $value));
    }

    /**
     * Check if array is multilang field
     *
     * Multilang format: [['id' => 1, 'value' => 'Text'], ['id' => 2, 'value' => 'Tekst']]
     *
     * @param array $data Array to check
     * @return bool True if multilang field
     */
    protected function isMultilangField(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $first = reset($data);
        return is_array($first) && isset($first['id']) && isset($first['value']);
    }

    /**
     * Check if array is indexed (numeric keys 0, 1, 2, ...)
     *
     * @param array $data Array to check
     * @return bool True if indexed array
     */
    protected function isIndexedArray(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        return array_keys($data) === range(0, count($data) - 1);
    }

    /**
     * Singularize plural words for XML elements
     *
     * PrestaShop expects singular element names inside plural containers:
     * - categories → category
     * - products → product
     * - associations → association
     *
     * @param string $word Plural word
     * @return string Singular word
     */
    protected function singularize(string $word): string
    {
        // categories → category
        if (str_ends_with($word, 'ies')) {
            return substr($word, 0, -3) . 'y';
        }

        // products → product, associations → association
        if (str_ends_with($word, 's')) {
            return substr($word, 0, -1);
        }

        return $word;
    }
}
