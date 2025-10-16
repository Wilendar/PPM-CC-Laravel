<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ADD PER-SHOP CATEGORIES SUPPORT
     *
     * PROBLEM (2025-10-13): Product categories są globalne - brak możliwości różnych
     * kategorii per-shop. Re-import produktu z innego sklepu nadpisuje kategorie
     * domyślne i kategorię z pierwszego sklepu.
     *
     * ROZWIĄZANIE: Dodanie shop_id do product_categories pivot table:
     * - shop_id = NULL → "dane domyślne" (z pierwszego importu)
     * - shop_id = 1,2,3... → override per-shop (różne kategorie na różnych sklepach)
     *
     * ARCHITECTURE:
     * - First import → tworzy kategorie z shop_id=NULL (default)
     * - Re-import z innego sklepu → tworzy kategorie z shop_id=X (per-shop override)
     * - Product->categories() → zwraca tylko default (shop_id=NULL)
     * - Product->categoriesForShop($shopId) → zwraca per-shop lub fallback to default
     *
     * BREAKING CHANGE: Yes - wymaga update wszystkich queries używających categories()
     * MIGRATION SAFETY: Istniejące dane zachowane (shop_id=NULL dla wszystkich)
     *
     * @package App\Database\Migrations
     * @version 1.0
     * @since 2025-10-13 - Per-Shop Categories Support
     */
    public function up(): void
    {
        Log::info('Migration START: Adding shop_id to product_categories');

        // STEP 1: Add shop_id column (NULLABLE - NULL = default categories)
        Schema::table('product_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('shop_id')
                  ->nullable()
                  ->after('category_id')
                  ->comment('NULL = dane domyślne, NOT NULL = per-shop override');

            // Add foreign key constraint
            $table->foreign('shop_id', 'fk_product_categories_shop')
                  ->references('id')
                  ->on('prestashop_shops')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            // Add index for performance
            $table->index('shop_id', 'idx_product_categories_shop_id');
        });

        Log::info('shop_id column added to product_categories');

        // STEP 2: Drop old unique constraint (product_id, category_id)
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropUnique('unique_product_category');
        });

        Log::info('Old unique constraint dropped');

        // STEP 3: Add new unique constraint (product_id, category_id, shop_id)
        // MySQL treats NULL values as distinct in unique constraints, which is perfect:
        // - (product_id=1, category_id=5, shop_id=NULL) - allowed once (default)
        // - (product_id=1, category_id=5, shop_id=1) - allowed once (shop 1)
        // - (product_id=1, category_id=5, shop_id=2) - allowed once (shop 2)
        Schema::table('product_categories', function (Blueprint $table) {
            $table->unique(
                ['product_id', 'category_id', 'shop_id'],
                'unique_product_category_per_shop'
            );
        });

        Log::info('New unique constraint added: (product_id, category_id, shop_id)');

        // STEP 4: Drop old triggers (is_primary was global, now needs to be per-shop)
        try {
            DB::statement('DROP TRIGGER IF EXISTS tr_product_categories_primary_check');
            DB::statement('DROP TRIGGER IF EXISTS tr_product_categories_primary_update');

            Log::info('Old triggers dropped');
        } catch (\Exception $e) {
            Log::warning('Failed to drop old triggers (may not exist)', [
                'error' => $e->getMessage(),
            ]);
        }

        // STEP 5: Create new triggers for per-shop is_primary enforcement
        // Only one is_primary=true per (product_id, shop_id) combination

        // Trigger on INSERT
        DB::statement("
            CREATE TRIGGER tr_product_categories_primary_per_shop_insert
            BEFORE INSERT ON product_categories
            FOR EACH ROW
            BEGIN
                IF NEW.is_primary = 1 THEN
                    -- Reset other categories for same product + shop combination
                    UPDATE product_categories
                    SET is_primary = 0
                    WHERE product_id = NEW.product_id
                      AND (
                          (shop_id IS NULL AND NEW.shop_id IS NULL)
                          OR (shop_id = NEW.shop_id)
                      )
                      AND is_primary = 1;
                END IF;
            END
        ");

        // Trigger on UPDATE
        DB::statement("
            CREATE TRIGGER tr_product_categories_primary_per_shop_update
            BEFORE UPDATE ON product_categories
            FOR EACH ROW
            BEGIN
                IF NEW.is_primary = 1 AND OLD.is_primary = 0 THEN
                    -- Reset other categories for same product + shop combination
                    UPDATE product_categories
                    SET is_primary = 0
                    WHERE product_id = NEW.product_id
                      AND (
                          (shop_id IS NULL AND NEW.shop_id IS NULL)
                          OR (shop_id = NEW.shop_id)
                      )
                      AND is_primary = 1
                      AND id != NEW.id;
                END IF;
            END
        ");

        Log::info('New per-shop is_primary triggers created');

        // STEP 6: Update existing records to shop_id=NULL (preserve as default)
        // All existing categories become "dane domyślne"
        $updated = DB::table('product_categories')
            ->whereNull('shop_id')
            ->update(['shop_id' => null]); // Explicitly set NULL (already NULL, but for clarity)

        Log::info('Migration COMPLETE: shop_id added to product_categories', [
            'existing_records_count' => $updated,
            'migration' => '2025_10_13_000004',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('Migration ROLLBACK START: Removing shop_id from product_categories');

        // STEP 1: Drop new triggers
        try {
            DB::statement('DROP TRIGGER IF EXISTS tr_product_categories_primary_per_shop_insert');
            DB::statement('DROP TRIGGER IF EXISTS tr_product_categories_primary_per_shop_update');

            Log::info('Per-shop triggers dropped');
        } catch (\Exception $e) {
            Log::warning('Failed to drop per-shop triggers', [
                'error' => $e->getMessage(),
            ]);
        }

        // STEP 2: Drop new unique constraint
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropUnique('unique_product_category_per_shop');
        });

        Log::info('Per-shop unique constraint dropped');

        // STEP 3: Restore old unique constraint
        Schema::table('product_categories', function (Blueprint $table) {
            $table->unique(['product_id', 'category_id'], 'unique_product_category');
        });

        Log::info('Old unique constraint restored');

        // STEP 4: Drop shop_id column and its constraints
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropForeign('fk_product_categories_shop');
            $table->dropIndex('idx_product_categories_shop_id');
            $table->dropColumn('shop_id');
        });

        Log::info('shop_id column and constraints dropped');

        // STEP 5: Restore old triggers (optional - may fail if they exist)
        try {
            DB::statement("
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
            ");

            DB::statement("
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
            ");

            Log::info('Old triggers restored');
        } catch (\Exception $e) {
            Log::warning('Failed to restore old triggers', [
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Migration ROLLBACK COMPLETE');
    }
};
