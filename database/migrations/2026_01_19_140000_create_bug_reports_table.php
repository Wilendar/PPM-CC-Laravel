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
        Schema::create('bug_reports', function (Blueprint $table) {
            $table->id();

            // Core fields
            $table->string('title', 255);
            $table->text('description');
            $table->text('steps_to_reproduce')->nullable();

            // Type & Status
            $table->enum('type', ['bug', 'feature_request', 'improvement', 'question', 'support'])
                ->default('bug');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])
                ->default('medium');
            $table->enum('status', ['new', 'in_progress', 'waiting', 'resolved', 'closed', 'rejected'])
                ->default('new');

            // Context & Diagnostics
            $table->string('context_url', 2048)->nullable();
            $table->string('browser_info', 512)->nullable();
            $table->string('os_info', 255)->nullable();
            $table->json('console_errors')->nullable();
            $table->json('user_actions')->nullable();
            $table->string('screenshot_path', 255)->nullable();

            // Relations (without FK constraints - users table may not exist)
            $table->unsignedBigInteger('reporter_id');
            $table->unsignedBigInteger('assigned_to')->nullable();

            // Resolution
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('type');
            $table->index('severity');
            $table->index('reporter_id');
            $table->index('assigned_to');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bug_reports');
    }
};
