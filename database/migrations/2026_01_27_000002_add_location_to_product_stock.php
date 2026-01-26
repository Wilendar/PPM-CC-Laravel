<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Location Field to Product Stock
 *
 * FAZA 2: Rozszerzona integracja Subiekt GT
 *
 * Dodaje pole location mapowane z Subiekt GT:
 * - location (tw_Pole2) - Lokalizacja produktu per magazyn
 *
 * UWAGA: To pole jest ODREBNE od istniejacych pol:
 * - warehouse_location (tekst z wieloma lokalizacjami rozdzielonymi ';')
 * - bin_location (primary bin/shelf location)
 *
 * Pole location to PROSTY string z Subiekt GT (tw_Pole2)
 * synchronizowany per rekord product_stock (produkt + magazyn).
 *
 * @package Database\Migrations
 * @version FAZA 2 - Subiekt GT Integration
 * @since 2026-01-27
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds location field to product_stock table
     */
    public function up(): void
    {
        Schema::table('product_stock', function (Blueprint $table) {
            $table->string('location', 50)
                ->nullable()
                ->after('minimum_stock')
                ->comment('tw_Pole2 - Lokalizacja produktu w magazynie (sync z Subiekt GT)');

            // Performance index for location queries
            $table->index(['warehouse_id', 'location'], 'idx_stock_warehouse_location');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Removes location field from product_stock table
     */
    public function down(): void
    {
        Schema::table('product_stock', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex('idx_stock_warehouse_location');

            // Drop column
            $table->dropColumn('location');
        });
    }
};
