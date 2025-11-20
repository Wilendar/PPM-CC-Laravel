<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * CONSOLIDATION: product_shop_categories → product_categories
     *
     * PROBLEM: Dwie tabele pełniące tę samą funkcję (per-shop categories):
     *   - product_shop_categories (created 2025-09-22, ETAP_05) - OLD architecture
     *   - product_categories with shop_id column (added 2025-10-13) - NEW architecture
     *
     * SOLUTION: Migrate unique data from OLD → NEW, drop OLD table
     *
     * MIGRATION STRATEGY:
     *   1. Find records in product_shop_categories NOT in product_categories
     *   2. Insert them into product_categories (preserve is_primary, sort_order)
     *   3. Drop product_shop_categories table
     *   4. Remove ProductShopCategory.php model (manual code cleanup)
     *
     * ROLLBACK: Recreate product_shop_categories table (structure only)
     *
     * Date: 2025-11-19
     * ETAP: ETAP_05 Cleanup
     */
    public function up(): void
    {
        echo "=== CONSOLIDATING CATEGORY TABLES ===\n";

        // STEP 1: Find records in OLD table NOT in NEW table
        $uniqueInOld = DB::table('product_shop_categories as psc')
            ->leftJoin('product_categories as pc', function($join) {
                $join->on('psc.product_id', '=', 'pc.product_id')
                     ->on('psc.shop_id', '=', 'pc.shop_id')
                     ->on('psc.category_id', '=', 'pc.category_id');
            })
            ->whereNull('pc.id')
            ->select('psc.*')
            ->get();

        $uniqueCount = $uniqueInOld->count();
        echo "Found {$uniqueCount} unique records in product_shop_categories\n";

        // STEP 2: Migrate unique records to product_categories
        if ($uniqueCount > 0) {
            echo "Migrating {$uniqueCount} records...\n";

            foreach ($uniqueInOld as $record) {
                DB::table('product_categories')->insert([
                    'product_id' => $record->product_id,
                    'category_id' => $record->category_id,
                    'shop_id' => $record->shop_id,
                    'is_primary' => $record->is_primary,
                    'sort_order' => $record->sort_order,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ]);

                echo "  Migrated: Product {$record->product_id}, Shop {$record->shop_id}, Category {$record->category_id}\n";
            }
        }

        // STEP 3: Verify migration
        $countBefore = DB::table('product_shop_categories')->count();
        $countAfter = DB::table('product_categories')->whereNotNull('shop_id')->count();

        echo "\nVERIFICATION:\n";
        echo "  product_shop_categories (before drop): {$countBefore} records\n";
        echo "  product_categories (per-shop after migration): {$countAfter} records\n";

        // STEP 4: Drop product_shop_categories table
        echo "\nDropping product_shop_categories table...\n";
        Schema::dropIfExists('product_shop_categories');

        echo "✅ CONSOLIDATION COMPLETE\n";
        echo "\n⚠️ MANUAL CLEANUP REQUIRED:\n";
        echo "   - Remove app/Models/ProductShopCategory.php\n";
        echo "   - Update code using ProductShopCategory::setCategoriesForProductShop()\n";
        echo "   - Use product_categories pivot table with attach/detach instead\n";
    }

    /**
     * Reverse the migrations (recreate structure only, data lost).
     */
    public function down(): void
    {
        echo "=== ROLLING BACK: Recreating product_shop_categories ===\n";

        Schema::create('product_shop_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('shop_id')->constrained('prestashop_shops')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Unique constraint
            $table->unique(['product_id', 'shop_id', 'category_id'], 'unique_product_shop_category');

            // Indexes
            $table->index(['product_id', 'shop_id'], 'idx_product_shop');
            $table->index('category_id', 'idx_category');
        });

        echo "⚠️ WARNING: Table recreated but DATA NOT RESTORED (use backup if needed)\n";
    }
};
