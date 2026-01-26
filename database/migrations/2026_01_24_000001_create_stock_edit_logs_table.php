<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabela do śledzenia zmian w stanach magazynowych:
     * - unlock/lock kolumn
     * - edycje wartości
     * - synchronizacje do ERP
     */
    public function up(): void
    {
        Schema::create('stock_edit_logs', function (Blueprint $table) {
            $table->id();

            // Who
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // What product
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // Which warehouse (nullable for column-level actions)
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();

            // Which column was affected
            $table->enum('column_name', ['quantity', 'reserved', 'minimum']);

            // What action was taken
            $table->enum('action', ['unlock', 'lock', 'edit', 'sync_to_erp']);

            // Value changes (for edit actions)
            $table->decimal('old_value', 15, 4)->nullable();
            $table->decimal('new_value', 15, 4)->nullable();

            // ERP connection (for sync actions)
            $table->foreignId('erp_connection_id')
                ->nullable()
                ->constrained('erp_connections')
                ->nullOnDelete();

            // Additional metadata (IP, user agent, etc.)
            $table->json('metadata')->nullable();

            // Timestamp
            $table->timestamp('created_at')->useCurrent();

            // Indexes for common queries
            $table->index(['user_id', 'product_id'], 'idx_user_product');
            $table->index(['product_id', 'column_name'], 'idx_product_column');
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_edit_logs');
    }
};
