<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create product_erp_data table
 *
 * ETAP_08.3: ERP Tab in ProductForm (Shop-Tab Pattern)
 *
 * Tabela product_erp_data przechowuje dane produktow specyficzne dla kazdego systemu ERP:
 * - Kazdy produkt moze miec rozne dane per ERP connection (nazwa, opisy, mapowania)
 * - Analogiczna struktura do product_shop_data dla spojnosci UX
 * - System wykrywa konflikty miedzy PPM a danymi w ERP
 * - Umozliwia publikowanie produktow do wybranych systemow ERP
 *
 * Multi-ERP Features:
 * - Per-ERP product data customization
 * - Sync status tracking per ERP connection
 * - Conflict detection and resolution
 * - Bidirectional sync support
 * - Performance optimized dla 100K+ products x ERP connections
 *
 * @package Database\Migrations
 * @version 1.0
 * @since ETAP_08.3 - ERP Tab Implementation (Shop-Tab Pattern)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_erp_data', function (Blueprint $table) {
            // Primary key
            $table->id();

            // ==========================================
            // FOREIGN KEYS - Relationship identification
            // ==========================================
            $table->unsignedBigInteger('product_id')->comment('ID produktu w PPM');
            $table->unsignedBigInteger('erp_connection_id')->comment('ID polaczenia ERP');

            // ==========================================
            // ALL PRODUCT FIELDS (can override defaults)
            // Mirrors product_shop_data structure
            // ==========================================
            $table->string('sku', 100)->nullable()->comment('SKU specyficzne dla ERP (override default)');
            $table->string('name', 500)->nullable()->comment('Nazwa produktu specyficzna dla ERP');
            $table->string('ean', 20)->nullable()->comment('EAN specyficzny dla ERP');
            $table->string('manufacturer', 200)->nullable()->comment('Producent specyficzny dla ERP');
            $table->string('supplier_code', 100)->nullable()->comment('Kod dostawcy specyficzny dla ERP');
            $table->text('short_description')->nullable()->comment('Krotki opis specyficzny dla ERP (max 800 znakow)');
            $table->longText('long_description')->nullable()->comment('Dlugi opis specyficzny dla ERP (max 21844 znakow)');

            // SEO fields per ERP
            $table->string('meta_title', 255)->nullable()->comment('SEO tytul specyficzny dla ERP');
            $table->text('meta_description')->nullable()->comment('SEO opis specyficzny dla ERP');

            // Physical properties
            $table->decimal('weight', 8, 3)->nullable()->comment('Waga specyficzna dla ERP (kg)');
            $table->decimal('height', 8, 2)->nullable()->comment('Wysokosc specyficzna dla ERP (cm)');
            $table->decimal('width', 8, 2)->nullable()->comment('Szerokosc specyficzna dla ERP (cm)');
            $table->decimal('length', 8, 2)->nullable()->comment('Dlugosc specyficzna dla ERP (cm)');
            $table->decimal('tax_rate', 5, 2)->nullable()->comment('Stawka VAT specyficzna dla ERP (%)');

            // Product status
            $table->boolean('is_active')->nullable()->comment('Status aktywnosci specyficzny dla ERP');

            // ==========================================
            // ERP-SPECIFIC MAPPINGS (JSON fields)
            // ==========================================
            $table->json('category_mappings')->nullable()->comment('Mapowanie kategorii specyficzne dla ERP');
            $table->json('attribute_mappings')->nullable()->comment('Mapowanie atrybutow/cech specyficzne dla ERP');
            $table->json('price_mappings')->nullable()->comment('Mapowanie cen/grup cenowych');
            $table->json('warehouse_mappings')->nullable()->comment('Mapowanie magazynow');
            $table->json('variant_mappings')->nullable()->comment('Mapowanie wariantow');
            $table->json('image_mappings')->nullable()->comment('Mapowanie zdjec');

            // ==========================================
            // SYNCHRONIZATION STATUS AND CONTROL
            // ==========================================
            $table->string('external_id', 200)->nullable()->comment('ID produktu w systemie ERP (np. Baselinker Product ID)');

            $table->enum('sync_status', [
                'pending',      // Oczekuje na synchronizacje
                'syncing',      // W trakcie synchronizacji
                'synced',       // Zsynchronizowane pomyslnie
                'error',        // Blad synchronizacji
                'conflict',     // Konflikt danych (wymaga interwencji)
                'disabled'      // Synchronizacja wylaczona dla tego ERP
            ])->default('pending')->comment('Status synchronizacji z tym ERP');

            $table->json('pending_fields')->nullable()->comment('Pola oczekujace na synchronizacje (JSON array)');

            $table->enum('sync_direction', [
                'ppm_to_erp',       // PPM -> ERP
                'erp_to_ppm',       // ERP -> PPM
                'bidirectional'     // Dwukierunkowa
            ])->default('bidirectional')->comment('Kierunek synchronizacji');

            // ==========================================
            // CONFLICT TRACKING
            // ==========================================
            $table->json('conflict_data')->nullable()->comment('Dane konfliktu do rozwiazania przez uzytkownika');
            $table->boolean('has_conflicts')->default(false)->comment('Flaga konfliktow');
            $table->timestamp('conflicts_detected_at')->nullable()->comment('Kiedy wykryto konflikty');

            // ==========================================
            // SYNCHRONIZATION TIMESTAMPS
            // ==========================================
            $table->timestamp('last_sync_at')->nullable()->comment('Ostatnia synchronizacja (dowolny kierunek)');
            $table->timestamp('last_push_at')->nullable()->comment('Ostatni push PPM -> ERP');
            $table->timestamp('last_pull_at')->nullable()->comment('Ostatni pull ERP -> PPM');
            $table->string('last_sync_hash', 64)->nullable()->comment('Hash danych z ostatniej synchronizacji');

            // ==========================================
            // ERROR HANDLING
            // ==========================================
            $table->text('error_message')->nullable()->comment('Ostatni komunikat bledu');
            $table->integer('retry_count')->default(0)->comment('Liczba prob synchronizacji');
            $table->integer('max_retries')->default(3)->comment('Maksymalna liczba prob');

            // ==========================================
            // EXTERNAL DATA CACHE
            // Przechowuje dane pobrane z ERP dla porownania
            // ==========================================
            $table->json('external_data')->nullable()->comment('Cache danych z ERP (dla porownania i UI)');

            // ==========================================
            // AUDIT TIMESTAMPS
            // ==========================================
            $table->timestamps();

            // ==========================================
            // FOREIGN KEY CONSTRAINTS
            // ==========================================
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');

            $table->foreign('erp_connection_id')
                  ->references('id')
                  ->on('erp_connections')
                  ->onDelete('cascade');

            // ==========================================
            // UNIQUE CONSTRAINT
            // Jeden wpis per produkt per ERP connection
            // ==========================================
            $table->unique(['product_id', 'erp_connection_id'], 'unique_product_per_erp');

            // ==========================================
            // STRATEGIC INDEXES dla multi-ERP performance
            // ==========================================
            // 1. Primary lookup pattern - product + ERP
            $table->index(['product_id', 'erp_connection_id'], 'idx_product_erp_lookup');

            // 2. ERP-based queries (wszystkie produkty dla ERP)
            $table->index(['erp_connection_id'], 'idx_erp_products');

            // 3. Sync status monitoring
            $table->index(['sync_status'], 'idx_erp_sync_status');

            // 4. Sync status + ERP combination dla dashboard
            $table->index(['erp_connection_id', 'sync_status'], 'idx_erp_sync_status_combo');

            // 5. Last sync monitoring dla scheduled jobs
            $table->index(['last_sync_at'], 'idx_erp_last_sync');

            // 6. Conflict resolution workflows
            $table->index(['has_conflicts', 'conflicts_detected_at'], 'idx_erp_conflicts');

            // 7. External ID lookups (reverse mapping from ERP)
            $table->index(['erp_connection_id', 'external_id'], 'idx_erp_external_lookup');

            // 8. SKU lookups per ERP
            $table->index(['sku'], 'idx_erp_sku');

            // 9. Product sync status aggregation
            $table->index(['product_id', 'sync_status'], 'idx_product_erp_sync_aggregation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_erp_data');
    }
};
