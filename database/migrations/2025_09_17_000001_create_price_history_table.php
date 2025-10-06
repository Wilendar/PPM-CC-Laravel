<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create price_history table
 *
 * FAZA 4: PRICE MANAGEMENT SYSTEM - Audit Trail
 *
 * Business Requirements:
 * - Kompletny audit trail wszystkich zmian w system price management
 * - Support dla bulk operations z batch tracking
 * - Polymorphic relationships dla różnych source entities
 * - Strategic indexing dla performance w large datasets
 * - JSON storage dla change details z proper casting
 *
 * Performance Considerations:
 * - Indexes na frequently queried fields (created_at, created_by, action)
 * - Polymorphic index dla historyable lookups
 * - Batch operations index dla bulk operations tracking
 * - Partition-ready structure dla future scaling
 *
 * @version FAZA 4 - PRICE MANAGEMENT
 * @since 2025-09-17
 */
return new class extends Migration
{
    /**
     * Run the migrations - Create price_history table
     */
    public function up(): void
    {
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship do source model (PriceGroup, ProductPrice)
            $table->string('historyable_type'); // Full class name
            $table->unsignedBigInteger('historyable_id'); // Source model ID (0 for bulk operations)

            // Action tracking
            $table->enum('action', [
                'created',      // New price/group created
                'updated',      // Price/group updated
                'deleted',      // Price/group deleted
                'bulk_update',  // Mass price update operation
                'import',       // Price imported from external source
                'sync',         // ERP/PrestaShop synchronization
                'restore',      // Price restored from backup
            ])->index();

            // Change tracking - JSON fields dla flexible storage
            $table->json('old_values')->nullable(); // Previous values
            $table->json('new_values')->nullable(); // New values after change
            $table->json('changed_fields')->nullable(); // Array of field names that changed

            // Business context
            $table->text('change_reason')->nullable(); // Human readable reason

            // Bulk operations support
            $table->string('batch_id', 100)->nullable()->index(); // UUID dla bulk operations
            $table->decimal('adjustment_percentage', 8, 2)->nullable(); // % adjustment w bulk updates
            $table->enum('adjustment_type', [
                'percentage',   // Percentage increase/decrease
                'fixed_amount', // Fixed amount add/subtract
                'set_margin',   // Set specific margin %
                'set_price',    // Set specific price
            ])->nullable();
            $table->unsignedInteger('affected_products_count')->nullable(); // Count w batch operations

            // Source tracking
            $table->enum('source', [
                'admin_panel',  // Manual change through admin interface
                'api',          // API call (internal/external)
                'import',       // CSV/Excel import
                'erp_sync',     // ERP system synchronization
                'prestashop_sync', // PrestaShop synchronization
                'system',       // System automated change
                'migration',    // Database migration/seeding
            ])->default('admin_panel')->index();

            // Additional metadata - JSON dla flexibility
            $table->json('metadata')->nullable(); // IP, browser, additional context

            // User tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            // Timestamps - tylko created_at, audit log nie potrzebuje updated_at
            $table->timestamp('created_at')->useCurrent()->index();

            // Indexes dla performance
            $table->index(['historyable_type', 'historyable_id'], 'idx_historyable');
            $table->index(['created_at', 'action'], 'idx_created_action');
            $table->index(['created_by', 'created_at'], 'idx_user_date');
            $table->index(['batch_id', 'created_at'], 'idx_batch_date');
            $table->index(['source', 'created_at'], 'idx_source_date');
        });
    }

    /**
     * Reverse the migrations - Drop price_history table
     */
    public function down(): void
    {
        Schema::dropIfExists('price_history');
    }
};