<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * DROP DEPRECATED TABLE: product_sync_status
     *
     * CONTEXT (2025-10-13):
     * Po ukończeniu konsolidacji OPCJA B wszystkie dane sync tracking
     * zostały przeniesione do product_shop_data. Tabela product_sync_status
     * jest już deprecated i może być usunięta.
     *
     * PREREQUISITE:
     * - Migration 2025_10_13_000001_consolidate_sync_tracking_to_product_shop_data.php
     *   MUSI być wykonana PRZED tą migracją!
     * - Wszystkie referencje do ProductSyncStatus w kodzie zostały zrefactorowane
     *   do ProductShopData
     *
     * SAFETY:
     * - Dane zostały już zmigrowane przez poprzednią migration
     * - Model ProductSyncStatus został usunięty z app/Models/
     * - Wszystkie Jobs/Services używają ProductShopData
     *
     * @package App\Database\Migrations
     * @version 1.0
     * @since 2025-10-13 - Table Drop After Consolidation
     */
    public function up(): void
    {
        // Verify previous consolidation migration was executed
        if (!Schema::hasColumn('product_shop_data', 'prestashop_product_id')) {
            throw new \RuntimeException(
                'Cannot drop product_sync_status table: ' .
                'Consolidation migration (2025_10_13_000001) has not been executed yet! ' .
                'Run consolidation migration first to migrate data to product_shop_data.'
            );
        }

        // Log table drop for audit trail
        Log::warning('Dropping deprecated table: product_sync_status', [
            'reason' => 'Table consolidated into product_shop_data',
            'migration' => '2025_10_13_000002',
            'date' => now()->toDateTimeString(),
        ]);

        // Drop the deprecated table
        Schema::dropIfExists('product_sync_status');

        Log::info('Table product_sync_status successfully dropped', [
            'migration' => '2025_10_13_000002',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * NOTE: Reverting this migration will RE-CREATE the table structure
     * but DATA WILL NOT BE RESTORED automatically!
     *
     * To restore data, you would need to:
     * 1. Rollback this migration (recreates empty table)
     * 2. Rollback consolidation migration (migrates data back from product_shop_data)
     */
    public function down(): void
    {
        // Re-create table structure (WITHOUT data migration)
        Schema::create('product_sync_status', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('shop_id');

            // PrestaShop external reference
            $table->unsignedBigInteger('prestashop_product_id')->nullable();
            $table->string('external_reference', 255)->nullable()
                ->comment('PrestaShop link_rewrite dla URL generation');

            // Sync status tracking
            $table->enum('sync_status', [
                'pending',
                'syncing',
                'synced',
                'error',
                'conflict',
                'disabled'
            ])->default('pending');

            $table->enum('sync_direction', [
                'ppm_to_ps',
                'ps_to_ppm',
                'bidirectional'
            ])->default('ppm_to_ps');

            // Timestamps
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_success_sync_at')->nullable();

            // Error handling
            $table->text('error_message')->nullable();

            // Conflict detection
            $table->json('conflict_data')->nullable();
            $table->timestamp('conflict_detected_at')->nullable();

            // Retry mechanism
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->unsignedTinyInteger('max_retries')->default(3);

            // Priority system
            $table->unsignedTinyInteger('priority')->default(5)
                ->comment('1=highest, 10=lowest');

            // Change detection
            $table->string('checksum', 64)->nullable()
                ->comment('MD5 hash dla change detection');

            $table->timestamps();

            // Indexes
            $table->unique(['product_id', 'shop_id'], 'unique_product_shop');
            $table->index('sync_status');
            $table->index('prestashop_product_id');
            $table->index(['retry_count', 'max_retries'], 'idx_retry_status');
            $table->index(['priority', 'sync_status'], 'idx_priority_status');

            // Foreign key constraints
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('shop_id')
                ->references('id')
                ->on('prestashop_shops')
                ->onDelete('cascade');
        });

        Log::warning('Table product_sync_status recreated (EMPTY - data not restored)', [
            'migration_rollback' => '2025_10_13_000002',
            'note' => 'Run consolidation migration rollback to restore data',
        ]);
    }
};
