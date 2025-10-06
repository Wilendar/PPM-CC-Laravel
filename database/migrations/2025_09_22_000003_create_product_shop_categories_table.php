<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Product Shop Categories Table - Multi-Store Category Management
     *
     * Obsługuje: Kategorie per sklep PrestaShop, dziedziczenie z domyślnych,
     * synchronizacja z ps_category_product per sklep, color coding
     *
     * Business Logic:
     * - shop_id = NULL -> dziedziczy z product_categories (domyślne)
     * - shop_id != NULL -> własne kategorie dla sklepu
     * - is_primary=true -> kategoria główna dla PrestaShop export per sklep
     * - sort_order -> kolejność w obrębie kategorii per sklep
     *
     * PrestaShop Compatibility:
     * - Mapuje do ps_category_product per sklep
     * - Obsługuje różne kategorie per sklep (jak ps_category_shop)
     * - Zachowuje is_primary per sklep dla export logic
     */
    public function up(): void
    {
        Schema::create('product_shop_categories', function (Blueprint $table) {
            // === PRIMARY IDENTITY ===
            $table->id(); // SERIAL PRIMARY KEY dla audit trail

            // === FOREIGN KEYS ===
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('shop_id'); // Referuje do prestashop_shops
            $table->unsignedBigInteger('category_id');

            // === RELATIONSHIP METADATA ===
            $table->boolean('is_primary')->default(false); // Kategoria główna per sklep
            $table->integer('sort_order')->default(0); // Kolejność w kategorii per sklep

            // === AUDIT TRAIL ===
            $table->timestamps(); // created_at, updated_at dla śledzenia zmian

            // === FOREIGN KEY CONSTRAINTS Z PROPER CASCADE ===
            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade') // Usuwa przypisania gdy produkt usunięty
                  ->onUpdate('cascade');

            $table->foreign('shop_id')
                  ->references('id')->on('prestashop_shops')
                  ->onDelete('cascade') // Usuwa przypisania gdy sklep usunięty
                  ->onUpdate('cascade');

            $table->foreign('category_id')
                  ->references('id')->on('categories')
                  ->onDelete('cascade') // Usuwa przypisania gdy kategoria usunięta
                  ->onUpdate('cascade');

            // === UNIQUE CONSTRAINTS ===
            $table->unique(['product_id', 'shop_id', 'category_id'], 'unique_product_shop_category');

            // === PERFORMANCE INDEXES ===
            $table->index(['product_id']); // Szybkie lookup kategorii dla produktu
            $table->index(['shop_id']); // Szybkie lookup produktów per sklep
            $table->index(['category_id']); // Szybkie lookup produktów w kategorii
            $table->index(['product_id', 'shop_id']); // Kategorie produktu per sklep
            $table->index(['shop_id', 'category_id', 'sort_order']); // Sortowanie w kategorii per sklep
            $table->index(['is_primary']); // Lookup kategorii głównych
            $table->index(['product_id', 'shop_id', 'is_primary']); // Kategoria główna produktu per sklep
            $table->index(['created_at']); // Chronological changes tracking
        });

        // === BUSINESS LOGIC CONSTRAINTS ===
        // Tylko jedna kategoria główna na produkt per sklep
        DB::statement('
            CREATE TRIGGER tr_product_shop_categories_primary_check
            BEFORE INSERT ON product_shop_categories
            FOR EACH ROW
            BEGIN
                IF NEW.is_primary = 1 THEN
                    UPDATE product_shop_categories
                    SET is_primary = 0
                    WHERE product_id = NEW.product_id
                      AND shop_id = NEW.shop_id
                      AND is_primary = 1;
                END IF;
            END
        ');

        DB::statement('
            CREATE TRIGGER tr_product_shop_categories_primary_update
            BEFORE UPDATE ON product_shop_categories
            FOR EACH ROW
            BEGIN
                IF NEW.is_primary = 1 AND OLD.is_primary = 0 THEN
                    UPDATE product_shop_categories
                    SET is_primary = 0
                    WHERE product_id = NEW.product_id
                      AND shop_id = NEW.shop_id
                      AND is_primary = 1
                      AND id != NEW.id;
                END IF;
            END
        ');

        // === MIGRACJA DANYCH ===
        // Informacja: Istniejące dane z product_categories pozostają jako domyślne
        // Shop-specific dane będą tworzone gdy admin je edytuje per sklep

        DB::statement('
            INSERT INTO product_shop_categories (product_id, shop_id, category_id, is_primary, sort_order, created_at, updated_at)
            SELECT
                pc.product_id,
                ps.id as shop_id,
                pc.category_id,
                pc.is_primary,
                COALESCE(pc.sort_order, 0) as sort_order,
                NOW() as created_at,
                NOW() as updated_at
            FROM product_categories pc
            CROSS JOIN prestashop_shops ps
            WHERE ps.is_active = 1
              AND EXISTS (SELECT 1 FROM products p WHERE p.id = pc.product_id)
              AND EXISTS (SELECT 1 FROM categories c WHERE c.id = pc.category_id)
        ');
    }

    /**
     * Reverse the migrations.
     *
     * Rollback support - usuwa tabelę product_shop_categories z triggery
     */
    public function down(): void
    {
        // Drop triggers first
        try {
            DB::statement('DROP TRIGGER IF EXISTS tr_product_shop_categories_primary_check');
            DB::statement('DROP TRIGGER IF EXISTS tr_product_shop_categories_primary_update');
        } catch (Exception $e) {
            // Ignore errors during rollback
        }

        Schema::dropIfExists('product_shop_categories');
    }
};