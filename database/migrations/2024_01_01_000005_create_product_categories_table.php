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
     * Obsługuje: Przypisanie produktu do wielu kategorii, kategoria domyślna,
     * sortowanie w kategoriach, audit trail dla zmian kategorii
     * 
     * Business Logic:
     * - is_primary=true -> kategoria domyślna dla PrestaShop export
     * - Jeden produkt może być w max 10 kategoriach (business rule)
     * - sort_order -> kolejność w obrębie kategorii
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
            $table->boolean('is_primary')->default(false); // Kategoria domyślna (jedna na produkt)
            $table->integer('sort_order')->default(0); // Kolejność w kategorii
            
            // === AUDIT TRAIL ===
            $table->timestamps(); // created_at, updated_at dla śledzenia zmian kategorii
            
            // === FOREIGN KEY CONSTRAINTS Z PROPER CASCADE ===
            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade') // Usuwa przypisania gdy produkt usunięty
                  ->onUpdate('cascade');
            
            $table->foreign('category_id')
                  ->references('id')->on('categories')
                  ->onDelete('cascade') // Usuwa przypisania gdy kategoria usunięta
                  ->onUpdate('cascade');
            
            // === UNIQUE CONSTRAINTS ===
            $table->unique(['product_id', 'category_id'], 'unique_product_category');
            
            // === PERFORMANCE INDEXES ===
            $table->index(['product_id']); // Szybkie lookup kategorii dla produktu
            $table->index(['category_id']); // Szybkie lookup produktów w kategorii
            $table->index(['category_id', 'sort_order']); // Sortowanie produktów w kategorii
            $table->index(['is_primary']); // Lookup kategorii domyślnych
            $table->index(['product_id', 'is_primary']); // Kategoria domyślna produktu
            $table->index(['created_at']); // Chronological changes tracking
        });

        // === BUSINESS LOGIC CONSTRAINTS ===
        // Tylko jedna kategoria domyślna na produkt
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

        // Constraint na maksymalną ilość kategorii na produkt (business rule)
        // To będzie sprawdzane w aplikacji, ale dodajemy backup constraint
        try {
            DB::statement('
                CREATE FUNCTION fn_check_max_categories_per_product(p_product_id BIGINT UNSIGNED) 
                RETURNS BOOLEAN 
                READS SQL DATA 
                DETERMINISTIC 
                BEGIN 
                    DECLARE category_count INT;
                    SELECT COUNT(*) INTO category_count 
                    FROM product_categories 
                    WHERE product_id = p_product_id;
                    RETURN category_count <= 10;
                END
            ');
            
            DB::statement('
                ALTER TABLE product_categories 
                ADD CONSTRAINT chk_max_categories 
                CHECK (fn_check_max_categories_per_product(product_id))
            ');
        } catch (Exception $e) {
            // Fallback - ten constraint może nie działać na wszystkich wersjach MySQL
            // Będzie sprawdzany w aplikacji Laravel
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Rollback support - usuwa tabelę, triggery i funkcje
     */
    public function down(): void
    {
        // Drop triggers first
        try {
            DB::statement('DROP TRIGGER IF EXISTS tr_product_categories_primary_check');
            DB::statement('DROP TRIGGER IF EXISTS tr_product_categories_primary_update');
            DB::statement('DROP FUNCTION IF EXISTS fn_check_max_categories_per_product');
        } catch (Exception $e) {
            // Ignore errors during rollback
        }
        
        Schema::dropIfExists('product_categories');
    }
};