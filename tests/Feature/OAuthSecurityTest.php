<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\OAuthAuditLog;
use App\Services\OAuthSecurityService;
use App\Services\OAuthSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * OAuth Security Service Tests
 * 
 * FAZA D: OAuth2 + Advanced Features
 * Comprehensive tests dla OAuth security features
 */
class OAuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected OAuthSecurityService $securityService;
    protected OAuthSessionService $sessionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->securityService = app(OAuthSecurityService::class);
        $this->sessionService = app(OAuthSessionService::class);
        
        Config::set('services.oauth', [
            'allowed_domains' => 'mpptrade.pl',
            'max_login_attempts' => 5,
            'lockout_duration' => 30,
        ]);
    }

    public function test_rate_limiting_detection()
    {
        $identifier = 'test_user';
        
        // Should not be limited initially
        $this->assertFalse($this->securityService->checkRateLimit('login', $identifier));
        
        // Record multiple attempts
        for ($i = 0; $i < 6; $i++) {
            $this->securityService->recordAttempt('login', $identifier);
        }
        
        // Should be rate limited now
        $this->assertTrue($this->securityService->checkRateLimit('login', $identifier));
    }

    public function test_suspicious_activity_detection_multiple_failures()
    {
        $user = User::factory()->create([
            'oauth_provider' => 'google',
            'oauth_id' => '123456789',
        ]);

        // Create failed login attempts
        for ($i = 0; $i < 4; $i++) {
            OAuthAuditLog::create([
                'user_id' => $user->id,
                'oauth_provider' => 'google',
                'oauth_action' => 'login.attempt',
                'status' => 'failure',
                'ip_address' => '192.168.1.1',
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $indicators = $this->securityService->detectSuspiciousActivity($user, 'google', [
            'ip' => '192.168.1.1'
        ]);

        $this->assertNotEmpty($indicators);
        $this->assertEquals('multiple_failures', $indicators[0]['type']);
        $this->assertEquals('high', $indicators[0]['severity']);
    }

    public function test_unusual_location_detection()
    {
        $user = User::factory()->create([
            'oauth_provider' => 'google',
            'oauth_id' => '123456789',
        ]);

        // Create successful logins from known IP
        OAuthAuditLog::create([
            'user_id' => $user->id,
            'oauth_provider' => 'google',
            'oauth_event_type' => 'authentication',
            'status' => 'success',
            'ip_address' => '192.168.1.1',
            'created_at' => now()->subDays(1),
        ]);

        $indicators = $this->securityService->detectSuspiciousActivity($user, 'google', [
            'ip' => '10.0.0.1' // Different IP
        ]);

        $this->assertNotEmpty($indicators);
        $this->assertEquals('unusual_location', $indicators[0]['type']);
        $this->assertEquals('medium', $indicators[0]['severity']);
    }

    public function test_security_incident_handling()
    {
        $user = User::factory()->create([
            'oauth_provider' => 'google',
            'oauth_id' => '123456789',
        ]);

        $indicators = [
            [
                'type' => 'multiple_failures',
                'severity' => 'high',
                'count' => 5,
            ],
            [
                'type' => 'unusual_location',
                'severity' => 'high',
                'ip' => '10.0.0.1',
            ]
        ];

        $this->securityService->handleSecurityIncident($user, 'google', $indicators);

        // Check audit log was created
        $this->assertDatabaseHas('oauth_audit_logs', [
            'user_id' => $user->id,
            'oauth_provider' => 'google',
            'oauth_action' => 'security.incident',
            'oauth_event_type' => 'security',
            'security_level' => 'critical',
        ]);

        // Check user was locked (emergency lockout for critical incidents)
        $user->refresh();
        $this->assertNotNull($user->oauth_locked_until);
        $this->assertNull($user->oauth_access_token);
    }

    public function test_enhanced_verification_requirement()
    {
        $user = User::factory()->create([
            'oauth_provider' => 'google',
            'oauth_id' => '123456789',
        ]);

        $this->assertFalse($this->securityService->requiresEnhancedVerification($user, 'google'));

        // Apply enhanced verification
        $indicators = [
            [
                'type' => 'unusual_device',
                'severity' => 'medium',
            ]
        ];

        $this->securityService->handleSecurityIncident($user, 'google', $indicators);

        $this->assertTrue($this->securityService->requiresEnhancedVerification($user, 'google'));

        // Clear verification
        $this->securityService->clearEnhancedVerification($user, 'google');

        $this->assertFalse($this->securityService->requiresEnhancedVerification($user, 'google'));
    }

    public function test_device_fingerprint_generation()
    {
        $context1 = [
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'accept_language' => 'en-US,en;q=0.9',
            'accept_encoding' => 'gzip, deflate, br',
        ];

        $context2 = [
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'accept_language' => 'en-US,en;q=0.9',
            'accept_encoding' => 'gzip, deflate, br',
        ];

        $reflection = new \ReflectionClass($this->securityService);
        $method = $reflection->getMethod('generateDeviceFingerprint');
        $method->setAccessible(true);

        $fingerprint1 = $method->invoke($this->securityService, $context1);
        $fingerprint2 = $method->invoke($this->securityService, $context2);

        $this->assertIsString($fingerprint1);
        $this->assertIsString($fingerprint2);
        $this->assertNotEquals($fingerprint1, $fingerprint2);
    }

    public function test_security_summary()
    {
        $user = User::factory()->create([
            'oauth_provider' => 'google',
            'oauth_id' => '123456789',
            'oauth_login_attempts' => 3,
            'oauth_locked_until' => null,
            'oauth_last_used_at' => now()->subHour(),
        ]);

        // Create a recent security incident
        OAuthAuditLog::create([
            'user_id' => $user->id,
            'oauth_provider' => 'google',
            'oauth_event_type' => 'security',
            'security_level' => 'critical',
            'created_at' => now()->subDays(2),
        ]);

        $summary = $this->securityService->getSecuritySummary($user);

        $this->assertFalse($summary['is_locked']);
        $this->assertEquals(3, $summary['failed_attempts']);
        $this->assertEquals(1, $summary['recent_incidents']);
        $this->assertEquals('high', $summary['risk_level']);
    }

    public function test_oauth_session_initialization()
    {
        $user = User::factory()->create([
            'oauth_provider' => 'google',
            'oauth_id' => '123456789',
            'oauth_domain' => 'mpptrade.pl',
            'oauth_verified' => true,
        ]);

        $sessionData = [
            'access_token' => 'test_token',
            'refresh_token' => 'test_refresh_token',
        ];

        $this->sessionService->initializeSession($user, 'google', $sessionData);

        // Check session was stored in cache
        $sessionKey = "oauth_session:{$user->id}:google";
        $this->assertTrue(Cache::has($sessionKey));

        // Check audit log
        $this->assertDatabaseHas('oauth_audit_logs', [
            'user_id' => $user->id,
            'oauth_provider' => 'google',
            'oauth_action' => 'session.initialized',
            'status' => 'success',
        ]);
    }

    public function test_session_security_validation()
    {
        $user = User::factory()->create([
            'oauth_provider' => 'google',
            'oauth_id' => '123456789',
        ]);

        // Initialize session with specific IP
        $sessionData = ['test' => 'data'];
        Cache::put("oauth_session:{$user->id}:google", [
            'user_id' => $user->id,
            'provider' => 'google',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Agent',
            'initiated_at' => now()->toISOString(),
            'last_activity' => now()->toISOString(),
        ], now()->addHours(2));

        // Mock request with different IP
        request()->merge(['REMOTE_ADDR' => '10.0.0.1']);
        request()->headers->set('User-Agent', 'Different Agent');

        $validation = $this->sessionService->validateSessionSecurity($user, 'google');

        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['issues']);
        $this->assertEquals('ip_mismatch', $validation['issues'][0]['type']);
    }

    public function test_session_termination()
    {
        $user = User::factory()->create([
            'oauth_provider' => 'google',
            'oauth_id' => '123456789',
        ]);

        // Initialize session
        $sessionKey = "oauth_session:{$user->id}:google";
        Cache::put($sessionKey, ['test' => 'data'], now()->addHours(2));

        $this->assertTrue(Cache::has($sessionKey));

        $this->sessionService->terminateSession($user, 'google');

        $this->assertFalse(Cache::has($sessionKey));

        // Check audit log
        $this->assertDatabaseHas('oauth_audit_logs', [
            'user_id' => $user->id,
            'oauth_provider' => 'google',
            'oauth_action' => 'session.terminated',
            'status' => 'success',
        ]);
    }

    public function test_active_sessions_retrieval()
    {
        $user = User::factory()->create([
            'oauth_provider' => 'google',
            'oauth_id' => '123456789',
            'oauth_linked_providers' => ['google', 'microsoft'],
        ]);

        // Create sessions for both providers
        Cache::put("oauth_session:{$user->id}:google", [
            'provider' => 'google',
            'initiated_at' => now()->toISOString(),
        ], now()->addHours(2));

        Cache::put("oauth_session:{$user->id}:microsoft", [
            'provider' => 'microsoft',
            'initiated_at' => now()->toISOString(),
        ], now()->addHours(2));

        $sessions = $this->sessionService->getActiveSessions($user);

        $this->assertCount(2, $sessions);
        $this->assertArrayHasKey('google', $sessions);
        $this->assertArrayHasKey('microsoft', $sessions);
    }

    public function test_oauth_audit_log_model_scopes()
    {
        $user = User::factory()->create();

        // Create various audit logs
        OAuthAuditLog::create([
            'user_id' => $user->id,
            'oauth_provider' => 'google',
            'oauth_action' => 'login.attempt',
            'oauth_event_type' => 'authentication',
            'status' => 'success',
            'ip_address' => '192.168.1.1',
        ]);

        OAuthAuditLog::create([
            'user_id' => $user->id,
            'oauth_provider' => 'google',
            'oauth_action' => 'login.attempt',
            'oauth_event_type' => 'authentication',
            'status' => 'failure',
            'ip_address' => '192.168.1.1',
        ]);

        OAuthAuditLog::create([
            'user_id' => $user->id,
            'oauth_provider' => 'microsoft',
            'oauth_action' => 'security.incident',
            'oauth_event_type' => 'security',
            'status' => 'blocked',
            'security_level' => 'critical',
            'ip_address' => '192.168.1.1',
        ]);

        // Test scopes
        $this->assertEquals(2, OAuthAuditLog::forProvider('google')->count());
        $this->assertEquals(3, OAuthAuditLog::forUser($user->id)->count());
        $this->assertEquals(1, OAuthAuditLog::failedAttempts()->count());
        $this->assertEquals(1, OAuthAuditLog::successfulAttempts()->count());
        $this->assertEquals(1, OAuthAuditLog::securityIncidents()->count());
        $this->assertEquals(1, OAuthAuditLog::suspiciousActivity()->count());
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}