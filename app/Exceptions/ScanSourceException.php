<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * ScanSourceException
 *
 * Custom exception for product scan source errors.
 * Thrown when scan source adapters encounter issues
 * with external system connections or data retrieval.
 *
 * @package App\Exceptions
 * @version 1.0.0
 */
class ScanSourceException extends Exception
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
     * Get HTTP status code.
     *
     * @return int HTTP status code or 0 if not HTTP error
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * Get error context.
     *
     * @return array Additional error details
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Check if error is retryable.
     *
     * @return bool True if operation should be retried
     */
    public function isRetryable(): bool
    {
        return $this->httpStatusCode >= 500
            || $this->httpStatusCode === 0
            || $this->httpStatusCode === 429;
    }

    /**
     * Check if error is authentication related.
     *
     * @return bool True if auth error
     */
    public function isAuthError(): bool
    {
        return in_array($this->httpStatusCode, [401, 403]);
    }

    /**
     * Convert exception to array for logging.
     *
     * @return array Exception details
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'http_status_code' => $this->httpStatusCode,
            'is_retryable' => $this->isRetryable(),
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
