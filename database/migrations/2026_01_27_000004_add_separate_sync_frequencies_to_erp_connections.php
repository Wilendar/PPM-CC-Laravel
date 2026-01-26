<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Separate Sync Frequencies to ERP Connections
 *
 * Rozdzielenie pojedynczego sync_frequency na 3 niezalezne czestotliwosci:
 * - price_sync_frequency - Czestotliwosc synchronizacji cen
 * - stock_sync_frequency - Czestotliwosc synchronizacji stanow magazynowych
 * - basic_data_sync_frequency - Czestotliwosc synchronizacji danych podstawowych (nazwa, opis)
 *
 * Business Logic:
 * - Kazdy typ synchronizacji moze miec inna czestotliwosc
 * - Ceny i stany moga byc synchronizowane czesciej (np. co 15 min)
 * - Dane podstawowe rzadziej (np. raz dziennie)
 *
 * @package Database\Migrations
 * @version FAZA 2.1 - Subiekt GT Integration Extended
 * @since 2026-01-27
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('erp_connections', function (Blueprint $table) {
            // === SEPARATE SYNC FREQUENCIES ===
            // Zachowujemy sync_frequency jako fallback, dodajemy nowe pola

            $table->string('price_sync_frequency', 20)
                ->default('6_hours')
                ->after('sync_frequency')
                ->comment('Czestotliwosc synchronizacji cen: every_15_min, every_30_min, hourly, 6_hours, daily');

            $table->string('stock_sync_frequency', 20)
                ->default('6_hours')
                ->after('price_sync_frequency')
                ->comment('Czestotliwosc synchronizacji stanow magazynowych: every_15_min, every_30_min, hourly, 6_hours, daily');

            $table->string('basic_data_sync_frequency', 20)
                ->default('daily')
                ->after('stock_sync_frequency')
                ->comment('Czestotliwosc synchronizacji danych podstawowych (nazwa, opis): every_15_min, every_30_min, hourly, 6_hours, daily');

            // === PERFORMANCE INDEXES ===
            $table->index(['price_sync_frequency', 'is_active'], 'idx_erp_price_sync_freq');
            $table->index(['stock_sync_frequency', 'is_active'], 'idx_erp_stock_sync_freq');
            $table->index(['basic_data_sync_frequency', 'is_active'], 'idx_erp_basic_sync_freq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_connections', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_erp_price_sync_freq');
            $table->dropIndex('idx_erp_stock_sync_freq');
            $table->dropIndex('idx_erp_basic_sync_freq');

            // Drop columns
            $table->dropColumn([
                'price_sync_frequency',
                'stock_sync_frequency',
                'basic_data_sync_frequency',
            ]);
        });
    }
};
