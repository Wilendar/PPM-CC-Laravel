<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiUsageLog;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiMonitoringMiddleware
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $suspicious = false;
        $securityNotes = '';

        // Detect suspicious patterns
        $suspiciousChecks = $this->detectSuspiciousActivity($request);
        if ($suspiciousChecks['suspicious']) {
            $suspicious = true;
            $securityNotes = $suspiciousChecks['notes'];
        }

        // Process the request
        $response = $next($request);
        
        $endTime = microtime(true);
        $responseTimeMs = round(($endTime - $startTime) * 1000, 2);

        // Log API usage
        try {
            $this->logApiUsage($request, $response, $responseTimeMs, $suspicious, $securityNotes);
        } catch (\Exception $e) {
            Log::error('Failed to log API usage', [
                'error' => $e->getMessage(),
                'endpoint' => $request->path(),
            ]);
        }

        return $response;
    }

    /**
     * Log API usage to database
     */
    protected function logApiUsage(
        Request $request, 
        Response $response, 
        float $responseTimeMs, 
        bool $suspicious, 
        string $securityNotes
    ): void {
        // Get rate limiting info if available
        $rateLimitRemaining = $response->headers->get('X-RateLimit-Remaining');
        $rateLimited = $response->getStatusCode() === 429;

        // Prepare request parameters (excluding sensitive data)
        $requestParams = $this->sanitizeRequestParams($request->all());

        // Get API key info if available
        $apiKeyId = $request->header('X-API-Key-ID');

        ApiUsageLog::create([
            'endpoint' => '/' . $request->path(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'api_key_id' => $apiKeyId,
            'response_code' => $response->getStatusCode(),
            'response_time_ms' => $responseTimeMs,
            'response_size_bytes' => strlen($response->getContent()),
            'rate_limit_remaining' => $rateLimitRemaining ? (int)$rateLimitRemaining : null,
            'rate_limited' => $rateLimited,
            'request_params' => $requestParams,
            'response_headers' => $this->getResponseHeaders($response),
            'error_message' => $this->getErrorMessage($response),
            'suspicious' => $suspicious,
            'security_notes' => $securityNotes ?: null,
            'requested_at' => now(),
        ]);

        // Send notifications for critical issues
        $this->handleCriticalIssues($request, $response, $responseTimeMs, $suspicious, $securityNotes);
    }

    /**
     * Detect suspicious activity patterns
     */
    protected function detectSuspiciousActivity(Request $request): array
    {
        $suspicious = false;
        $notes = [];

        // Check for high frequency requests from same IP
        $recentRequests = ApiUsageLog::where('ip_address', $request->ip())
            ->where('requested_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentRequests > 100) {
            $suspicious = true;
            $notes[] = "High frequency requests: {$recentRequests} in 5 minutes";
        }

        // Check for suspicious user agents
        $userAgent = $request->userAgent();
        $suspiciousAgents = ['curl', 'wget', 'python-requests', 'bot', 'crawler', 'scraper'];
        
        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                $suspicious = true;
                $notes[] = "Suspicious user agent: {$userAgent}";
                break;
            }
        }

        // Check for SQL injection attempts
        $allInput = json_encode($request->all());
        $sqlPatterns = ['union', 'select', 'drop', 'insert', 'delete', 'update', '--', ';'];
        
        foreach ($sqlPatterns as $pattern) {
            if (stripos($allInput, $pattern) !== false) {
                $suspicious = true;
                $notes[] = "Potential SQL injection attempt detected";
                break;
            }
        }

        // Check for XSS attempts
        $xssPatterns = ['<script', 'javascript:', 'onload=', 'onerror=', 'alert('];
        
        foreach ($xssPatterns as $pattern) {
            if (stripos($allInput, $pattern) !== false) {
                $suspicious = true;
                $notes[] = "Potential XSS attempt detected";
                break;
            }
        }

        // Check for unauthorized endpoint access
        if (!auth()->check() && !$this->isPublicEndpoint($request->path())) {
            $suspicious = true;
            $notes[] = "Unauthorized access attempt to protected endpoint";
        }

        return [
            'suspicious' => $suspicious,
            'notes' => implode('; ', $notes),
        ];
    }

    /**
     * Handle critical API issues
     */
    protected function handleCriticalIssues(
        Request $request, 
        Response $response, 
        float $responseTimeMs, 
        bool $suspicious, 
        string $securityNotes
    ): void {
        // Alert for very slow responses
        if ($responseTimeMs > 10000) {
            $this->notificationService->systemError(
                "Bardzo powolne API",
                "Endpoint {$request->path()} odpowiedział w {$responseTimeMs}ms",
                null,
                [
                    'endpoint' => $request->path(),
                    'response_time' => $responseTimeMs,
                    'ip_address' => $request->ip(),
                ]
            );
        }

        // Alert for suspicious activity
        if ($suspicious) {
            $this->notificationService->securityAlert(
                "Podejrzana aktywność API",
                "Wykryto podejrzaną aktywność na endpoint {$request->path()}: {$securityNotes}",
                null,
                [
                    'endpoint' => $request->path(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'security_notes' => $securityNotes,
                ]
            );
        }

        // Alert for high error rates
        $recentErrors = ApiUsageLog::where('endpoint', '/' . $request->path())
            ->where('requested_at', '>=', now()->subMinutes(10))
            ->where('response_code', '>=', 400)
            ->count();

        $recentTotal = ApiUsageLog::where('endpoint', '/' . $request->path())
            ->where('requested_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recentTotal > 10 && ($recentErrors / $recentTotal) > 0.5) {
            $errorRate = round(($recentErrors / $recentTotal) * 100, 2);
            
            $this->notificationService->systemError(
                "Wysoki wskaźnik błędów API",
                "Endpoint {$request->path()} ma {$errorRate}% błędów w ostatnich 10 minutach",
                null,
                [
                    'endpoint' => $request->path(),
                    'error_rate' => $errorRate,
                    'errors' => $recentErrors,
                    'total_requests' => $recentTotal,
                ]
            );
        }
    }

    /**
     * Sanitize request parameters (remove sensitive data)
     */
    protected function sanitizeRequestParams(array $params): array
    {
        $sensitiveKeys = ['password', 'token', 'api_key', 'secret', 'private_key', 'credit_card'];
        
        foreach ($params as $key => $value) {
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (stripos($key, $sensitiveKey) !== false) {
                    $params[$key] = '[REDACTED]';
                    break;
                }
            }
        }

        return $params;
    }

    /**
     * Get response headers for logging
     */
    protected function getResponseHeaders(Response $response): array
    {
        $headers = [];
        $importantHeaders = [
            'content-type',
            'cache-control',
            'x-ratelimit-limit',
            'x-ratelimit-remaining',
            'x-response-time',
        ];

        foreach ($importantHeaders as $header) {
            if ($response->headers->has($header)) {
                $headers[$header] = $response->headers->get($header);
            }
        }

        return $headers;
    }

    /**
     * Get error message from response
     */
    protected function getErrorMessage(Response $response): ?string
    {
        if ($response->getStatusCode() >= 400) {
            $content = $response->getContent();
            
            // Try to parse JSON error message
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['message'])) {
                return $decoded['message'];
            }
            
            // Return truncated content for non-JSON responses
            return substr($content, 0, 500);
        }

        return null;
    }

    /**
     * Check if endpoint is public (doesn't require authentication)
     */
    protected function isPublicEndpoint(string $path): bool
    {
        $publicEndpoints = [
            'api/health',
            'api/status',
            'api/login',
            'api/register',
            'api/password/reset',
        ];

        foreach ($publicEndpoints as $endpoint) {
            if (str_starts_with($path, $endpoint)) {
                return true;
            }
        }

        return false;
    }
}