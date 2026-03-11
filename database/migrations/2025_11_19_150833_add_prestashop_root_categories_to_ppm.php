<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Add PrestaShop Root Categories to PPM
 *
 * BUG FIX 2025-11-19 (BUG #3):
 * PPM should have Baza (id=1) and Wszystko (id=2) in its categories table
 * for full PrestaShop compatibility.
 *
 * PrestaShop Root Categories (STANDARD in every installation):
 * - ID 1: "Baza" (Root, parent_id = 0)
 * - ID 2: "Wszystko" or "Home" (Home category, parent_id = 1)
 *
 * Business Logic:
 * - These categories are auto-injected during export (ProductTransformer)
 * - Having them in PPM database ensures consistency
 * - Allows proper mapping between PPM and PrestaShop
 * - Prevents foreign key constraint violations
 *
 * @package Database\Migrations
 * @version 1.0
 * @since 2025-11-19
 */
return new class extends Migration
{
    /**
     * Run the migrations
     */
    public function up(): void
    {
        Log::info('[MIGRATION] Adding PrestaShop root categories to PPM');

        try {
            // Check if categories already exist (idempotency)
            $bazaExists = DB::table('categories')->where('id', 1)->exists();
            $wszystkoExists = DB::table('categories')->where('id', 2)->exists();

            if ($bazaExists && $wszystkoExists) {
                Log::info('[MIGRATION] Root categories already exist, skipping');
                return;
            }

            // CRITICAL: Disable auto-increment temporarily to insert specific IDs
            DB::statement('SET SESSION sql_mode = "NO_AUTO_VALUE_ON_ZERO"');

            // Insert Baza (ID 1) if not exists
            if (!$bazaExists) {
                DB::table('categories')->insert([
                    'id' => 1,
                    'name' => 'Baza',
                    'slug' => 'baza',
                    'parent_id' => null,
                    'description' => 'PrestaShop Root Category (automatically added)',
                    'is_active' => true,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('[MIGRATION] Created category: Baza (ID 1)');
            }

            // Insert Wszystko (ID 2) if not exists
            if (!$wszystkoExists) {
                DB::table('categories')->insert([
                    'id' => 2,
                    'name' => 'Wszystko',
                    'slug' => 'wszystko',
                    'parent_id' => 1, // Parent is Baza
                    'description' => 'PrestaShop Home Category (automatically added)',
                    'is_active' => true,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('[MIGRATION] Created category: Wszystko (ID 2)');
            }

            // Re-enable normal auto-increment behavior
            DB::statement('SET SESSION sql_mode = ""');

            Log::info('[MIGRATION] Root categories added successfully', [
                'baza_created' => !$bazaExists,
                'wszystko_created' => !$wszystkoExists,
            ]);

        } catch (\Exception $e) {
            Log::error('[MIGRATION] Failed to add root categories', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-enable normal mode even if migration fails
            DB::statement('SET SESSION sql_mode = ""');

            throw $e;
        }
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Log::info('[MIGRATION ROLLBACK] Removing PrestaShop root categories from PPM');

        try {
            // CRITICAL: Do NOT delete if categories are in use by products
            $bazaInUse = DB::table('product_categories')
                ->where('category_id', 1)
                ->exists();

            $wszystkoInUse = DB::table('product_categories')
                ->where('category_id', 2)
                ->exists();

            if ($bazaInUse || $wszystkoInUse) {
                Log::warning('[MIGRATION ROLLBACK] Root categories are in use, skipping deletion', [
                    'baza_in_use' => $bazaInUse,
                    'wszystko_in_use' => $wszystkoInUse,
                ]);

                // Do not delete - data integrity
                return;
            }

            // Safe to delete
            DB::table('categories')->whereIn('id', [1, 2])->delete();

            Log::info('[MIGRATION ROLLBACK] Root categories removed');

        } catch (\Exception $e) {
            Log::error('[MIGRATION ROLLBACK] Failed to remove root categories', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
};
