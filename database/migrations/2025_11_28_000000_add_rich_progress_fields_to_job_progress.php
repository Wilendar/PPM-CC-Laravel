<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * ETAP_07c: Add rich progress fields to job_progress table
 *
 * NEW FIELDS:
 * - user_id: Track who initiated the job (audit trail + filtering)
 * - metadata: Flexible JSON for job-specific context (mode, filters, options)
 * - action_button: UI action config for completed jobs (retry, view details)
 *
 * NEW JOB TYPES:
 * - category_analysis: Background category analysis before import
 * - bulk_export: Export products to PrestaShop
 * - bulk_update: Update products on PrestaShop
 * - stock_sync: Synchronize stock levels
 * - price_sync: Synchronize prices
 *
 * NEW STATUS:
 * - awaiting_user: Job paused, waiting for user action (e.g., category preview)
 *
 * @see Plan_Projektu/ETAP_07c_Import_UX_Redesign.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_progress', function (Blueprint $table) {
            // User tracking - who initiated this job
            $table->unsignedBigInteger('user_id')
                  ->nullable()
                  ->after('shop_id');

            // Flexible metadata for job-specific context
            $table->json('metadata')
                  ->nullable()
                  ->after('error_details');

            // UI action button config for completed jobs
            $table->json('action_button')
                  ->nullable()
                  ->after('metadata');

            // Index for user filtering
            $table->index('user_id', 'idx_job_progress_user_id');

            // Composite index for efficient polling queries
            $table->index(['user_id', 'status', 'updated_at'], 'idx_job_progress_user_status_updated');

            // Foreign key to users table
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });

        // Extend job_type enum with new types
        // Note: MySQL ENUM modification requires raw SQL
        DB::statement("ALTER TABLE job_progress MODIFY COLUMN job_type ENUM(
            'import',
            'sync',
            'export',
            'category_delete',
            'category_analysis',
            'bulk_export',
            'bulk_update',
            'stock_sync',
            'price_sync'
        ) NOT NULL");

        // Extend status enum with awaiting_user
        DB::statement("ALTER TABLE job_progress MODIFY COLUMN status ENUM(
            'pending',
            'running',
            'completed',
            'failed',
            'awaiting_user'
        ) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('job_progress', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['user_id']);

            // Drop indexes
            $table->dropIndex('idx_job_progress_user_id');
            $table->dropIndex('idx_job_progress_user_status_updated');

            // Drop columns
            $table->dropColumn(['user_id', 'metadata', 'action_button']);
        });

        // Revert job_type enum
        DB::statement("ALTER TABLE job_progress MODIFY COLUMN job_type ENUM(
            'import',
            'sync',
            'export',
            'category_delete'
        ) NOT NULL");

        // Revert status enum
        DB::statement("ALTER TABLE job_progress MODIFY COLUMN status ENUM(
            'pending',
            'running',
            'completed',
            'failed'
        ) NOT NULL DEFAULT 'pending'");
    }
};
