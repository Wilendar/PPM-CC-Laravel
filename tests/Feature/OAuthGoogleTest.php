<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\OAuthAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * Google OAuth Integration Tests
 * 
 * FAZA D: OAuth2 + Advanced Features
 * Comprehensive tests dla Google Workspace OAuth flow
 */
class OAuthGoogleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up OAuth configuration for testing
        Config::set('services.google', [
            'client_id' => 'test_google_client_id',
            'client_secret' => 'test_google_client_secret',
            'redirect' => 'http://localhost/auth/google/callback',
            'hosted_domain' => 'mpptrade.pl',
        ]);
        
        Config::set('services.oauth', [
            'enabled_providers' => 'google,microsoft',
            'allowed_domains' => 'mpptrade.pl',
            'auto_registration' => true,
            'link_existing_accounts' => true,
        ]);
    }

    public function test_google_oauth_redirect()
    {
        $response = $this->get('/auth/google');
        
        $response->assertStatus(302);
        $this->assertStringContains('accounts.google.com', $response->headers->get('Location'));
        
        // Check audit log
        $this->assertDatabaseHas('oauth_audit_logs', [
            'oauth_provider' => 'google',
            'oauth_action' => 'oauth.redirect.initiated'
        ]);
    }

    public function test_google_oauth_callback_new_user()
    {
        // Mock Socialite
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('123456789');
        $abstractUser->shouldReceive('getEmail')->andReturn('test@mpptrade.pl');
        $abstractUser->shouldReceive('getName')->andReturn('Test User');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');
        $abstractUser->token = 'test_access_token';
        $abstractUser->refreshToken = 'test_refresh_token';
        $abstractUser->expiresIn = 3600;

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        // Make callback request
        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/dashboard');
        
        // Check user was created
        $this->assertDatabaseHas('users', [
            'email' => 'test@mpptrade.pl',
            'oauth_provider' => 'google',
            'oauth_id' => '123456789',
            'oauth_verified' => true,
            'primary_auth_method' => 'google',
        ]);
        
        // Check audit log
        $this->assertDatabaseHas('oauth_audit_logs', [
            'oauth_provider' => 'google',
            'oauth_action' => 'oauth.login.success'
        ]);
    }

    public function test_google_oauth_callback_existing_user()
    {
        // Create existing user
        $user = User::factory()->create([
            'email' => 'test@mpptrade.pl',
            'oauth_provider' => null,
            'oauth_id' => null,
        ]);

        // Mock Socialite
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('123456789');
        $abstractUser->shouldReceive('getEmail')->andReturn('test@mpptrade.pl');
        $abstractUser->shouldReceive('getName')->andReturn('Test User');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');
        $abstractUser->token = 'test_access_token';
        $abstractUser->refreshToken = 'test_refresh_token';
        $abstractUser->expiresIn = 3600;

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/dashboard');
        
        // Check user was linked
        $user->refresh();
        $this->assertEquals('google', $user->oauth_provider);
        $this->assertEquals('123456789', $user->oauth_id);
        $this->assertEquals('google', $user->primary_auth_method);
        
        // Check audit log
        $this->assertDatabaseHas('oauth_audit_logs', [
            'user_id' => $user->id,
            'oauth_provider' => 'google',
            'oauth_action' => 'oauth.account.linked'
        ]);
    }

    public function test_google_oauth_callback_domain_restriction()
    {
        // Mock Socialite with restricted domain
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('123456789');
        $abstractUser->shouldReceive('getEmail')->andReturn('test@unauthorized-domain.com');
        $abstractUser->shouldReceive('getName')->andReturn('Test User');

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['oauth']);
        
        // Check no user was created
        $this->assertDatabaseMissing('users', [
            'email' => 'test@unauthorized-domain.com'
        ]);
        
        // Check audit log
        $this->assertDatabaseHas('oauth_audit_logs', [
            'oauth_provider' => 'google',
            'oauth_action' => 'oauth.domain.rejected',
            'oauth_email' => 'test@unauthorized-domain.com'
        ]);
    }

    public function test_google_oauth_callback_error_handling()
    {
        $response = $this->get('/auth/google/callback?error=access_denied&error_description=User+denied+access');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['oauth']);
        
        // Check audit log
        $this->assertDatabaseHas('oauth_audit_logs', [
            'oauth_provider' => 'google',
            'oauth_action' => 'oauth.callback.error'
        ]);
    }

    public function test_google_account_linking()
    {
        $user = User::factory()->create(['email' => 'test@mpptrade.pl']);
        
        // Mock Socialite
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('123456789');
        $abstractUser->shouldReceive('getEmail')->andReturn('test@mpptrade.pl');
        $abstractUser->shouldReceive('getName')->andReturn('Test User');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');
        $abstractUser->token = 'test_access_token';
        $abstractUser->refreshToken = 'test_refresh_token';

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->actingAs($user)->post('/auth/google/link');

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success');
        
        // Check user was linked
        $user->refresh();
        $this->assertEquals('google', $user->oauth_provider);
        $this->assertContains('google', $user->oauth_linked_providers ?? []);
        
        // Check audit log
        $this->assertDatabaseHas('oauth_audit_logs', [
            'user_id' => $user->id,
            'oauth_provider' => 'google',
            'oauth_action' => 'oauth.link.success'
        ]);
    }

    public function test_google_account_unlinking()
    {
        $user = User::factory()->create([
            'email' => 'test@mpptrade.pl',
            'oauth_provider' => 'google',
            'oauth_id' => '123456789',
            'oauth_email' => 'test@mpptrade.pl',
            'oauth_linked_providers' => ['google'],
            'primary_auth_method' => 'google',
        ]);

        $response = $this->actingAs($user)->delete('/auth/google/unlink');

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success');
        
        // Check user was unlinked
        $user->refresh();
        $this->assertNull($user->oauth_provider);
        $this->assertEquals('local', $user->primary_auth_method);
        
        // Check audit log
        $this->assertDatabaseHas('oauth_audit_logs', [
            'user_id' => $user->id,
            'oauth_provider' => 'google',
            'oauth_action' => 'oauth.unlink.success'
        ]);
    }

    public function test_google_domain_verification()
    {
        $response = $this->post('/auth/google/domain-verify', [
            'email' => 'test@mpptrade.pl',
            'domain' => 'mpptrade.pl'
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success');
        
        // Check audit log
        $this->assertDatabaseHas('oauth_audit_logs', [
            'oauth_provider' => 'google',
            'oauth_action' => 'oauth.domain.verification.success'
        ]);
    }

    public function test_google_oauth_locked_account()
    {
        $user = User::factory()->create([
            'oauth_provider' => 'google',
            'oauth_id' => '123456789',
            'oauth_locked_until' => now()->addHour(),
        ]);

        // Mock Socialite
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('123456789');
        $abstractUser->shouldReceive('getEmail')->andReturn($user->email);

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['oauth']);
        
        // Check audit log
        $this->assertDatabaseHas('oauth_audit_logs', [
            'user_id' => $user->id,
            'oauth_provider' => 'google',
            'oauth_action' => 'oauth.account.locked'
        ]);
    }

    public function test_google_oauth_rate_limiting()
    {
        // Make multiple rapid requests
        for ($i = 0; $i < 15; $i++) {
            $response = $this->get('/auth/google');
        }
        
        // Should be rate limited
        $response = $this->get('/auth/google');
        $response->assertStatus(429);
    }

    public function test_oauth_user_model_methods()
    {
        $user = User::factory()->create([
            'oauth_provider' => 'google',
            'oauth_id' => '123456789',
            'oauth_email' => 'test@mpptrade.pl',
            'oauth_verified' => true,
            'oauth_domain' => 'mpptrade.pl',
            'oauth_linked_providers' => ['google'],
        ]);

        $this->assertTrue($user->isOAuthUser());
        $this->assertTrue($user->isVerifiedOAuthUser());
        $this->assertEquals('Google Workspace', $user->getOAuthProviderDisplayName());
        $this->assertEquals('mpptrade.pl', $user->getOAuthDomainAttribute());
        $this->assertTrue($user->isOAuthDomainAllowed());
        $this->assertFalse($user->canLinkOAuthProvider('google'));
        $this->assertTrue($user->canLinkOAuthProvider('microsoft'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}