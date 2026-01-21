<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ETAP_04 FAZA A: Security Alerts Table
 *
 * Stores security alerts for admin review and action.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_alerts', function (Blueprint $table) {
            $table->id();

            // Alert type and severity
            $table->string('alert_type', 50); // brute_force, suspicious_ip, multiple_sessions, unusual_location, etc.
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');

            // Alert details
            $table->string('title', 255);
            $table->text('message');
            $table->json('details')->nullable(); // Additional context data

            // Related entities
            $table->foreignId('related_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('related_ip', 45)->nullable();
            $table->foreignId('related_session_id')->nullable()->constrained('user_sessions')->nullOnDelete();

            // Status
            $table->boolean('acknowledged')->default(false);
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('acknowledgment_notes')->nullable();

            // Resolution
            $table->boolean('resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();

            // Auto-expire old alerts
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['severity', 'acknowledged']);
            $table->index(['alert_type', 'created_at']);
            $table->index('related_user_id');
            $table->index('related_ip');
            $table->index(['acknowledged', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_alerts');
    }
};
