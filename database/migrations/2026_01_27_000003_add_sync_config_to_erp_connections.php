<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Sync Configuration to ERP Connections
 *
 * FAZA 2: Rozszerzona integracja Subiekt GT
 *
 * Dodaje pola konfiguracji synchronizacji:
 * - sync_frequency - Czestotliwosc automatycznej synchronizacji
 * - is_price_source - Czy ERP jest zrodlem prawdy dla cen
 * - is_stock_source - Czy ERP jest zrodlem prawdy dla stanow magazynowych
 *
 * Business Logic:
 * - Jesli is_price_source=true, ceny z tego ERP nadpisuja ceny w PPM
 * - Jesli is_stock_source=true, stany z tego ERP nadpisuja stany w PPM
 * - Tylko JEDNO polaczenie ERP moze byc zrodlem cen/stanow (constraint w aplikacji)
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
     * Adds sync configuration fields to erp_connections table
     */
    public function up(): void
    {
        Schema::table('erp_connections', function (Blueprint $table) {
            // === SYNC FREQUENCY CONFIGURATION ===
            $table->string('sync_frequency', 20)
                ->default('6_hours')
                ->after('sync_mode')
                ->comment('Czestotliwosc synchronizacji: every_15_min, every_30_min, hourly, 6_hours, daily');

            // === DATA SOURCE FLAGS ===
            $table->boolean('is_price_source')
                ->default(false)
                ->after('sync_frequency')
                ->comment('Czy ERP jest zrodlem prawdy dla cen (nadpisuje ceny w PPM)');

            $table->boolean('is_stock_source')
                ->default(false)
                ->after('is_price_source')
                ->comment('Czy ERP jest zrodlem prawdy dla stanow magazynowych (nadpisuje stany w PPM)');

            // === PERFORMANCE INDEXES ===
            $table->index(['is_price_source'], 'idx_erp_price_source');
            $table->index(['is_stock_source'], 'idx_erp_stock_source');
            $table->index(['sync_frequency', 'is_active'], 'idx_erp_sync_frequency_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Removes sync configuration fields from erp_connections table
     */
    public function down(): void
    {
        Schema::table('erp_connections', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_erp_price_source');
            $table->dropIndex('idx_erp_stock_source');
            $table->dropIndex('idx_erp_sync_frequency_active');

            // Drop columns
            $table->dropColumn([
                'sync_frequency',
                'is_price_source',
                'is_stock_source',
            ]);
        });
    }
};
