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
     * USUWANIE PROBLEMATYCZNYCH TRIGGERÓW
     *
     * Triggery powodowały błąd SQL 1442:
     * "Can't update table 'product_shop_categories' in stored function/trigger
     * because it is already used by statement which invoked this stored function/trigger"
     *
     * Application logic w ProductShopCategory::setCategoriesForProductShop()
     * już zapewnia poprawność danych (tylko jeden is_primary=true per product+shop).
     */
    public function up(): void
    {
        try {
            // Usuń triggery które powodują konflikty
            DB::statement('DROP TRIGGER IF EXISTS tr_product_shop_categories_primary_check');
            DB::statement('DROP TRIGGER IF EXISTS tr_product_shop_categories_primary_update');

            echo "Triggery zostały usunięte - zapisywanie produktów powinno teraz działać\n";
        } catch (Exception $e) {
            // Ignoruj błędy jeśli triggery już nie istnieją
            echo "Info: Triggery już były usunięte lub nie istniały\n";
        }
    }

    /**
     * Reverse the migrations.
     *
     * W razie potrzeby przywrócenia triggerów (nie zalecane)
     */
    public function down(): void
    {
        // Przywróć triggery (kopiowane z oryginalnej migracji)
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
    }
};
