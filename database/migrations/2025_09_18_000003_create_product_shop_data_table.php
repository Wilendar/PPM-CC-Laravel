<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * FAZA 1.5: Multi-Store Synchronization System - ProductShopData Table
     *
     * Tabela product_shop_data przechowuje dane produktów specyficzne dla każdego sklepu PrestaShop:
     * - Każdy produkt może mieć różne dane per sklep (nazwa, opisy, kategorie, zdjęcia)
     * - Wspólne dane biznesowe (SKU, ceny, stany) pozostają w tabeli products
     * - System wykrywa konflikty między PPM a rzeczywistymi danymi w sklepach
     * - Umożliwia publikowanie produktów na wybranych sklepach
     *
     * Multi-Store Features:
     * - Per-shop product data customization
     * - Sync status tracking per shop
     * - Conflict detection and resolution
     * - Publishing control per shop
     * - Performance optimized dla 100K+ products x shops
     *
     * @package App\Database\Migrations
     * @version 1.0
     * @since FAZA 1.5 - Multi-Store System
     */
    public function up(): void
    {
        Schema::create('product_shop_data', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign keys - relationship identification
            $table->unsignedBigInteger('product_id')->comment('ID produktu w PPM');
            $table->unsignedBigInteger('shop_id')->comment('ID sklepu PrestaShop');

            // Per-shop product data (can override default product data)
            $table->string('name', 500)->nullable()->comment('Nazwa produktu specyficzna dla sklepu');
            $table->string('slug', 600)->nullable()->comment('Slug URL specyficzny dla sklepu');
            $table->text('short_description')->nullable()->comment('Krótki opis specyficzny dla sklepu (max 800 znaków)');
            $table->longText('long_description')->nullable()->comment('Długi opis specyficzny dla sklepu (max 21844 znaków)');

            // SEO fields per shop
            $table->string('meta_title', 255)->nullable()->comment('SEO tytuł specyficzny dla sklepu');
            $table->text('meta_description')->nullable()->comment('SEO opis specyficzny dla sklepu');

            // Shop-specific mappings (JSON fields)
            $table->json('category_mappings')->nullable()->comment('Mapowanie kategorii specyficzne dla sklepu');
            $table->json('attribute_mappings')->nullable()->comment('Mapowanie atrybutów/cech specyficzne dla sklepu');
            $table->json('image_settings')->nullable()->comment('Ustawienia zdjęć (kolejność, które wyświetlać)');

            // Synchronization status and control
            $table->enum('sync_status', [
                'pending',      // Oczekuje na synchronizację
                'synced',       // Zsynchronizowane pomyślnie
                'error',        // Błąd synchronizacji
                'conflict',     // Konflikt danych (wymaga interwencji)
                'disabled'      // Synchronizacja wyłączona dla tego sklepu
            ])->default('pending')->comment('Status synchronizacji z tym sklepem');

            // Synchronization metadata
            $table->timestamp('last_sync_at')->nullable()->comment('Ostatnia synchronizacja z tym sklepem');
            $table->string('last_sync_hash', 64)->nullable()->comment('Hash danych z ostatniej synchronizacji (do wykrywania zmian)');

            // Error handling and conflict resolution
            $table->json('sync_errors')->nullable()->comment('Szczegóły błędów synchronizacji (JSON)');
            $table->json('conflict_data')->nullable()->comment('Dane konfliktu do rozwiązania przez użytkownika');
            $table->timestamp('conflict_detected_at')->nullable()->comment('Kiedy wykryto konflikt');

            // Publishing control
            $table->boolean('is_published')->default(false)->comment('Czy produkt jest publikowany na tym sklepie');
            $table->timestamp('published_at')->nullable()->comment('Kiedy opublikowano na tym sklepie');
            $table->timestamp('unpublished_at')->nullable()->comment('Kiedy usunięto z tego sklepu');

            // External system reference (PrestaShop product ID)
            $table->string('external_id', 100)->nullable()->comment('ID produktu w systemie PrestaShop');
            $table->string('external_reference', 200)->nullable()->comment('Dodatkowa referencja (SKU w PrestaShop)');

            // Audit and timestamps
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('shop_id')->references('id')->on('prestashop_shops')->onDelete('cascade');

            // Unique constraint - jeden wpis per produkt per sklep
            $table->unique(['product_id', 'shop_id'], 'unique_product_per_shop');

            // Strategic indexes dla multi-store performance
            // 1. Primary lookup pattern - product + shop
            $table->index(['product_id', 'shop_id'], 'idx_product_shop_lookup');

            // 2. Shop-based queries (wszystkie produkty dla sklepu)
            $table->index(['shop_id'], 'idx_shop_products');

            // 3. Sync status monitoring
            $table->index(['sync_status'], 'idx_sync_status');

            // 4. Published products filtering
            $table->index(['is_published'], 'idx_published_products');

            // 5. Sync status + shop combination dla dashboard
            $table->index(['shop_id', 'sync_status'], 'idx_shop_sync_status');

            // 6. Last sync monitoring dla scheduled jobs
            $table->index(['last_sync_at'], 'idx_last_sync_monitoring');

            // 7. Conflict resolution workflows
            $table->index(['sync_status', 'conflict_detected_at'], 'idx_conflict_resolution');

            // 8. External ID lookups (reverse mapping from PrestaShop)
            $table->index(['shop_id', 'external_id'], 'idx_external_lookup');

            // 9. Publishing timeline queries
            $table->index(['published_at'], 'idx_publishing_timeline');

            // 10. Product sync status aggregation
            $table->index(['product_id', 'sync_status'], 'idx_product_sync_aggregation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_shop_data');
    }
};