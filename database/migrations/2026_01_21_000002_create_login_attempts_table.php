<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ETAP_04 FAZA A: Login Attempts Table
 *
 * Tracks all login attempts for security monitoring,
 * brute force detection, and audit compliance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();

            // Attempt result
            $table->boolean('success')->default(false);
            $table->string('failure_reason', 100)->nullable();

            // Link to user if found
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Device info
            $table->string('device_type', 50)->nullable();
            $table->string('browser', 100)->nullable();

            // Geolocation
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();

            // OAuth attempt
            $table->string('oauth_provider', 50)->nullable();

            $table->timestamp('attempted_at')->useCurrent();

            // Indexes for security queries
            $table->index(['email', 'attempted_at']);
            $table->index(['ip_address', 'attempted_at']);
            $table->index(['success', 'attempted_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
