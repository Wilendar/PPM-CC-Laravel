<?php

namespace App\Services;

use App\Models\User;
use App\Models\OAuthAuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * OAuth Security Service
 * 
 * FAZA D: OAuth2 + Advanced Features
 * Advanced security dla OAuth2 operations
 * 
 * Features:
 * - Brute force protection
 * - Suspicious activity detection
 * - Account lockout management
 * - Security incident tracking
 * - Device fingerprinting
 * - Location-based security
 */
class OAuthSecurityService
{
    /**
     * Rate limit cache prefix.
     */
    protected const RATE_LIMIT_PREFIX = 'oauth_rate_limit:';
    
    /**
     * Security incident cache prefix.
     */
    protected const INCIDENT_CACHE_PREFIX = 'oauth_incident:';
    
    /**
     * Device fingerprint cache prefix.
     */
    protected const DEVICE_CACHE_PREFIX = 'oauth_device:';

    /**
     * Check if OAuth operation is rate limited.
     */
    public function checkRateLimit(string $operation, string $identifier): bool
    {
        $key = $operation . ':' . $identifier;
        
        $limits = $this->getRateLimits();
        $limit = $limits[$operation] ?? ['attempts' => 10, 'period' => 60];
        
        return RateLimiter::tooManyAttempts($key, $limit['attempts']);
    }

    /**
     * Record OAuth attempt for rate limiting.
     */
    public function recordAttempt(string $operation, string $identifier): void
    {
        $key = $operation . ':' . $identifier;
        
        $limits = $this->getRateLimits();
        $limit = $limits[$operation] ?? ['attempts' => 10, 'period' => 60];
        
        RateLimiter::hit($key, $limit['period']);
    }

    /**
     * Detect suspicious OAuth activity.
     */
    public function detectSuspiciousActivity(User $user, string $provider, array $context): array
    {
        $suspiciousIndicators = [];
        
        // Check for multiple failed attempts
        $recentFailures = $this->getRecentFailedAttempts($user, $provider);
        if ($recentFailures >= 3) {
            $suspiciousIndicators[] = [
                'type' => 'multiple_failures',
                'severity' => 'high',
                'count' => $recentFailures,
                'description' => "Multiple failed OAuth attempts ({$recentFailures}) in last hour"
            ];
        }
        
        // Check for unusual IP address
        if ($this->isUnusualLocation($user, $context['ip'] ?? request()->ip())) {
            $suspiciousIndicators[] = [
                'type' => 'unusual_location',
                'severity' => 'medium',
                'ip' => $context['ip'] ?? request()->ip(),
                'description' => 'OAuth attempt from unusual IP address'
            ];
        }
        
        // Check for device fingerprint changes
        $deviceFingerprint = $this->generateDeviceFingerprint($context);
        if ($this->isUnusualDevice($user, $deviceFingerprint)) {
            $suspiciousIndicators[] = [
                'type' => 'unusual_device',
                'severity' => 'medium',
                'fingerprint' => $deviceFingerprint,
                'description' => 'OAuth attempt from unusual device'
            ];
        }
        
        // Check for rapid provider switching
        if ($this->hasRapidProviderSwitching($user)) {
            $suspiciousIndicators[] = [
                'type' => 'rapid_provider_switching',
                'severity' => 'medium',
                'description' => 'Multiple OAuth provider attempts in short time'
            ];
        }
        
        // Check for off-hours activity
        if ($this->isOffHoursActivity($user)) {
            $suspiciousIndicators[] = [
                'type' => 'off_hours_activity',
                'severity' => 'low',
                'description' => 'OAuth attempt during unusual hours'
            ];
        }
        
        // Check domain verification bypass attempts
        if (!$user->oauth_verified && !$user->isOAuthDomainAllowed()) {
            $suspiciousIndicators[] = [
                'type' => 'domain_bypass_attempt',
                'severity' => 'high',
                'domain' => $user->oauth_domain,
                'description' => 'Attempt to bypass domain verification'
            ];
        }
        
        return $suspiciousIndicators;
    }

