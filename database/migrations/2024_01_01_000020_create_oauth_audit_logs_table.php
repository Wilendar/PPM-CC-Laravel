<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FAZA D: OAuth2 + Advanced Features
     * 20. OAuth Audit Logs - dedykowana tabela dla OAuth security logging
     * 
     * Rozszerza system audit o specjalistyczne logowanie OAuth2:
     * - OAuth login attempts (successful/failed)
     * - Provider-specific user data sync
     * - Domain verification events
     * - Account linking/unlinking activities
     * - Third-party access grants/revocations
     * - Suspicious OAuth activity detection
     * - GDPR compliance audit exports
     */
    public function up(): void
    {
        Schema::create('oauth_audit_logs', function (Blueprint $table) {
            $table->id();
            
            // === SEKCJA 1: CORE AUDIT FIELDS ===
            // Basic audit information
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('oauth_provider', 50)->index(); // google, microsoft, etc.
            $table->string('oauth_action', 100)->index(); // login.success, link.account, etc.
            $table->string('oauth_event_type', 50)->index(); // authentication, authorization, sync, security
            
            // === SEKCJA 2: REQUEST CONTEXT ===
            // Information about the OAuth request
            $table->string('oauth_session_id', 100)->nullable()->index();
            $table->string('oauth_state', 255)->nullable(); // OAuth state parameter
            $table->string('oauth_client_id', 255)->nullable(); // OAuth client identifier
            $table->string('oauth_redirect_uri', 500)->nullable();
            
            // === SEKCJA 3: USER CONTEXT ===
            // User and request information
            $table->string('oauth_email', 255)->nullable()->index();
            $table->string('oauth_domain', 100)->nullable()->index();
            $table->string('oauth_external_id', 100)->nullable()->index();
            $table->ipAddress('ip_address')->index();
            $table->text('user_agent')->nullable();
            
            // === SEKCJA 4: DETAILED EVENT DATA ===
            // Comprehensive event information
            $table->json('oauth_request_data')->nullable(); // Request parameters
            $table->json('oauth_response_data')->nullable(); // Provider response
            $table->json('oauth_token_info')->nullable(); // Token metadata (no actual tokens!)
            $table->json('oauth_profile_data')->nullable(); // Synced profile information
            $table->json('oauth_permissions')->nullable(); // Granted permissions/scopes
            
            // === SEKCJA 5: SECURITY TRACKING ===
            // Security and compliance information
            $table->string('security_level', 20)->default('normal')->index(); // normal, suspicious, critical
            $table->json('security_indicators')->nullable(); // Flags for suspicious activity
            $table->string('compliance_category', 50)->nullable()->index(); // GDPR, SOX, etc.
            $table->boolean('requires_review')->default(false)->index();
            
            // === SEKCJA 6: STATUS AND OUTCOME ===
            // Result tracking
            $table->string('status', 20)->index(); // success, failure, pending, blocked
            $table->text('error_message')->nullable();
            $table->string('error_code', 50)->nullable()->index();
            $table->integer('attempt_number')->default(1)->index(); // For retry tracking
            
            // === SEKCJA 7: TIMESTAMPS ===
            // Detailed timing information
            $table->timestamp('oauth_initiated_at')->nullable()->index();
            $table->timestamp('oauth_completed_at')->nullable()->index();
            $table->integer('processing_time_ms')->nullable(); // Response time tracking
            $table->timestamps(); // created_at, updated_at
            
            // === SEKCJA 8: RETENTION AND ARCHIVAL ===
            // Data lifecycle management
            $table->timestamp('archived_at')->nullable()->index();
            $table->string('retention_policy', 50)->default('standard')->index(); // standard, extended, permanent
            $table->boolean('is_sensitive')->default(false)->index(); // For GDPR handling
            
            // === FOREIGN KEY CONSTRAINTS ===
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            // === COMPOSITE INDEXES ===
            // Performance indexes dla common queries
            $table->index(['oauth_provider', 'oauth_action', 'created_at'], 'idx_oauth_audit_provider_action_time');
            $table->index(['user_id', 'oauth_provider', 'created_at'], 'idx_oauth_audit_user_provider_time');
            $table->index(['security_level', 'requires_review', 'created_at'], 'idx_oauth_audit_security_review');
            $table->index(['status', 'oauth_event_type', 'created_at'], 'idx_oauth_audit_status_event');
            $table->index(['oauth_domain', 'oauth_action', 'created_at'], 'idx_oauth_audit_domain_action');
            $table->index(['compliance_category', 'is_sensitive'], 'idx_oauth_audit_compliance');
            $table->index(['archived_at', 'retention_policy'], 'idx_oauth_audit_retention');
        });
        
        // === TABLE COMMENT ===
        DB::statement("ALTER TABLE oauth_audit_logs COMMENT = 'OAuth2 specific audit logging with security tracking and compliance features'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_audit_logs');
    }
};