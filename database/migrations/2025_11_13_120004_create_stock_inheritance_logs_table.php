<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Stock Inheritance Logs Table
 *
 * Audit Trail for Stock Inheritance/Sync Operations (Strategy B)
 *
 * Purpose:
 * - Track all stock inheritance operations (warehouse → shop)
 * - Debug inheritance issues
 * - Audit compliance
 * - Performance analytics
 *
 * Logged Operations:
 * - inherit: Shop inherited stock from warehouse
 * - pull: Shop pulled stock from PrestaShop API
 * - override: Shop manually overridden stock
 * - sync: Stock synchronized between systems
 *
 * @package Database\Migrations
 * @version Strategy B - Complex Warehouse Redesign
 * @since 2025-11-13
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_inheritance_logs', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade')
                  ->comment('Product that stock changed for');

            $table->foreignId('shop_id')
                  ->constrained('prestashop_shops')
                  ->onDelete('cascade')
                  ->comment('Shop that received/modified stock');

            $table->foreignId('warehouse_id')
                  ->nullable()
                  ->constrained('warehouses')
                  ->onDelete('set null')
                  ->comment('Source warehouse (if applicable)');

            // Operation Details
            $table->enum('action', ['inherit', 'pull', 'override', 'sync'])
                  ->comment('Type of stock operation');

            $table->string('source')
                  ->comment('Source of operation: warehouse, shop, manual, api');

            // Stock Changes
            $table->integer('quantity_before')
                  ->nullable()
                  ->comment('Stock quantity before operation');

            $table->integer('quantity_after')
                  ->comment('Stock quantity after operation');

            // Additional Context
            $table->json('metadata')
                  ->nullable()
                  ->comment('Additional operation context (user_id, api_call, etc.)');

            // Timestamps
            $table->timestamps();

            // Performance Indexes
            $table->index(['product_id', 'shop_id'], 'idx_product_shop_logs');
            $table->index('created_at', 'idx_created_at');
            $table->index(['action', 'created_at'], 'idx_action_date');
            $table->index('warehouse_id', 'idx_warehouse_logs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_inheritance_logs');
    }
};
