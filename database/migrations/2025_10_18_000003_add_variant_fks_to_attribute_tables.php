<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Product Variant Foreign Keys to Attribute Tables
 *
 * This migration adds foreign key constraints from attribute-related tables to product_variants,
 * which were removed from original migrations to fix dependency ordering issues.
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
     * Adds foreign key constraints to product_variants from attribute tables
     */
    public function up(): void
    {
        // Add FK to product_attribute_values table
        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->foreign('product_variant_id')
                  ->references('id')
                  ->on('product_variants')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Removes foreign key constraints
     */
    public function down(): void
    {
        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
        });
    }
};