    /**
     * Handle security incident.
     */
    public function handleSecurityIncident(User $user, string $provider, array $indicators): void
    {
        $incidentId = Str::uuid();
        $severity = $this->calculateIncidentSeverity($indicators);
        
        // Create security incident log
        OAuthAuditLog::logSecurityIncident(
            $provider,
            $user->id,
            'suspicious_activity_detected',
            $indicators,
            [
                'incident_id' => $incidentId,
                'user_agent' => request()->userAgent(),
                'referrer' => request()->header('referer'),
                'timestamp' => now()->toISOString()
            ]
        );
        
        // Cache incident for quick reference
        $incidentKey = $this->getIncidentKey($user->id, $provider);
        Cache::put($incidentKey, [
            'incident_id' => $incidentId,
            'severity' => $severity,
            'indicators' => $indicators,
            'timestamp' => now()->toISOString()
        ], now()->addHours(24));
        
        // Apply security measures based on severity
        if ($severity === 'critical') {
            $this->applyEmergencyLockout($user, $provider);
        } elseif ($severity === 'suspicious') {
            $this->applyEnhancedVerification($user, $provider);
        }
        
        // Log security event
        Log::warning('OAuth security incident detected', [
            'incident_id' => $incidentId,
            'user_id' => $user->id,
            'provider' => $provider,
            'severity' => $severity,
            'indicators' => $indicators,
            'ip' => request()->ip()
        ]);
    }

