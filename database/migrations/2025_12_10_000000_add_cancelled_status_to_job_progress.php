<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add 'cancelled' and 'awaiting_user' status to job_progress table
 *
 * FIX (2025-12-10): Extend ENUM to support job cancellation and awaiting user status
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify ENUM to include new statuses
        DB::statement("ALTER TABLE job_progress MODIFY COLUMN status ENUM('pending', 'running', 'completed', 'failed', 'cancelled', 'awaiting_user') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original ENUM (will fail if any rows have new status values)
        DB::statement("ALTER TABLE job_progress MODIFY COLUMN status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending'");
    }
};
