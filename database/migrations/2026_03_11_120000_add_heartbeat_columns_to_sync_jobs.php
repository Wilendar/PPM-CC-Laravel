<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add heartbeat monitoring columns to sync_jobs table.
 *
 * Enables real-time worker health monitoring via sonar dot indicator,
 * matching the existing heartbeat system in job_progress/worker_heartbeats.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_jobs', function (Blueprint $table) {
            $table->unsignedInteger('worker_pid')->nullable()->after('queue_attempts');
            $table->timestamp('last_heartbeat_at')->nullable()->after('worker_pid');
        });
    }

    public function down(): void
    {
        Schema::table('sync_jobs', function (Blueprint $table) {
            $table->dropColumn(['worker_pid', 'last_heartbeat_at']);
        });
    }
};
