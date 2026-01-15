<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05d FAZA 1 - Migration 4/4
     *
     * Creates compatibility_bulk_operations table for audit logging.
     *
     * PURPOSE:
     * - Track all bulk compatibility operations
     * - Enable undo/rollback of bulk changes
     * - Audit trail for compliance
     * - Performance monitoring (operation timing)
     *
     * OPERATION TYPES:
     * - add: Bulk add compatibility records
     * - remove: Bulk remove compatibility records
     * - verify: Bulk verify compatibility records
     * - copy: Copy compatibility from one shop to another
     * - apply_suggestions: Apply AI suggestions in bulk
     * - import: Import from Excel/CSV
     *
     * BUSINESS RULES:
     * - All bulk operations MUST be logged
     * - operation_data contains: product_ids, vehicle_ids, attribute_id, shop_id
     * - affected_records stored for undo functionality
     * - Status tracking: pending → processing → completed/failed
     */
    public function up(): void
    {
        Schema::create('compatibility_bulk_operations', function (Blueprint $table) {
            $table->id();

            // Operation type
            $table->enum('operation_type', [
                'add',
                'remove',
                'verify',
                'copy',
                'apply_suggestions',
                'import'
            ])->comment('Type of bulk operation');

            // User who initiated
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Shop context (null = global/all shops)
            $table->foreignId('shop_id')
                  ->nullable()
                  ->constrained('prestashop_shops')
                  ->cascadeOnDelete();

            // Operation data (JSON)
            $table->json('operation_data')
                  ->comment('Input parameters: product_ids, vehicle_ids, attribute_id, etc.');

            // Affected records for undo
            $table->json('affected_records')
                  ->nullable()
                  ->comment('Records affected for potential undo');

            // Results
            $table->integer('affected_rows')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('error_count')->default(0);

            // Status tracking
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled'
            ])->default('pending');

            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable()
                  ->comment('Detailed error info for debugging');

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_ms')->nullable()
                  ->comment('Operation duration in milliseconds');

            $table->timestamps();

            // Performance indexes
            $table->index('user_id', 'idx_bulk_op_user');
            $table->index('shop_id', 'idx_bulk_op_shop');
            $table->index('status', 'idx_bulk_op_status');
            $table->index('operation_type', 'idx_bulk_op_type');
            $table->index('created_at', 'idx_bulk_op_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compatibility_bulk_operations');
    }
};
