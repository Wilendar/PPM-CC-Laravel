<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Custom exception for Subiekt GT REST API errors
 *
 * Provides enhanced error context including:
 * - HTTP status code
 * - Error categorization
 * - Retry logic support
 *
 * @package App\Exceptions
 */
class SubiektApiException extends Exception
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
     * Get connection ID from context
     *
     * @return int|null Connection ID or null if not available
     */
    public function getConnectionId(): ?int
    {
        return $this->context['connection_id'] ?? null;
    }

    /**
     * Get error category
     *
     * @return string|null Error category or null if not categorized
     */
    public function getErrorCategory(): ?string
    {
        // Categorize based on HTTP status code
        return match (true) {
            $this->httpStatusCode === 401 => 'authentication_failed',
            $this->httpStatusCode === 403 => 'authorization_failed',
            $this->httpStatusCode === 404 => 'not_found',
            $this->httpStatusCode === 429 => 'rate_limited',
            $this->httpStatusCode >= 500 => 'server_error',
            $this->httpStatusCode === 0 => 'connection_failed',
            default => $this->context['error_category'] ?? null,
        };
    }

    /**
     * Check if error is retryable
     *
     * Determines if the operation should be retried based on error type
     *
     * @return bool True if error is retryable (5xx server errors, connection issues, rate limits)
     */
    public function isRetryable(): bool
    {
        // Retry on server errors (5xx), connection issues, and rate limits
        return $this->httpStatusCode >= 500
            || $this->httpStatusCode === 0
            || $this->httpStatusCode === 429
            || in_array($this->getErrorCategory(), ['server_error', 'connection_failed', 'rate_limited']);
    }

    /**
     * Check if error is authentication/authorization related
     *
     * @return bool True if auth error (401, 403)
     */
    public function isAuthError(): bool
    {
        return in_array($this->httpStatusCode, [401, 403]);
    }

    /**
     * Check if this is a 404 Not Found error
     *
     * @return bool True if HTTP 404 error
     */
    public function isNotFound(): bool
    {
        return $this->httpStatusCode === 404;
    }

    /**
     * Check if rate limited
     *
     * @return bool True if HTTP 429 error
     */
    public function isRateLimited(): bool
    {
        return $this->httpStatusCode === 429;
    }

    /**
     * Get retry-after time in seconds (if rate limited)
     *
     * @return int|null Seconds to wait before retry, or null if not applicable
     */
    public function getRetryAfter(): ?int
    {
        return $this->context['retry_after'] ?? null;
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
            'connection_id' => $this->getConnectionId(),
            'is_retryable' => $this->isRetryable(),
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
    }
}
