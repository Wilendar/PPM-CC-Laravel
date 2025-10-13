<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Category Preview Table
 *
 * ETAP_07 FAZA 3D: Category Import Preview System
 *
 * Purpose: Temporary storage dla category preview data przed bulk import
 * Business Logic: Preview missing categories → user approval → bulk create
 * Data Retention: Auto-expires after 1 hour (cleanup cron)
 *
 * Table: category_preview
 * - Stores hierarchical category tree structure (JSON)
 * - Links to job_progress via job_id (UUID)
 * - Tracks user selection and approval status
 * - Automatic expiration dla memory efficiency
 *
 * Performance:
 * - Indexes on job_id, shop_id, status, expires_at
 * - Foreign key cascade delete on shop removal
 *
 * @package PPM-CC-Laravel
 * @version 1.0
 * @since 2025-10-08
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates category_preview table dla temporary preview storage
     */
    public function up(): void
    {
        Schema::create('category_preview', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Job tracking - links to job_progress.job_id
            $table->uuid('job_id')->index()->comment('UUID linking to job_progress');

            // Shop association - foreign key z cascade delete
            $table->foreignId('shop_id')
                  ->constrained('prestashop_shops')
                  ->onDelete('cascade')
                  ->comment('PrestaShop shop reference');

            // Category tree data (denormalized JSON dla performance)
            $table->json('category_tree_json')
                  ->comment('Hierarchical category tree structure');

            // Category count metadata
            $table->unsignedInteger('total_categories')
                  ->default(0)
                  ->comment('Total number of categories in tree');

            // User selection after preview (nullable until user approves)
            $table->json('user_selection_json')
                  ->nullable()
                  ->comment('User-selected category IDs after preview');

            // Preview status tracking
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])
                  ->default('pending')
                  ->index()
                  ->comment('Preview approval status');

            // Expiration tracking dla automatic cleanup
            $table->timestamp('expires_at')
                  ->index()
                  ->comment('Auto-expiration timestamp (cleanup after 1h)');

            // Standard timestamps
            $table->timestamps();

            // Composite indexes dla performance
            $table->index(['job_id', 'shop_id'], 'idx_job_shop');
            $table->index(['shop_id', 'status'], 'idx_shop_status');

            // Comment dla database documentation
            $table->comment('Temporary category preview storage dla bulk import workflow');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops category_preview table
     */
    public function down(): void
    {
        Schema::dropIfExists('category_preview');
    }
};
