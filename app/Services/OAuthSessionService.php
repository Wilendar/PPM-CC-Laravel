<?php

namespace App\Services;

use App\Models\User;
use App\Models\OAuthAuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Exception;

/**
 * OAuth Session Management Service
 * 
 * FAZA D: OAuth2 + Advanced Features
 * Advanced session management dla multi-provider OAuth support
 * 
 * Features:
 * - Multi-provider session synchronization
 * - OAuth token refresh automation
 * - Session security validation
 * - Cross-provider session management
 * - Token expiry handling
 * - Security monitoring
 */
class OAuthSessionService
{
    /**
     * Session cache key prefix.
     */
    protected const SESSION_CACHE_PREFIX = 'oauth_session:';
    
    /**
     * Token refresh cache key prefix.
     */
    protected const TOKEN_CACHE_PREFIX = 'oauth_token:';
    
    /**
     * Security check cache key prefix.
     */
    protected const SECURITY_CACHE_PREFIX = 'oauth_security:';

    /**
     * Initialize OAuth session dla authenticated user.
     */
    public function initializeSession(User $user, string $provider, array $sessionData): void
    {
        try {
            $sessionKey = $this->getSessionKey($user->id, $provider);
            
            $sessionInfo = [
                'user_id' => $user->id,
                'provider' => $provider,
                'oauth_id' => $user->oauth_id,
                'email' => $user->oauth_email,
                'domain' => $user->oauth_domain,
                'initiated_at' => now()->toISOString(),
                'last_activity' => now()->toISOString(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'session_data' => $sessionData,
                'security_flags' => $this->generateSecurityFlags($user, $provider),
            ];
            
            // Store session info w cache
            $sessionLifetime = config('services.oauth.session_lifetime', 120); // minutes
            Cache::put($sessionKey, $sessionInfo, now()->addMinutes($sessionLifetime));
            
            // Log session initialization
            OAuthAuditLog::create([
                'user_id' => $user->id,
                'oauth_provider' => $provider,
                'oauth_action' => 'session.initialized',
                'oauth_event_type' => 'authentication',
                'status' => 'success',
                'oauth_session_id' => $sessionKey,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'oauth_request_data' => $sessionData,
            ]);
            
        } catch (Exception $e) {
            Log::error('OAuth session initialization failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Update session activity.
     */
    public function updateSessionActivity(User $user, string $provider): void
    {
        $sessionKey = $this->getSessionKey($user->id, $provider);
        
        if ($sessionInfo = Cache::get($sessionKey)) {
            $sessionInfo['last_activity'] = now()->toISOString();
            $sessionInfo['activity_count'] = ($sessionInfo['activity_count'] ?? 0) + 1;
            
            // Refresh cache TTL
            $sessionLifetime = config('services.oauth.session_lifetime', 120);
            Cache::put($sessionKey, $sessionInfo, now()->addMinutes($sessionLifetime));
        }
    }

    /**
     * Validate OAuth session security.
     */
    public function validateSessionSecurity(User $user, string $provider): array
    {
        $sessionKey = $this->getSessionKey($user->id, $provider);
        $sessionInfo = Cache::get($sessionKey);
        
        if (!$sessionInfo) {
            return [
                'valid' => false,
                'reason' => 'session_not_found',
                'severity' => 'warning'
            ];
        }
        
        $securityIssues = [];
        
        // Check IP address consistency
        if ($sessionInfo['ip_address'] !== request()->ip()) {
            $securityIssues[] = [
                'type' => 'ip_mismatch',
                'severity' => 'medium',
                'original_ip' => $sessionInfo['ip_address'],
                'current_ip' => request()->ip()
            ];
        }
        
        // Check user agent consistency
        if ($sessionInfo['user_agent'] !== request()->userAgent()) {
            $securityIssues[] = [
                'type' => 'user_agent_mismatch',
                'severity' => 'low',
                'original_ua' => $sessionInfo['user_agent'],
                'current_ua' => request()->userAgent()
            ];
        }
        
        // Check session age
        $sessionAge = now()->diffInMinutes(Carbon::parse($sessionInfo['initiated_at']));
        $maxSessionAge = config('services.oauth.session_lifetime', 120);
        
        if ($sessionAge > $maxSessionAge) {
            $securityIssues[] = [
                'type' => 'session_expired',
                'severity' => 'high',
                'session_age' => $sessionAge,
                'max_age' => $maxSessionAge
            ];
        }
        
        // Check for suspicious activity patterns
        if (isset($sessionInfo['activity_count']) && $sessionInfo['activity_count'] > 1000) {
            $securityIssues[] = [
                'type' => 'excessive_activity',
                'severity' => 'high',
                'activity_count' => $sessionInfo['activity_count']
            ];
        }
        
        // Log security validation if issues found
        if (!empty($securityIssues)) {
            OAuthAuditLog::create([
                'user_id' => $user->id,
                'oauth_provider' => $provider,
                'oauth_action' => 'session.security_validation',
                'oauth_event_type' => 'security',
                'status' => 'warning',
                'security_level' => $this->calculateSecurityLevel($securityIssues),
                'security_indicators' => $securityIssues,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
        
        return [
            'valid' => empty($securityIssues) || $this->areIssuesAcceptable($securityIssues),
            'issues' => $securityIssues,
            'severity' => $this->calculateSecurityLevel($securityIssues)
        ];
    }

    /**
     * Refresh OAuth token if needed.
     */
    public function refreshTokenIfNeeded(User $user): bool
    {
        if (!$user->isOAuthUser() || $user->isOAuthTokenExpired()) {
            return false;
        }
        
        // Check if token needs refresh (within buffer time)
        $bufferMinutes = config('services.oauth.token_expiry_buffer', 5);
        if (!$user->oauth_token_expires_at || 
            $user->oauth_token_expires_at->subMinutes($bufferMinutes)->isFuture()) {
            return true; // Token still valid
        }
        
        try {
            return $this->performTokenRefresh($user);
        } catch (Exception $e) {
            Log::error('OAuth token refresh failed', [
                'user_id' => $user->id,
                'provider' => $user->oauth_provider,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Perform OAuth token refresh.
     */
    protected function performTokenRefresh(User $user): bool
    {
        if (!$user->oauth_refresh_token) {
            return false; // No refresh token available
        }
        
        $provider = $user->oauth_provider;
        $refreshToken = decrypt($user->oauth_refresh_token);
        
        try {
            $tokenResponse = null;
            
            if ($provider === 'google') {
                $tokenResponse = $this->refreshGoogleToken($refreshToken);
            } elseif ($provider === 'microsoft') {
                $tokenResponse = $this->refreshMicrosoftToken($refreshToken);
            }
            
            if ($tokenResponse && isset($tokenResponse['access_token'])) {
                // Update user's token data
                $updates = [
                    'oauth_access_token' => encrypt($tokenResponse['access_token']),
                    'oauth_token_expires_at' => isset($tokenResponse['expires_in']) 
                        ? now()->addSeconds($tokenResponse['expires_in'])
                        : null
                ];
                
                // Update refresh token if provided
                if (isset($tokenResponse['refresh_token'])) {
                    $updates['oauth_refresh_token'] = encrypt($tokenResponse['refresh_token']);
                }
                
                $user->update($updates);
                
                // Log successful refresh
                OAuthAuditLog::create([
                    'user_id' => $user->id,
                    'oauth_provider' => $provider,
                    'oauth_action' => 'token.refreshed',
                    'oauth_event_type' => 'authentication',
                    'status' => 'success',
                    'oauth_token_info' => [
                        'expires_at' => $updates['oauth_token_expires_at']?->toISOString(),
                        'has_refresh_token' => isset($tokenResponse['refresh_token'])
                    ],
                    'ip_address' => request()->ip(),
                ]);
                
                return true;
            }
            
        } catch (Exception $e) {
            // Log failed refresh
            OAuthAuditLog::create([
                'user_id' => $user->id,
                'oauth_provider' => $provider,
                'oauth_action' => 'token.refresh_failed',
                'oauth_event_type' => 'security',
                'status' => 'failure',
                'error_message' => $e->getMessage(),
                'ip_address' => request()->ip(),
            ]);
            
            throw $e;
        }
        
        return false;
    }

    /**
     * Refresh Google OAuth token.
     */
    protected function refreshGoogleToken(string $refreshToken): ?array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);
        
        if ($response->successful()) {
            return $response->json();
        }
        
        throw new Exception('Google token refresh failed: ' . $response->body());
    }

    /**
     * Refresh Microsoft OAuth token.
     */
    protected function refreshMicrosoftToken(string $refreshToken): ?array
    {
        $tenant = config('services.microsoft.tenant', 'common');
        $url = "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token";
        
        $response = Http::asForm()->post($url, [
            'client_id' => config('services.microsoft.client_id'),
            'client_secret' => config('services.microsoft.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);
        
        if ($response->successful()) {
            return $response->json();
        }
        
        throw new Exception('Microsoft token refresh failed: ' . $response->body());
    }

    /**
     * Terminate OAuth session.
     */
    public function terminateSession(User $user, string $provider): void
    {
        $sessionKey = $this->getSessionKey($user->id, $provider);
        
        // Get session info before deletion
        $sessionInfo = Cache::get($sessionKey);
        
        // Remove from cache
        Cache::forget($sessionKey);
        
        // Clear related security caches
        $securityKey = $this->getSecurityKey($user->id, $provider);
        Cache::forget($securityKey);
        
        // Log session termination
        OAuthAuditLog::create([
            'user_id' => $user->id,
            'oauth_provider' => $provider,
            'oauth_action' => 'session.terminated',
            'oauth_event_type' => 'authentication',
            'status' => 'success',
            'oauth_session_id' => $sessionKey,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'oauth_request_data' => $sessionInfo,
        ]);
    }

    /**
     * Get active OAuth sessions for user.
     */
    public function getActiveSessions(User $user): array
    {
        $sessions = [];
        $providers = $user->oauth_linked_providers ?? [$user->oauth_provider];
        
        foreach ($providers as $provider) {
            if (!$provider) continue;
            
            $sessionKey = $this->getSessionKey($user->id, $provider);
            $sessionInfo = Cache::get($sessionKey);
            
            if ($sessionInfo) {
                $sessions[$provider] = $sessionInfo;
            }
        }
        
        return $sessions;
    }

    /**
     * Synchronize sessions across providers.
     */
    public function synchronizeProviderSessions(User $user): void
    {
        $linkedProviders = $user->oauth_linked_providers ?? [];
        
        if (count($linkedProviders) <= 1) {
            return; // No synchronization needed
        }
        
        foreach ($linkedProviders as $provider) {
            $sessionKey = $this->getSessionKey($user->id, $provider);
            
            if (Cache::has($sessionKey)) {
                $this->updateSessionActivity($user, $provider);
            }
        }
    }

    /**
     * Generate session key.
     */
    protected function getSessionKey(int $userId, string $provider): string
    {
        return self::SESSION_CACHE_PREFIX . "{$userId}:{$provider}";
    }

    /**
     * Generate security key.
     */
    protected function getSecurityKey(int $userId, string $provider): string
    {
        return self::SECURITY_CACHE_PREFIX . "{$userId}:{$provider}";
    }

    /**
     * Generate security flags dla session.
     */
    protected function generateSecurityFlags(User $user, string $provider): array
    {
        return [
            'domain_verified' => $user->oauth_verified,
            'domain' => $user->oauth_domain,
            'allowed_domain' => $user->isOAuthDomainAllowed(),
            'multi_provider' => count($user->oauth_linked_providers ?? []) > 1,
            'provider_count' => count($user->oauth_linked_providers ?? []),
            'created_via_oauth' => $user->primary_auth_method === $provider,
        ];
    }

    /**
     * Calculate security level from issues.
     */
    protected function calculateSecurityLevel(array $issues): string
    {
        if (empty($issues)) {
            return 'normal';
        }
        
        $maxSeverity = 'low';
        foreach ($issues as $issue) {
            if ($issue['severity'] === 'high') {
                $maxSeverity = 'critical';
                break;
            } elseif ($issue['severity'] === 'medium' && $maxSeverity !== 'high') {
                $maxSeverity = 'suspicious';
            }
        }
        
        return match($maxSeverity) {
            'high' => 'critical',
            'medium' => 'suspicious',
            default => 'normal'
        };
    }

    /**
     * Check if security issues are acceptable.
     */
    protected function areIssuesAcceptable(array $issues): bool
    {
        // Only allow low severity issues
        foreach ($issues as $issue) {
            if (in_array($issue['severity'], ['medium', 'high'])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Clean up expired sessions.
     */
    public function cleanupExpiredSessions(): int
    {
        // This would be called by a scheduled task
        // For now, we rely on cache TTL for cleanup
        // In future, we could implement manual cleanup logic
        
        return 0;
    }
}