<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Real-Time Progress Tracking for PrestaShop Import/Export Operations
     *
     * FEATURES:
     * - Track job progress in real-time (current/total counts)
     * - Store error details for failed products (SKU-specific)
     * - Status tracking: pending, running, completed, failed
     * - Timestamps for started_at, completed_at (duration calculation)
     * - Multi-shop support with shop_id
     *
     * USAGE:
     * - BulkImportProducts: Track import progress from PrestaShop
     * - BulkSyncProducts: Track export/sync progress to PrestaShop
     * - ProductList UI: Display real-time progress bars
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('job_progress', function (Blueprint $table) {
            $table->id();

            // Job Identification
            $table->string('job_id', 255)->unique()->comment('Laravel queue job ID');
            $table->enum('job_type', ['import', 'sync', 'export'])->comment('Operation type');
            $table->unsignedBigInteger('shop_id')->nullable()->comment('Target PrestaShop shop');

            // Progress Tracking
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->unsignedInteger('current_count')->default(0)->comment('Processed items count');
            $table->unsignedInteger('total_count')->default(0)->comment('Total items to process');
            $table->unsignedInteger('error_count')->default(0)->comment('Failed items count');

            // Error Details (JSON for SKU-specific errors)
            // Format: [{"sku": "ABC123", "error": "Product already exists"}, ...]
            $table->json('error_details')->nullable()->comment('Array of errors with SKU/ID');

            // Timestamps
            $table->timestamp('started_at')->nullable()->comment('Job start timestamp');
            $table->timestamp('completed_at')->nullable()->comment('Job completion timestamp');
            $table->timestamps();

            // Indexes for efficient querying
            $table->index('job_id', 'idx_job_id');
            $table->index('shop_id', 'idx_shop_id');
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index(['shop_id', 'status'], 'idx_shop_status');

            // Foreign key constraint
            $table->foreign('shop_id')
                  ->references('id')
                  ->on('prestashop_shops')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('job_progress');
    }
};
