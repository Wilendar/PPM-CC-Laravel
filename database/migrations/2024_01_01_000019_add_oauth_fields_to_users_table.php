<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FAZA D: OAuth2 + Advanced Features
     * 19. OAuth Integration Fields - rozszerzenie users table o pola OAuth2
     * 
     * Dodaje pola wymagane dla integracji OAuth2 z:
     * - Google Workspace (Google Cloud Console)
     * - Microsoft Entra ID (Azure AD)
     * - Generic OAuth2 providers
     * 
     * Obejmuje:
     * - Provider identification i external IDs
     * - OAuth tokens i refresh tokens
     * - Provider-specific user data
     * - Account linking i verification
     * - Security tracking dla OAuth flows
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // === SEKCJA 1: OAUTH PROVIDER IDENTIFICATION ===
            // Identyfikacja dostawcy OAuth i external user ID
            $table->string('oauth_provider', 50)->nullable()->after('notification_settings');
            $table->string('oauth_id', 100)->nullable()->after('oauth_provider');
            $table->string('oauth_email', 255)->nullable()->after('oauth_id');
            
            // === SEKCJA 2: OAUTH TOKENS ===
            // Przechowywanie tokenów OAuth (encrypted w aplikacji)
            $table->text('oauth_access_token')->nullable()->after('oauth_email');
            $table->text('oauth_refresh_token')->nullable()->after('oauth_access_token');
            $table->timestamp('oauth_token_expires_at')->nullable()->after('oauth_refresh_token');
            
            // === SEKCJA 3: PROVIDER-SPECIFIC DATA ===
            // Dane specyficzne dla dostawcy (profile foto, workplace info, etc.)
            $table->json('oauth_provider_data')->nullable()->after('oauth_token_expires_at');
            $table->string('oauth_avatar_url', 500)->nullable()->after('oauth_provider_data');
            
            // === SEKCJA 4: ACCOUNT LINKING & VERIFICATION ===
            // Zarządzanie powiązaniem kont i weryfikacja
            $table->boolean('oauth_verified')->default(false)->after('oauth_avatar_url');
            $table->timestamp('oauth_linked_at')->nullable()->after('oauth_verified');
            $table->string('oauth_domain', 100)->nullable()->after('oauth_linked_at'); // dla domain restrictions
            
            // === SEKCJA 5: SECURITY TRACKING ===
            // Śledzenie bezpieczeństwa dla OAuth flows
            $table->timestamp('oauth_last_used_at')->nullable()->after('oauth_domain');
            $table->integer('oauth_login_attempts')->default(0)->after('oauth_last_used_at');
            $table->timestamp('oauth_locked_until')->nullable()->after('oauth_login_attempts');
            
            // === SEKCJA 6: MULTI-PROVIDER SUPPORT ===
            // Wsparcie dla wielu dostawców OAuth na jednym koncie
            $table->json('oauth_linked_providers')->nullable()->after('oauth_locked_until');
            $table->string('primary_auth_method', 20)->default('local')->after('oauth_linked_providers'); // local, google, microsoft
            
            // === INDEKSY WYDAJNOŚCIOWE ===
            // Indeksy dla OAuth queries
            $table->index(['oauth_provider', 'oauth_id'], 'idx_users_oauth_provider');
            $table->index('oauth_email', 'idx_users_oauth_email');
            $table->index(['oauth_verified', 'oauth_domain'], 'idx_users_oauth_verification');
            $table->index('oauth_last_used_at', 'idx_users_oauth_activity');
            $table->index('primary_auth_method', 'idx_users_auth_method');
        });
        
        // === UNIQUE CONSTRAINTS ===
        // Zapobieganie duplikatom OAuth accounts
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['oauth_provider', 'oauth_id'], 'uk_users_oauth_provider_id');
        });
        
        // === KOMENTARZE TABELI ===
        DB::statement("ALTER TABLE users COMMENT = 'Extended users table with OAuth2 integration fields for Google Workspace and Microsoft Entra ID'");
    }

    /**
     * Cofnięcie zmian - usuwa wszystkie pola OAuth
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Usuń indeksy przed usunięciem kolumn
            $table->dropIndex('idx_users_oauth_provider');
            $table->dropIndex('idx_users_oauth_email');
            $table->dropIndex('idx_users_oauth_verification');
            $table->dropIndex('idx_users_oauth_activity');
            $table->dropIndex('idx_users_auth_method');
            $table->dropUnique('uk_users_oauth_provider_id');
            
            // Usuń kolumny OAuth w odwrotnej kolejności
            $table->dropColumn([
                'primary_auth_method',
                'oauth_linked_providers',
                'oauth_locked_until',
                'oauth_login_attempts',
                'oauth_last_used_at',
                'oauth_domain',
                'oauth_linked_at',
                'oauth_verified',
                'oauth_avatar_url',
                'oauth_provider_data',
                'oauth_token_expires_at',
                'oauth_refresh_token',
                'oauth_access_token',
                'oauth_email',
                'oauth_id',
                'oauth_provider'
            ]);
        });
    }
};