    /**
     * Apply emergency lockout dla critical incidents.
     */
    protected function applyEmergencyLockout(User $user, string $provider): void
    {
        $user->update([
            'oauth_locked_until' => now()->addHours(2),
            'oauth_login_attempts' => 10 // Max attempts
        ]);
        
        // Revoke OAuth tokens
        $user->update([
            'oauth_access_token' => null,
            'oauth_refresh_token' => null,
            'oauth_token_expires_at' => null
        ]);
        
        OAuthAuditLog::create([
            'user_id' => $user->id,
            'oauth_provider' => $provider,
            'oauth_action' => 'emergency_lockout_applied',
            'oauth_event_type' => 'security',
            'status' => 'success',
            'security_level' => 'critical',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Apply enhanced verification dla suspicious activity.
     */
    protected function applyEnhancedVerification(User $user, string $provider): void
    {
        // Mark for additional verification
        $verificationKey = "oauth_enhanced_verification:{$user->id}:{$provider}";
        Cache::put($verificationKey, [
            'required' => true,
            'reason' => 'suspicious_activity',
            'timestamp' => now()->toISOString()
        ], now()->addHours(6));
        
        OAuthAuditLog::create([
            'user_id' => $user->id,
            'oauth_provider' => $provider,
            'oauth_action' => 'enhanced_verification_required',
            'oauth_event_type' => 'security',
            'status' => 'pending',
            'security_level' => 'suspicious',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get recent failed attempts dla user and provider.
     */
    protected function getRecentFailedAttempts(User $user, string $provider): int
    {
        return OAuthAuditLog::where('user_id', $user->id)
                           ->where('oauth_provider', $provider)
                           ->where('oauth_action', 'login.attempt')
                           ->where('status', 'failure')
                           ->where('created_at', '>=', now()->subHour())
                           ->count();
    }

    /**
     * Check if IP address is unusual dla user.
     */
    protected function isUnusualLocation(User $user, string $ip): bool
    {
        // Get user's recent IP addresses
        $recentIPs = OAuthAuditLog::where('user_id', $user->id)
                                 ->where('oauth_event_type', 'authentication')
                                 ->where('status', 'success')
                                 ->where('created_at', '>=', now()->subDays(30))
                                 ->pluck('ip_address')
                                 ->unique()
                                 ->toArray();
        
        return !in_array($ip, $recentIPs) && count($recentIPs) > 0;
    }

    /**
     * Generate device fingerprint.
     */
    protected function generateDeviceFingerprint(array $context): string
    {
        $userAgent = $context['user_agent'] ?? request()->userAgent();
        $acceptLanguage = $context['accept_language'] ?? request()->header('accept-language');
        $acceptEncoding = $context['accept_encoding'] ?? request()->header('accept-encoding');
        
        $fingerprint = [
            'user_agent' => $userAgent,
            'accept_language' => $acceptLanguage,
            'accept_encoding' => $acceptEncoding,
        ];
        
        return hash('sha256', serialize($fingerprint));
    }

    /**
     * Check if device is unusual dla user.
     */
    protected function isUnusualDevice(User $user, string $fingerprint): bool
    {
        $deviceKey = $this->getDeviceKey($user->id);
        $knownDevices = Cache::get($deviceKey, []);
        
        if (!in_array($fingerprint, $knownDevices)) {
            // Add to known devices (keep last 5)
            $knownDevices[] = $fingerprint;
            $knownDevices = array_slice($knownDevices, -5);
            Cache::put($deviceKey, $knownDevices, now()->addDays(90));
            
            return count($knownDevices) > 1; // First device is not unusual
        }
        
        return false;
    }

    /**
     * Check for rapid provider switching.
     */
    protected function hasRapidProviderSwitching(User $user): bool
    {
        $recentProviders = OAuthAuditLog::where('user_id', $user->id)
                                       ->where('oauth_action', 'login.attempt')
                                       ->where('created_at', '>=', now()->subMinutes(10))
                                       ->pluck('oauth_provider')
                                       ->unique()
                                       ->count();
        
        return $recentProviders > 1;
    }

    /**
     * Check if activity is during off-hours.
     */
    protected function isOffHoursActivity(User $user): bool
    {
        $timezone = $user->getUserTimezone();
        $currentHour = now()->setTimezone($timezone)->hour;
        
        // Consider 22:00-06:00 as off-hours
        return $currentHour >= 22 || $currentHour <= 6;
    }

    /**
     * Calculate incident severity.
     */
    protected function calculateIncidentSeverity(array $indicators): string
    {
        $highCount = 0;
        $mediumCount = 0;
        
        foreach ($indicators as $indicator) {
            if ($indicator['severity'] === 'high') {
                $highCount++;
            } elseif ($indicator['severity'] === 'medium') {
                $mediumCount++;
            }
        }
        
        if ($highCount >= 2 || ($highCount >= 1 && $mediumCount >= 2)) {
            return 'critical';
        } elseif ($highCount >= 1 || $mediumCount >= 2) {
            return 'suspicious';
        }
        
        return 'normal';
    }

    /**
     * Get rate limit configuration.
     */
    protected function getRateLimits(): array
    {
        return [
            'login' => ['attempts' => 5, 'period' => 300], // 5 attempts per 5 minutes
            'redirect' => ['attempts' => 10, 'period' => 60], // 10 redirects per minute
            'callback' => ['attempts' => 20, 'period' => 60], // 20 callbacks per minute
            'link' => ['attempts' => 5, 'period' => 600], // 5 links per 10 minutes
            'unlink' => ['attempts' => 3, 'period' => 600], // 3 unlinks per 10 minutes
            'token_refresh' => ['attempts' => 10, 'period' => 300], // 10 refreshes per 5 minutes
        ];
    }

    /**
     * Get incident cache key.
     */
    protected function getIncidentKey(int $userId, string $provider): string
    {
        return self::INCIDENT_CACHE_PREFIX . "{$userId}:{$provider}";
    }

    /**
     * Get device cache key.
     */
    protected function getDeviceKey(int $userId): string
    {
        return self::DEVICE_CACHE_PREFIX . $userId;
    }

    /**
     * Check if user requires enhanced verification.
     */
    public function requiresEnhancedVerification(User $user, string $provider): bool
    {
        $verificationKey = "oauth_enhanced_verification:{$user->id}:{$provider}";
        return Cache::has($verificationKey);
    }

    /**
     * Clear enhanced verification requirement.
     */
    public function clearEnhancedVerification(User $user, string $provider): void
    {
        $verificationKey = "oauth_enhanced_verification:{$user->id}:{$provider}";
        Cache::forget($verificationKey);
        
        OAuthAuditLog::create([
            'user_id' => $user->id,
            'oauth_provider' => $provider,
            'oauth_action' => 'enhanced_verification_cleared',
            'oauth_event_type' => 'security',
            'status' => 'success',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get security summary dla user.
     */
    public function getSecuritySummary(User $user): array
    {
        $summary = [
            'is_locked' => $user->isOAuthLocked(),
            'locked_until' => $user->oauth_locked_until?->toISOString(),
            'failed_attempts' => $user->oauth_login_attempts,
            'last_activity' => $user->oauth_last_used_at?->toISOString(),
            'requires_verification' => false,
            'recent_incidents' => 0,
            'risk_level' => 'low'
        ];
        
        // Check for enhanced verification requirements
        if ($user->oauth_provider) {
            $summary['requires_verification'] = $this->requiresEnhancedVerification($user, $user->oauth_provider);
        }
        
        // Count recent incidents
        $summary['recent_incidents'] = OAuthAuditLog::where('user_id', $user->id)
                                                   ->where('oauth_event_type', 'security')
                                                   ->where('security_level', 'critical')
                                                   ->where('created_at', '>=', now()->subDays(7))
                                                   ->count();
        
        // Calculate risk level
        if ($summary['recent_incidents'] > 0 || $summary['is_locked']) {
            $summary['risk_level'] = 'high';
        } elseif ($summary['failed_attempts'] > 2 || $summary['requires_verification']) {
            $summary['risk_level'] = 'medium';
        }
        
        return $summary;
    }
}