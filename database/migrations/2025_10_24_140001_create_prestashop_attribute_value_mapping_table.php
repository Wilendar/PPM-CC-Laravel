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
     * Migration 2/2: PrestaShop Attribute Value Mapping
     *
     * PURPOSE:
     * - Maps PPM AttributeValue → PrestaShop ps_attribute
     * - Tracks synchronization status per shop per value
     * - Enables multi-store support for attribute values
     * - Monitors color consistency (#ffffff format)
     *
     * MAPPING EXAMPLE:
     * - PPM AttributeValue "Czerwony" (id: 5, color_hex: #ff0000)
     *   → Shop A ps_attribute (id: 42, label: "Czerwony", color: #ff0000)
     *   → Shop B ps_attribute (id: 18, label: "Red", color: #ff0000)
     *
     * COLOR HANDLING:
     * - prestashop_color stores PrestaShop color value
     * - Used for verification against PPM color_hex
     * - Format: #ffffff (7 characters)
     * - NULL for non-color attribute types
     *
     * SYNC STATUSES:
     * - synced: Attribute value exists and is synchronized
     * - conflict: Mismatch (label or color differs)
     * - missing: Attribute value doesn't exist in PrestaShop
     * - pending: Waiting for sync verification (default)
     *
     * RELATIONSHIPS:
     * - belongs to AttributeValue (cascade delete)
     * - belongs to PrestaShopShop (cascade delete)
     *
     * INDEXES:
     * - sync_status (for bulk sync queries - ARCHITECT RECOMMENDATION)
     * - last_synced_at (for filtering by sync time - ARCHITECT RECOMMENDATION)
     * - prestashop_attribute_id (for reverse lookups - ARCHITECT RECOMMENDATION)
     *
     * @version 1.0
     * @since 2025-10-24
     */
    public function up(): void
    {
        Schema::create('prestashop_attribute_value_mapping', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('attribute_value_id')
                  ->constrained('attribute_values')
                  ->onDelete('cascade')
                  ->comment('PPM AttributeValue ID');

            $table->foreignId('prestashop_shop_id')
                  ->constrained('prestashop_shops')
                  ->onDelete('cascade')
                  ->comment('PrestaShop Shop ID');

            // PrestaShop mapping data
            $table->unsignedInteger('prestashop_attribute_id')
                  ->nullable()
                  ->comment('PrestaShop ps_attribute.id_attribute');

            $table->string('prestashop_label', 255)
                  ->nullable()
                  ->comment('Label from PrestaShop (name)');

            $table->string('prestashop_color', 7)
                  ->nullable()
                  ->comment('Color from PrestaShop (#ffffff format, NULL for non-color types)');

            // Synchronization tracking
            $table->boolean('is_synced')
                  ->default(false)
                  ->comment('Whether attribute value is synchronized');

            $table->timestamp('last_synced_at')
                  ->nullable()
                  ->comment('Last successful synchronization timestamp');

            $table->enum('sync_status', ['synced', 'conflict', 'missing', 'pending'])
                  ->default('pending')
                  ->comment('Current synchronization status');

            $table->text('sync_notes')
                  ->nullable()
                  ->comment('Error messages, warnings, color mismatches, sync details');

            $table->timestamps();

            // UNIQUE constraint (one mapping per attribute value per shop)
            $table->unique(['attribute_value_id', 'prestashop_shop_id'], 'unique_value_shop');

            // INDEXES for performance (architect recommendations)
            $table->index('sync_status', 'idx_value_sync_status');
            $table->index('last_synced_at', 'idx_value_last_synced');
            $table->index('prestashop_attribute_id', 'idx_value_ps_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestashop_attribute_value_mapping');
    }
};
