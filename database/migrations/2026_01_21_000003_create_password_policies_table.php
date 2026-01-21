<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ETAP_04 FAZA A: Password Policies Table
 *
 * Defines password requirements and expiration policies
 * that can be assigned to users or roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();

            // Password requirements
            $table->integer('min_length')->default(8);
            $table->integer('max_length')->default(128);
            $table->boolean('require_uppercase')->default(true);
            $table->boolean('require_lowercase')->default(true);
            $table->boolean('require_numbers')->default(true);
            $table->boolean('require_symbols')->default(false);

            // Password expiration
            $table->integer('expire_days')->default(0); // 0 = never expires
            $table->integer('warning_days_before_expire')->default(7);

            // History
            $table->integer('history_count')->default(3); // Can't reuse last N passwords

            // Lockout
            $table->integer('lockout_attempts')->default(5);
            $table->integer('lockout_duration_minutes')->default(30);

            // Default policy flag
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('is_default');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_policies');
    }
};
