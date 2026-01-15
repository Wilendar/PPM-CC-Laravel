<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Publish History Table
 *
 * ETAP_06 Import/Export - FAZA 1
 *
 * Tabela audit trail dla operacji publikacji produktow.
 * Zapisuje kazda publikacje z pending_products do products
 * wraz z informacja o sync do PrestaShop.
 *
 * @package Database\Migrations
 * @since 2025-12-08
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('publish_history', function (Blueprint $table) {
            $table->id();

            // ===========================================
            // LINKAGE
            // ===========================================

            // Source: PendingProduct (moze byc NULL jesli soft-deleted)
            $table->foreignId('pending_product_id')
                  ->nullable()
                  ->constrained('pending_products')
                  ->nullOnDelete();

            // Target: Product created during publication
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade');

            // Who published
            $table->foreignId('published_by')
                  ->constrained('users')
                  ->onDelete('cascade');

            // ===========================================
            // PUBLICATION DETAILS
            // ===========================================

            // Publication timestamp
            $table->timestamp('published_at');

            // SKU snapshot (for audit even if pending_product deleted)
            $table->string('sku_snapshot', 128);

            // Name snapshot
            $table->string('name_snapshot', 255)->nullable();

            // ===========================================
            // WHAT WAS PUBLISHED (SNAPSHOTS)
            // ===========================================

            // Shops where product was published [1, 3, 5]
            $table->json('published_shops');

            // Categories assigned during publication [3, 7, 12]
            $table->json('published_categories');

            // Number of media files moved
            $table->unsignedInteger('published_media_count')->default(0);

            // Variants created count
            $table->unsignedInteger('published_variants_count')->default(0);

            // ===========================================
            // PRESTASHOP SYNC TRACKING
            // ===========================================

            // Array of dispatched job UUIDs
            $table->json('sync_jobs_dispatched')->nullable();

            // Overall sync status
            $table->enum('sync_status', [
                'pending',      // Jobs dispatched but not started
                'in_progress',  // At least one job running
                'completed',    // All jobs completed successfully
                'partial',      // Some jobs failed, some succeeded
                'failed',       // All jobs failed
            ])->default('pending');

            // Sync completion timestamp
            $table->timestamp('sync_completed_at')->nullable();

            // Sync error details (JSON)
            $table->json('sync_errors')->nullable();

            // ===========================================
            // METADATA
            // ===========================================

            // Publication mode (single vs bulk)
            $table->enum('publish_mode', ['single', 'bulk'])->default('single');

            // If bulk: batch ID for grouping
            $table->uuid('batch_id')->nullable();

            // Processing time (ms)
            $table->unsignedInteger('processing_time_ms')->nullable();

            $table->timestamps();

            // ===========================================
            // INDEXES
            // ===========================================
            $table->index('pending_product_id');
            $table->index('product_id');
            $table->index('published_by');
            $table->index('published_at');
            $table->index('sync_status');
            $table->index('batch_id');
            $table->index('sku_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publish_history');
    }
};
