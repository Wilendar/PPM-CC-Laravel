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
     * ADD UNIQUE CONSTRAINT: (shop_id, prestashop_product_id)
     *
     * PROBLEM (2025-10-13): Brak unique constraint na (shop_id, prestashop_product_id)
     * pozwala na sytuację gdzie różne produkty PPM mogą mieć ten sam prestashop_product_id
     * w tym samym sklepie - to jest błąd logiczny!
     *
     * ROZWIĄZANIE: Dodanie unique constraint zapewnia że:
     * - Jeden produkt PrestaShop (prestashop_product_id) w danym sklepie (shop_id)
     *   może być przypisany tylko do jednego produktu PPM (product_id)
     * - Różne sklepy mogą mieć produkty z tym samym prestashop_product_id (OK)
     *
     * BEFORE:
     * - unique_product_per_shop (product_id, shop_id) ← zapobiega duplikatom per produkt PPM
     * - NO constraint na (shop_id, prestashop_product_id) ← problem!
     *
     * AFTER:
     * - unique_product_per_shop (product_id, shop_id) ← pozostaje
     * - unique_prestashop_product_per_shop (shop_id, prestashop_product_id) ← NOWY!
     *
     * DATA INTEGRITY CHECK:
     * - Before adding constraint, check for duplicates
     * - If duplicates found, keep first occurrence, log others
     *
     * @package App\Database\Migrations
     * @version 1.0
     * @since 2025-10-13 - Unique Constraint Fix
     */
    public function up(): void
    {
        // STEP 1: Check for existing duplicates
        $duplicates = DB::select("
            SELECT shop_id, prestashop_product_id, COUNT(*) as count
            FROM product_shop_data
            WHERE prestashop_product_id IS NOT NULL
            GROUP BY shop_id, prestashop_product_id
            HAVING count > 1
        ");

        if (!empty($duplicates)) {
            Log::warning('Found duplicate (shop_id, prestashop_product_id) pairs before adding constraint', [
                'duplicates_count' => count($duplicates),
                'duplicates' => $duplicates,
            ]);

            // STEP 2: For each duplicate, keep first occurrence, nullify others
            foreach ($duplicates as $duplicate) {
                $records = DB::table('product_shop_data')
                    ->where('shop_id', $duplicate->shop_id)
                    ->where('prestashop_product_id', $duplicate->prestashop_product_id)
                    ->orderBy('id', 'asc')
                    ->get();

                // Keep first, nullify rest
                $keep = $records->first();
                $remove = $records->slice(1);

                foreach ($remove as $record) {
                    DB::table('product_shop_data')
                        ->where('id', $record->id)
                        ->update(['prestashop_product_id' => null]);

                    Log::info('Nullified duplicate prestashop_product_id', [
                        'id' => $record->id,
                        'product_id' => $record->product_id,
                        'shop_id' => $record->shop_id,
                        'prestashop_product_id' => $duplicate->prestashop_product_id,
                        'kept_record_id' => $keep->id,
                    ]);
                }
            }
        }

        // STEP 3: Add unique constraint
        // Note: Constraint allows NULL values (multiple NULLs are OK in unique constraint)
        Schema::table('product_shop_data', function (Blueprint $table) {
            $table->unique(['shop_id', 'prestashop_product_id'], 'unique_prestashop_product_per_shop');
        });

        Log::info('Unique constraint added: (shop_id, prestashop_product_id)', [
            'constraint_name' => 'unique_prestashop_product_per_shop',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            $table->dropUnique('unique_prestashop_product_per_shop');
        });

        Log::info('Unique constraint dropped: (shop_id, prestashop_product_id)');
    }
};
