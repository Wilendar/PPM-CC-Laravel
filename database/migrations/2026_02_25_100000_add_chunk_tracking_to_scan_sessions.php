<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add chunk tracking columns to product_scan_sessions
 *
 * Dodaje kolumny potrzebne do trybu chunked skanowania (AJAX).
 * Pozwala sledzic postep skanowania w chunkach po 500 produktow.
 *
 * Nowe kolumny:
 * - total_chunks: laczna liczba chunkow do przetworzenia
 * - processed_chunks: liczba juz przetworzonych chunkow
 * - chunk_size: rozmiar jednego chunka (domyslnie 500)
 * - source_cache_key: JSON z lista konfiguracji zrodel dla prefetchowania SKU
 * - scan_mode: tryb skanowania ('legacy' lub 'chunked')
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_scan_sessions', function (Blueprint $table) {
            $table->unsignedInteger('total_chunks')->nullable()
                ->after('result_summary')
                ->comment('Laczna liczba chunkow do przetworzenia (tryb chunked)');

            $table->unsignedInteger('processed_chunks')->default(0)
                ->after('total_chunks')
                ->comment('Liczba juz przetworzonych chunkow');

            $table->unsignedInteger('chunk_size')->default(500)
                ->after('processed_chunks')
                ->comment('Rozmiar jednego chunka (liczba produktow PPM)');

            $table->string('source_cache_key')->nullable()
                ->after('chunk_size')
                ->comment('JSON z lista konfiguracji zrodel do prefetchowania SKU do cache');

            $table->string('scan_mode')->default('legacy')
                ->after('source_cache_key')
                ->comment('Tryb skanowania: legacy=klasyczny, chunked=AJAX chunked z progress barem');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_scan_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'total_chunks',
                'processed_chunks',
                'chunk_size',
                'source_cache_key',
                'scan_mode',
            ]);
        });
    }
};
