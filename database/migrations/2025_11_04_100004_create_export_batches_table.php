<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_07 FAZA 5 - Migration 4/5
     *
     * Creates export_batches table for tracking export operations (XLSX and PrestaShop API).
     *
     * PURPOSE:
     * - Track every export job with full audit trail
     * - Monitor export progress (total_products, exported_products)
     * - Store export filters for reproducibility
     * - Provide export history for users
     *
     * BUSINESS RULES:
     * - Cascade delete: if user deleted → batches deleted
     * - Set null: if shop deleted → batch preserved but shop_id=null
     * - Status progression: pending → processing → completed/failed
     * - Track timing: started_at, completed_at for performance analysis
     *
     * EXPORT TYPES:
     * - xlsx: File-based export (filename stored)
     * - prestashop_api: API-based sync (shop_id stored)
     *
     * FILTERS FORMAT (JSON):
     * {
     *   "has_variants": true,
     *   "category_id": 5,
     *   "price_group": "detaliczna",
     *   "warehouse": "MPPTRADE"
     * }
     *
     * EXAMPLES:
     * - user_id=1, export_type='xlsx', filename='export_2025-11-04.xlsx', status='completed'
     * - user_id=2, export_type='prestashop_api', shop_id=3, status='processing'
     *
     * RELATIONSHIPS:
     * - belongs to User (cascade delete)
     * - belongs to PrestaShopShop (set null on delete)
     */
    public function up(): void
    {
        Schema::create('export_batches', function (Blueprint $table) {
            $table->id();

            // User relation (cascade delete)
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete()
                  ->comment('User who initiated export');

            // Export destination type
            $table->enum('export_type', ['xlsx', 'prestashop_api'])
                  ->comment('Destination: XLSX file or PrestaShop API');

            // Destination identifiers
            $table->foreignId('shop_id')->nullable()
                  ->constrained('prestashop_shops')
                  ->nullOnDelete()
                  ->comment('PrestaShop shop (for API exports)');

            $table->string('filename')->nullable()
                  ->comment('Generated filename (for XLSX exports)');

            // Status tracking
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending')
                  ->comment('Current batch status');

            // Progress counters
            $table->integer('total_products')->default(0)
                  ->comment('Total products to export');

            $table->integer('exported_products')->default(0)
                  ->comment('Successfully exported products count');

            $table->integer('failed_products')->default(0)
                  ->comment('Failed products count');

            // Export configuration
            $table->json('filters')->nullable()
                  ->comment('Export filters: {"has_variants": true, "category_id": 5}');

            // Timing
            $table->timestamp('started_at')->nullable()
                  ->comment('When export started');

            $table->timestamp('completed_at')->nullable()
                  ->comment('When export completed/failed');

            // Error handling
            $table->text('error_message')->nullable()
                  ->comment('Error message for failed exports');

            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'status'], 'idx_export_user_status');
            $table->index(['export_type', 'status'], 'idx_export_type_status');
            $table->index('created_at', 'idx_export_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_batches');
    }
};
