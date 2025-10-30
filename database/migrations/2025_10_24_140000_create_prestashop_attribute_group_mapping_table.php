<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05b FAZA 1 - Phase 1: Database Schema
     * Migration 1/2: PrestaShop Attribute Group Mapping
     *
     * PURPOSE:
     * - Maps PPM AttributeType → PrestaShop ps_attribute_group
     * - Tracks synchronization status per shop
     * - Enables multi-store support for attribute definitions
     * - Monitors sync health and conflicts
     *
     * MAPPING EXAMPLE:
     * - PPM AttributeType "Kolor" (id: 1)
     *   → Shop A ps_attribute_group (id: 25, label: "Kolor")
     *   → Shop B ps_attribute_group (id: 14, label: "Color")
     *
     * SYNC STATUSES:
     * - synced: Attribute group exists and is synchronized
     * - pending: Waiting for sync verification
     * - conflict: Mismatch between PPM and PrestaShop
     * - missing: Attribute group doesn't exist in PrestaShop
     *
     * RELATIONSHIPS:
     * - belongs to AttributeType (cascade delete)
     * - belongs to PrestaShopShop (cascade delete)
     *
     * INDEXES:
     * - sync_status (for bulk sync queries - ARCHITECT RECOMMENDATION)
     * - last_synced_at (for filtering by sync time - ARCHITECT RECOMMENDATION)
     *
     * @version 1.0
     * @since 2025-10-24
     */
    public function up(): void
    {
        Schema::create('prestashop_attribute_group_mapping', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('attribute_type_id')
                  ->constrained('attribute_types')
                  ->onDelete('cascade')
                  ->comment('PPM AttributeType ID');

            $table->foreignId('prestashop_shop_id')
                  ->constrained('prestashop_shops')
                  ->onDelete('cascade')
                  ->comment('PrestaShop Shop ID');

            // PrestaShop mapping data
            $table->unsignedInteger('prestashop_attribute_group_id')
                  ->nullable()
                  ->comment('PrestaShop ps_attribute_group.id_attribute_group');

            $table->string('prestashop_label', 255)
                  ->nullable()
                  ->comment('Label from PrestaShop (public_name)');

            // Synchronization tracking
            $table->boolean('is_synced')
                  ->default(false)
                  ->comment('Whether attribute group is synchronized');

            $table->timestamp('last_synced_at')
                  ->nullable()
                  ->comment('Last successful synchronization timestamp');

            $table->enum('sync_status', ['synced', 'pending', 'conflict', 'missing'])
                  ->default('pending')
                  ->comment('Current synchronization status');

            $table->text('sync_notes')
                  ->nullable()
                  ->comment('Error messages, warnings, sync details');

            $table->timestamps();

            // UNIQUE constraint (one mapping per attribute type per shop)
            $table->unique(['attribute_type_id', 'prestashop_shop_id'], 'unique_type_shop');

            // INDEXES for performance (architect recommendations)
            $table->index('sync_status', 'idx_group_sync_status');
            $table->index('last_synced_at', 'idx_group_last_synced');
            $table->index('prestashop_attribute_group_id', 'idx_group_ps_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestashop_attribute_group_mapping');
    }
};
