<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_07 FAZA 5 - Migration 1/5
     *
     * Creates import_batches table for tracking import operations (XLSX and PrestaShop API).
     *
     * PURPOSE:
     * - Track every import job with full audit trail
     * - Monitor progress in real-time (total_rows, processed_rows)
     * - Provide statistics (imported_products, failed_products, conflicts_count)
     * - Enable batch-based conflict resolution workflow
     *
     * BUSINESS RULES:
     * - Cascade delete: if user deleted → batches deleted
     * - Set null: if shop deleted → batch preserved but shop_id=null
     * - Status progression: pending → processing → completed/failed
     * - Track timing: started_at, completed_at for performance analysis
     *
     * IMPORT TYPES:
     * - xlsx: File-based import (filename stored)
     * - prestashop_api: API-based import (shop_id stored)
     *
     * EXAMPLES:
     * - user_id=1, import_type='xlsx', filename='variants_2025-11-04.xlsx', status='completed'
     * - user_id=2, import_type='prestashop_api', shop_id=3, status='processing'
     *
     * RELATIONSHIPS:
     * - belongs to User (cascade delete)
     * - belongs to PrestaShopShop (set null on delete)
     * - has many ConflictLogs (child table)
     */
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();

            // User relation (cascade delete)
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Import source type
            $table->enum('import_type', ['xlsx', 'prestashop_api'])
                  ->comment('Source: XLSX file or PrestaShop API');

            // Source identifiers
            $table->string('filename')->nullable()
                  ->comment('XLSX filename (for xlsx imports)');

            $table->foreignId('shop_id')->nullable()
                  ->constrained('prestashop_shops')
                  ->nullOnDelete()
                  ->comment('PrestaShop shop (for API imports)');

            // Status tracking
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending')
                  ->comment('Current batch status');

            // Progress counters
            $table->integer('total_rows')->default(0)
                  ->comment('Total rows in source (file or API response)');

            $table->integer('processed_rows')->default(0)
                  ->comment('Current progress (rows processed so far)');

            $table->integer('imported_products')->default(0)
                  ->comment('Successfully imported products count');

            $table->integer('failed_products')->default(0)
                  ->comment('Failed products count');

            $table->integer('conflicts_count')->default(0)
                  ->comment('Duplicate SKU conflicts requiring resolution');

            // Timing
            $table->timestamp('started_at')->nullable()
                  ->comment('When processing started');

            $table->timestamp('completed_at')->nullable()
                  ->comment('When processing completed/failed');

            // Error handling
            $table->text('error_message')->nullable()
                  ->comment('Error message for failed batches');

            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'status'], 'idx_import_user_status');
            $table->index(['import_type', 'status'], 'idx_import_type_status');
            $table->index('created_at', 'idx_import_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
