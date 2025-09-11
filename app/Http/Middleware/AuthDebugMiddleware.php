<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * AuthDebugMiddleware - Comprehensive authentication debugging and logging
 * 
 * This middleware provides detailed logging for authentication-related requests
 * to help diagnose login issues in the PPM-CC-Laravel application.
 * 
 * Features:
 * - Request/Response logging
 * - Session tracking
 * - Database query monitoring
 * - Error capture and analysis
 * - Performance monitoring
 * 
 * @author Claude Code Deployment Specialist
 * @version 1.0
 */
class AuthDebugMiddleware
{
    private $startTime;
    private $queryCount = 0;
    private $queries = [];

    public function handle(Request $request, Closure $next)
    {
        $this->startTime = microtime(true);
        
        // Start query logging
        $this->startQueryLogging();
        
        // Log incoming request
        $this->logRequest($request);
        
        try {
            // Process request
            $response = $next($request);
            
            // Log successful response
            $this->logResponse($request, $response, 'SUCCESS');
            
            return $response;
            
        } catch (\Exception $e) {
            // Log error response
            $this->logException($request, $e);
            
            throw $e;
        } finally {
            // Stop query logging
            $this->stopQueryLogging();
            
            // Log performance metrics
            $this->logPerformance($request);
        }
    }

    /**
     * Log incoming request details
     */
    private function logRequest(Request $request)
    {
        $logData = [
            'timestamp' => now()->toISOString(),
            'type' => 'AUTH_REQUEST',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->headers->get('referer'),
            'session_id' => session()->getId(),
            'csrf_token' => $request->header('X-CSRF-TOKEN') ?: $request->input('_token'),
            'has_remember_token' => $request->hasCookie('remember_token'),
            'auth_check' => Auth::check(),
            'auth_user_id' => Auth::id(),
            'headers' => $this->filterSensitiveHeaders($request->headers->all()),
        ];
        
        // Log form data for POST requests (with password filtering)
        if ($request->isMethod('POST')) {
            $formData = $request->all();
            if (isset($formData['password'])) {
                $formData['password'] = '[HIDDEN]';
            }
            $logData['form_data'] = $formData;
        }
        
        // Log session data
        $logData['session_data'] = [
            'session_started' => session()->isStarted(),
            'session_id' => session()->getId(),
            'session_name' => session()->getName(),
            'session_data_keys' => array_keys(session()->all()),
            'flash_data' => session()->getFlashBag()->peekAll(),
        ];
        
        Log::channel('auth_debug')->info('AUTH_REQUEST', $logData);
    }

    /**
     * Log response details
     */
    private function logResponse(Request $request, $response, string $status)
    {
        $logData = [
            'timestamp' => now()->toISOString(),
            'type' => 'AUTH_RESPONSE',
            'status' => $status,
            'http_status' => $response->getStatusCode(),
            'content_type' => $response->headers->get('content-type'),
            'location' => $response->headers->get('location'),
            'session_id' => session()->getId(),
            'auth_check_after' => Auth::check(),
            'auth_user_id_after' => Auth::id(),
            'response_size' => strlen($response->getContent()),
        ];
        
        // Log cookies set
        if ($response->headers->getCookies()) {
            $cookies = [];
            foreach ($response->headers->getCookies() as $cookie) {
                $cookies[] = [
                    'name' => $cookie->getName(),
                    'domain' => $cookie->getDomain(),
                    'path' => $cookie->getPath(),
                    'secure' => $cookie->isSecure(),
                    'httponly' => $cookie->isHttpOnly(),
                ];
            }
            $logData['cookies_set'] = $cookies;
        }
        
        // For redirects, log the redirect chain
        if ($response->isRedirection()) {
            $logData['redirect_to'] = $response->headers->get('location');
            $logData['redirect_type'] = $response->getStatusCode();
        }
        
        // Check for authentication changes
        if (Auth::check()) {
            $user = Auth::user();
            $logData['authenticated_user'] = [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'roles' => $user->getRoleNames()->toArray(),
                'last_login' => $user->last_login_at ?? null,
            ];
        }
        
        Log::channel('auth_debug')->info('AUTH_RESPONSE', $logData);
    }

    /**
     * Log exceptions and errors
     */
    private function logException(Request $request, \Exception $e)
    {
        $logData = [
            'timestamp' => now()->toISOString(),
            'type' => 'AUTH_EXCEPTION',
            'exception_class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'session_id' => session()->getId(),
            'auth_check' => Auth::check(),
            'auth_user_id' => Auth::id(),
        ];
        
        Log::channel('auth_debug')->error('AUTH_EXCEPTION', $logData);
    }

    /**
     * Start database query logging
     */
    private function startQueryLogging()
    {
        DB::listen(function ($query) {
            $this->queryCount++;
            
            $this->queries[] = [
                'query' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
                'connection' => $query->connectionName,
            ];
            
            // Log individual queries if they're slow or auth-related
            if ($query->time > 100 || $this->isAuthQuery($query->sql)) {
                Log::channel('auth_debug')->debug('SLOW_OR_AUTH_QUERY', [
                    'type' => 'DATABASE_QUERY',
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                    'connection' => $query->connectionName,
                ]);
            }
        });
    }

    /**
     * Stop query logging and summarize
     */
    private function stopQueryLogging()
    {
        if ($this->queryCount > 0) {
            $totalTime = array_sum(array_column($this->queries, 'time'));
            
            Log::channel('auth_debug')->info('DATABASE_SUMMARY', [
                'type' => 'DATABASE_SUMMARY',
                'query_count' => $this->queryCount,
                'total_time_ms' => $totalTime,
                'avg_time_ms' => $totalTime / $this->queryCount,
                'slow_queries' => array_filter($this->queries, fn($q) => $q['time'] > 50),
            ]);
        }
    }

    /**
     * Log performance metrics
     */
    private function logPerformance(Request $request)
    {
        $endTime = microtime(true);
        $executionTime = ($endTime - $this->startTime) * 1000; // Convert to ms
        
        $logData = [
            'timestamp' => now()->toISOString(),
            'type' => 'PERFORMANCE',
            'execution_time_ms' => round($executionTime, 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'database_queries' => $this->queryCount,
            'database_time_ms' => array_sum(array_column($this->queries, 'time')),
        ];
        
        // Determine performance category
        if ($executionTime > 2000) {
            $logData['performance_category'] = 'SLOW';
            Log::channel('auth_debug')->warning('SLOW_REQUEST', $logData);
        } elseif ($executionTime > 1000) {
            $logData['performance_category'] = 'MODERATE';
            Log::channel('auth_debug')->info('MODERATE_REQUEST', $logData);
        } else {
            $logData['performance_category'] = 'FAST';
            Log::channel('auth_debug')->info('FAST_REQUEST', $logData);
        }
    }

    /**
     * Check if query is authentication-related
     */
    private function isAuthQuery(string $sql): bool
    {
        $authTables = ['users', 'password_resets', 'sessions', 'roles', 'permissions'];
        $sqlLower = strtolower($sql);
        
        foreach ($authTables as $table) {
            if (strpos($sqlLower, $table) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Filter sensitive headers
     */
    private function filterSensitiveHeaders(array $headers): array
    {
        $filtered = [];
        $sensitiveHeaders = ['authorization', 'cookie', 'set-cookie'];
        
        foreach ($headers as $name => $values) {
            if (in_array(strtolower($name), $sensitiveHeaders)) {
                $filtered[$name] = '[FILTERED]';
            } else {
                $filtered[$name] = $values;
            }
        }
        
        return $filtered;
    }
}