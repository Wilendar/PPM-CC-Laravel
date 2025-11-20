<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Product Variant Foreign Key to Product Prices
 *
 * This migration adds the foreign key constraint from product_prices.product_variant_id
 * to product_variants.id, which was removed from the original product_prices migration
 * to fix dependency ordering issues.
 *
 * EXECUTION ORDER: Must run AFTER 2025_10_17_100001_create_product_variants_table.php
 *
 * @package Database\Migrations
 * @version ETAP_05b
 * @since 2025-10-18
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds foreign key constraint from product_prices to product_variants
     */
    public function up(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            // Add foreign key constraint to product_variants table
            $table->foreign('product_variant_id')
                  ->references('id')
                  ->on('product_variants')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Removes foreign key constraint
     */
    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
        });
    }
};
