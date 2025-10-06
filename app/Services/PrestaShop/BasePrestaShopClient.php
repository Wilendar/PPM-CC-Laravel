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
            $response = match(strtoupper($method)) {
                'GET' => $client->get($url),
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
}
