<?php

namespace App\Http\Middleware;

use App\Services\OAuthSecurityService;
use App\Services\OAuthSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * OAuth Security Middleware
 * 
 * FAZA D: OAuth2 + Advanced Features
 * Middleware dla OAuth security validation and monitoring
 * 
 * Features:
 * - Rate limiting for OAuth operations
 * - Session security validation
 * - Suspicious activity detection
 * - Enhanced verification enforcement
 * - Token refresh handling
 */
class OAuthSecurityMiddleware
{
    public function __construct(
        protected OAuthSecurityService $securityService,
        protected OAuthSessionService $sessionService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $operation = 'general'): Response
    {
        // Skip security checks for non-OAuth routes
        if (!$this->isOAuthRoute($request)) {
            return $next($request);
        }

        try {
            // Apply rate limiting
            if (!$this->checkRateLimit($request, $operation)) {
                return $this->rateLimitExceeded($request);
            }
            
            // Record attempt for rate limiting
            $this->securityService->recordAttempt($operation, $this->getIdentifier($request));
            
            // If user is authenticated, perform additional checks
            if (Auth::check()) {
                $user = Auth::user();
                $provider = $this->extractProvider($request);
                
                // Skip if user is not OAuth user
                if (!$user->isOAuthUser() || !$provider) {
                    return $next($request);
                }
                
                // Check if account is locked
                if ($user->isOAuthLocked()) {
                    return $this->accountLocked($request);
                }
                
                // Validate session security
                $securityValidation = $this->sessionService->validateSessionSecurity($user, $provider);
                if (!$securityValidation['valid']) {
                    return $this->sessionSecurityFailed($request, $securityValidation);
                }
                
                // Check for suspicious activity
                $suspiciousIndicators = $this->securityService->detectSuspiciousActivity($user, $provider, [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'route' => $request->route()?->getName(),
                    'operation' => $operation
                ]);
                
                if (!empty($suspiciousIndicators)) {
                    $this->securityService->handleSecurityIncident($user, $provider, $suspiciousIndicators);
                    
                    // Block critical incidents immediately
                    $severity = $this->calculateSeverity($suspiciousIndicators);
                    if ($severity === 'critical') {
                        return $this->securityIncidentBlocked($request);
                    }
                }
                
                // Check if enhanced verification is required
                if ($this->securityService->requiresEnhancedVerification($user, $provider)) {
                    return $this->enhancedVerificationRequired($request);
                }
                
                // Refresh token if needed
                if (!$this->sessionService->refreshTokenIfNeeded($user)) {
                    Log::warning('OAuth token refresh failed', [
                        'user_id' => $user->id,
                        'provider' => $provider,
                        'route' => $request->route()?->getName()
                    ]);
                }
                
                // Update session activity
                $this->sessionService->updateSessionActivity($user, $provider);
            }
            
            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('OAuth security middleware error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'route' => $request->route()?->getName(),
                'operation' => $operation,
                'ip' => $request->ip()
            ]);
            
            // Allow request to continue on middleware error (fail open)
            return $next($request);
        }
    }

    /**
     * Check if this is an OAuth-related route.
     */
    protected function isOAuthRoute(Request $request): bool
    {
        $path = $request->path();
        
        return str_starts_with($path, 'auth/google') ||
               str_starts_with($path, 'auth/microsoft') ||
               str_starts_with($path, 'auth/oauth') ||
               str_contains($path, 'oauth');
    }

    /**
     * Check rate limiting.
     */
    protected function checkRateLimit(Request $request, string $operation): bool
    {
        $identifier = $this->getIdentifier($request);
        
        return !$this->securityService->checkRateLimit($operation, $identifier);
    }

    /**
     * Get identifier for rate limiting.
     */
    protected function getIdentifier(Request $request): string
    {
        // Use IP address for anonymous requests, user ID for authenticated
        if (Auth::check()) {
            return 'user:' . Auth::id();
        }
        
        return 'ip:' . $request->ip();
    }

    /**
     * Extract OAuth provider from request.
     */
    protected function extractProvider(Request $request): ?string
    {
        $path = $request->path();
        
        if (str_contains($path, 'google')) {
            return 'google';
        }
        
        if (str_contains($path, 'microsoft')) {
            return 'microsoft';
        }
        
        // Try to get from route parameters
        $route = $request->route();
        if ($route && $route->hasParameter('provider')) {
            return $route->parameter('provider');
        }
        
        // Try to get from authenticated user
        if (Auth::check()) {
            return Auth::user()->oauth_provider;
        }
        
        return null;
    }

    /**
     * Calculate severity from indicators.
     */
    protected function calculateSeverity(array $indicators): string
    {
        $highCount = 0;
        
        foreach ($indicators as $indicator) {
            if ($indicator['severity'] === 'high') {
                $highCount++;
            }
        }
        
        return $highCount >= 2 ? 'critical' : 'suspicious';
    }

    /**
     * Handle rate limit exceeded.
     */
    protected function rateLimitExceeded(Request $request): Response
    {
        Log::warning('OAuth rate limit exceeded', [
            'ip' => $request->ip(),
            'user_id' => Auth::id(),
            'route' => $request->route()?->getName(),
            'user_agent' => $request->userAgent()
        ]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => 'Zbyt wiele prób. Spróbuj ponownie za chwilę.',
                'retry_after' => 60
            ], 429);
        }
        
        return redirect()
            ->route('login')
            ->withErrors(['oauth' => 'Zbyt wiele prób logowania. Spróbuj ponownie za chwilę.']);
    }

    /**
     * Handle account locked.
     */
    protected function accountLocked(Request $request): Response
    {
        $user = Auth::user();
        
        Log::info('OAuth access attempt on locked account', [
            'user_id' => $user->id,
            'locked_until' => $user->oauth_locked_until,
            'ip' => $request->ip(),
            'route' => $request->route()?->getName()
        ]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Account locked',
                'message' => 'Konto jest tymczasowo zablokowane ze względów bezpieczeństwa.',
                'locked_until' => $user->oauth_locked_until?->toISOString()
            ], 403);
        }
        
        return redirect()
            ->route('login')
            ->withErrors(['oauth' => 'Konto jest tymczasowo zablokowane ze względów bezpieczeństwa.']);
    }

    /**
     * Handle session security failure.
     */
    protected function sessionSecurityFailed(Request $request, array $validation): Response
    {
        Log::warning('OAuth session security validation failed', [
            'user_id' => Auth::id(),
            'validation' => $validation,
            'ip' => $request->ip(),
            'route' => $request->route()?->getName()
        ]);
        
        // Log out user for security
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Session security failed',
                'message' => 'Sesja nie przeszła weryfikacji bezpieczeństwa. Zaloguj się ponownie.',
                'issues' => $validation['issues']
            ], 401);
        }
        
        return redirect()
            ->route('login')
            ->withErrors(['oauth' => 'Sesja nie przeszła weryfikacji bezpieczeństwa. Zaloguj się ponownie.']);
    }

    /**
     * Handle security incident blocked.
     */
    protected function securityIncidentBlocked(Request $request): Response
    {
        Log::critical('OAuth security incident blocked access', [
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'route' => $request->route()?->getName(),
            'user_agent' => $request->userAgent()
        ]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Security incident',
                'message' => 'Dostęp został zablokowany ze względów bezpieczeństwa.',
                'incident_id' => \Illuminate\Support\Str::uuid()
            ], 403);
        }
        
        return redirect()
            ->route('login')
            ->withErrors(['oauth' => 'Dostęp został zablokowany ze względów bezpieczeństwa.']);
    }

    /**
     * Handle enhanced verification required.
     */
    protected function enhancedVerificationRequired(Request $request): Response
    {
        Log::info('OAuth enhanced verification required', [
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'route' => $request->route()?->getName()
        ]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Enhanced verification required',
                'message' => 'Wymagana jest dodatkowa weryfikacja.',
                'verification_url' => route('auth.oauth.verification.show')
            ], 403);
        }
        
        return redirect()
            ->route('auth.oauth.verification.show')
            ->with('info', 'Ze względów bezpieczeństwa wymagana jest dodatkowa weryfikacja.');
    }
}