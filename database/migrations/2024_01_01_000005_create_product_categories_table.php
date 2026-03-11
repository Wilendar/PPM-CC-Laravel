<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Product Categories Pivot Table - Many-to-Many Relationship
     * Obs³uguje: Przypisanie produktu do wielu kategorii, kategoria domyœlna,
     * sortowanie w kategoriach, audit trail dla zmian kategorii
     * 
     * Business Logic:
     * - is_primary=true -> kategoria domyœlna dla PrestaShop export
     * - Jeden produkt mo¿e byæ w max 10 kategoriach (business rule)
     * - sort_order -> kolejnoœæ w obrêbie kategorii
     */
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            // === PRIMARY IDENTITY ===
            $table->id(); // SERIAL PRIMARY KEY dla audit trail
            
            // === FOREIGN KEYS ===
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('category_id');
            
            // === RELATIONSHIP METADATA ===
            $table->boolean('is_primary')->default(false); // Kategoria domyœlna (jedna na produkt)
            $table->integer('sort_order')->default(0); // Kolejnoœæ w kategorii
            
            // === AUDIT TRAIL ===
            $table->timestamps(); // created_at, updated_at dla œledzenia zmian kategorii
            
            // === FOREIGN KEY CONSTRAINTS Z PROPER CASCADE ===
            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade') // Usuwa przypisania gdy produkt usuniêty
                  ->onUpdate('cascade');
            
            $table->foreign('category_id')
                  ->references('id')->on('categories')
                  ->onDelete('cascade') // Usuwa przypisania gdy kategoria usuniêta
                  ->onUpdate('cascade');
            
            // === UNIQUE CONSTRAINTS ===
            $table->unique(['product_id', 'category_id'], 'unique_product_category');
            
            // === PERFORMANCE INDEXES ===
            $table->index(['product_id']); // Szybkie lookup kategorii dla produktu
            $table->index(['category_id']); // Szybkie lookup produktów w kategorii
            $table->index(['category_id', 'sort_order']); // Sortowanie produktów w kategorii
            $table->index(['is_primary']); // Lookup kategorii domyœlnych
            $table->index(['product_id', 'is_primary']); // Kategoria domyœlna produktu
            $table->index(['created_at']); // Chronological changes tracking
        });

        // === BUSINESS LOGIC CONSTRAINTS ===
        // Tylko jedna kategoria domyœlna na produkt
        DB::statement('
            CREATE TRIGGER tr_product_categories_primary_check 
            BEFORE INSERT ON product_categories
            FOR EACH ROW
            BEGIN
                IF NEW.is_primary = 1 THEN
                    UPDATE product_categories 
                    SET is_primary = 0 
                    WHERE product_id = NEW.product_id AND is_primary = 1;
                END IF;
            END
        ');

        DB::statement('
            CREATE TRIGGER tr_product_categories_primary_update 
            BEFORE UPDATE ON product_categories
            FOR EACH ROW
            BEGIN
                IF NEW.is_primary = 1 AND OLD.is_primary = 0 THEN
                    UPDATE product_categories 
                    SET is_primary = 0 
                    WHERE product_id = NEW.product_id AND is_primary = 1 AND id != NEW.id;
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     * 
     * Rollback support - usuwa tabelê, triggery i funkcje
     */
    public function down(): void
    {
        // Drop triggers first
        try {
            DB::statement('DROP TRIGGER IF EXISTS tr_product_categories_primary_check');
            DB::statement('DROP TRIGGER IF EXISTS tr_product_categories_primary_update');
        } catch (Exception $e) {
            // Ignore errors during rollback
        }
        
        Schema::dropIfExists('product_categories');
    }
};
