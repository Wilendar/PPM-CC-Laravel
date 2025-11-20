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
     * Check if this is a 404 Not Found error
     *
     * Used for detecting products deleted from PrestaShop (BUG #8 FIX #1)
     *
     * @return bool True if HTTP 404 error
     */
    public function isNotFound(): bool
    {
        return $this->httpStatusCode === 404;
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
