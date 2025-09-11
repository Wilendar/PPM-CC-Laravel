<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_usage_logs', function (Blueprint $table) {
            $table->id();
            
            // Request details
            $table->string('endpoint');
            $table->string('method');
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            
            // Authentication
            $table->foreignId('user_id')->nullable()
                  ->constrained('users');
            $table->string('api_key_id')->nullable();
            
            // Response details
            $table->integer('response_code');
            $table->integer('response_time_ms');
            $table->integer('response_size_bytes')->nullable();
            
            // Rate limiting
            $table->integer('rate_limit_remaining')->nullable();
            $table->boolean('rate_limited')->default(false);
            
            // Request/Response data
            $table->json('request_params')->nullable();
            $table->json('response_headers')->nullable();
            $table->text('error_message')->nullable();
            
            // Security monitoring
            $table->boolean('suspicious')->default(false);
            $table->text('security_notes')->nullable();
            
            $table->timestamp('requested_at');
            
            // Indexes for performance and monitoring
            $table->index(['endpoint', 'requested_at']);
            $table->index(['user_id', 'requested_at']);
            $table->index(['ip_address', 'requested_at']);
            $table->index(['response_code', 'requested_at']);
            $table->index(['suspicious', 'requested_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_usage_logs');
    }
};