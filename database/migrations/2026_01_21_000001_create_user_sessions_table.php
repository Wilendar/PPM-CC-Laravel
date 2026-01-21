<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ETAP_04 FAZA A: User Sessions Table
 *
 * Extended session tracking with device detection, geolocation,
 * and security monitoring capabilities.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id', 255)->unique();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();

            // Device detection
            $table->enum('device_type', ['desktop', 'mobile', 'tablet', 'unknown'])->default('unknown');
            $table->string('browser', 100)->nullable();
            $table->string('browser_version', 50)->nullable();
            $table->string('os', 100)->nullable();
            $table->string('os_version', 50)->nullable();

            // Geolocation (optional, from IP)
            $table->string('country', 100)->nullable();
            $table->string('country_code', 5)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('region', 100)->nullable();

            // Session status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_suspicious')->default(false);
            $table->text('suspicious_reason')->nullable();

            // Activity tracking
            $table->timestamp('last_activity')->useCurrent();
            $table->string('last_url', 500)->nullable();

            // Session termination
            $table->timestamp('ended_at')->nullable();
            $table->enum('end_reason', [
                'logout',
                'timeout',
                'force_logout_admin',
                'concurrent_limit',
                'security_block',
                'password_change',
                'user_blocked',
                'bulk_force_logout',
                'session_expired'
            ])->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'is_active']);
            $table->index('last_activity');
            $table->index('ip_address');
            $table->index('is_suspicious');
            $table->index(['is_active', 'last_activity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
