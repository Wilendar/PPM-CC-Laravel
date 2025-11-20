<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * FAZA 9: Smart Status Logic (2025-11-12)
     * Adds 'completed_with_errors' status to sync_jobs.status ENUM
     *
     * STATUS LOGIC:
     * - completed: All items succeeded (0 failures)
     * - completed_with_errors: Partial success (some succeeded, some failed)
     * - failed: All items failed
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE sync_jobs
            MODIFY COLUMN status ENUM(
                'pending',
                'running',
                'paused',
                'completed',
                'completed_with_errors',
                'failed',
                'cancelled',
                'timeout'
            ) DEFAULT 'pending' COMMENT 'Status zadania'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'completed_with_errors' from ENUM
        DB::statement("
            ALTER TABLE sync_jobs
            MODIFY COLUMN status ENUM(
                'pending',
                'running',
                'paused',
                'completed',
                'failed',
                'cancelled',
                'timeout'
            ) DEFAULT 'pending' COMMENT 'Status zadania'
        ");
    }
};
