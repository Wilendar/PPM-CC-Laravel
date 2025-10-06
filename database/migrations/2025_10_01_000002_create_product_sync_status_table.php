<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_07 FAZA 1: PrestaShop API Integration - Product Sync Status Table
     *
     * Tabela product_sync_status przechowuje stan synchronizacji każdego produktu
     * z każdym sklepem PrestaShop. Jeden produkt może mieć różne stany sync dla różnych sklepów.
     *
     * Lifecycle synchronizacji:
     * 1. 'pending' - Produkt czeka na synchronizację
     * 2. 'syncing' - Synchronizacja w trakcie (job processing)
     * 3. 'synced' - Synchronizacja zakończona sukcesem
     * 4. 'error' - Błąd synchronizacji (sprawdź error_message)
     * 5. 'conflict' - Konflikt danych PPM vs PrestaShop (wymaga manual resolution)
     * 6. 'disabled' - Synchronizacja wyłączona dla tego produktu
     *
     * Retry mechanism:
     * - retry_count - liczba prób synchronizacji
     * - max_retries - maksymalna liczba prób (domyślnie 3)
     * - Po przekroczeniu max_retries status → 'error' i wymaga interwencji
     *
     * Change detection:
     * - checksum - MD5 hash danych produktu (name, description, prices, stock)
     * - Jeśli checksum się nie zmienił, synchronizacja jest pomijana (performance)
     *
     * Priority system:
     * - 1 = highest priority (featured products)
     * - 5 = normal priority (default)
     * - 10 = lowest priority (old products)
     */
    public function up(): void
    {
        Schema::create('product_sync_status', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign keys
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade')
                ->comment('ID produktu w PPM');

            $table->foreignId('shop_id')
                ->constrained('prestashop_shops')
                ->onDelete('cascade')
                ->comment('ID sklepu PrestaShop');

            // PrestaShop product ID (nullable - produkt może nie być jeszcze w PS)
            $table->unsignedBigInteger('prestashop_product_id')
                ->nullable()
                ->comment('ID produktu w PrestaShop');

            // Status synchronizacji
            $table->enum('sync_status', [
                'pending',
                'syncing',
                'synced',
                'error',
                'conflict',
                'disabled'
            ])->default('pending')->comment('Status synchronizacji');

            // Timestamps synchronizacji
            $table->timestamp('last_sync_at')->nullable()->comment('Ostatnia próba synchronizacji');
            $table->timestamp('last_success_sync_at')->nullable()->comment('Ostatnia udana synchronizacja');

            // Kierunek synchronizacji (FAZA 1: tylko ppm_to_ps)
            $table->enum('sync_direction', [
                'ppm_to_ps',        // PPM → PrestaShop (FAZA 1)
                'ps_to_ppm',        // PrestaShop → PPM (FAZA 2)
                'bidirectional'     // Dwukierunkowa (FAZA 2)
            ])->default('ppm_to_ps')->comment('Kierunek synchronizacji');

            // Error handling
            $table->text('error_message')->nullable()->comment('Komunikat błędu');
            $table->json('conflict_data')->nullable()->comment('Dane konfliktów do resolucji');

            // Retry mechanism
            $table->unsignedTinyInteger('retry_count')->default(0)->comment('Liczba prób synchronizacji');
            $table->unsignedTinyInteger('max_retries')->default(3)->comment('Maksymalna liczba prób');

            // Priority system (1=highest, 10=lowest)
            $table->unsignedTinyInteger('priority')->default(5)->comment('Priorytet synchronizacji (1-10)');

            // Change detection checksum
            $table->string('checksum', 64)->nullable()->comment('MD5 hash danych produktu');

            // Timestamps
            $table->timestamps();

            // UNIQUE constraint - jeden status per (product_id, shop_id)
            $table->unique(['product_id', 'shop_id'], 'unique_product_shop_sync');

            // Indexes dla performance
            $table->index(['sync_status'], 'idx_sync_status');
            $table->index(['shop_id', 'sync_status'], 'idx_shop_sync_status');
            $table->index(['priority', 'sync_status'], 'idx_priority_status');
            $table->index(['retry_count', 'max_retries'], 'idx_retry_status');
            $table->index(['last_sync_at'], 'idx_last_sync_at');
            $table->index(['prestashop_product_id'], 'idx_ps_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_sync_status');
    }
};
