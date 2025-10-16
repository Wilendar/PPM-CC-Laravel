<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * DROP TRIGGERS FOR is_primary ENFORCEMENT
     *
     * PROBLEM (2025-10-13): MySQL Error 1442 - "Can't update table 'product_categories'
     * in stored function/trigger because it is already used by statement which invoked
     * this stored function/trigger"
     *
     * ROOT CAUSE: Trigger wykonuje UPDATE na product_categories podczas INSERT/UPDATE
     * na tej samej tabeli, co jest zabronione przez MySQL 5.7+
     *
     * ROZWIĄZANIE: Usuń triggery, zarządzaj is_primary logic w application layer
     * (ProductCategoryManager) - Enterprise Best Practice
     *
     * ARCHITECTURE:
     * - Before: MySQL triggers resetowały is_primary automatycznie
     * - After: ProductCategoryManager resetuje is_primary przed sync
     * - Benefit: Więcej kontroli, lepsza debugowalność, zgodność z MySQL constraints
     *
     * @package App\Database\Migrations
     * @version 1.0
     * @since 2025-10-13 - Trigger Removal
     */
    public function up(): void
    {
        Log::info('Migration START: Dropping product_categories triggers');

        try {
            // Drop INSERT trigger
            DB::statement('DROP TRIGGER IF EXISTS tr_product_categories_primary_per_shop_insert');
            Log::info('Trigger dropped: tr_product_categories_primary_per_shop_insert');

            // Drop UPDATE trigger
            DB::statement('DROP TRIGGER IF EXISTS tr_product_categories_primary_per_shop_update');
            Log::info('Trigger dropped: tr_product_categories_primary_per_shop_update');

            Log::info('Migration COMPLETE: All triggers dropped successfully');
        } catch (\Exception $e) {
            Log::error('Migration FAILED: Error dropping triggers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('Migration ROLLBACK START: Restoring product_categories triggers');

        try {
            // Restore INSERT trigger
            DB::statement("
                CREATE TRIGGER tr_product_categories_primary_per_shop_insert
                BEFORE INSERT ON product_categories
                FOR EACH ROW
                BEGIN
                    IF NEW.is_primary = 1 THEN
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
            Log::info('Trigger restored: tr_product_categories_primary_per_shop_insert');

            // Restore UPDATE trigger
            DB::statement("
                CREATE TRIGGER tr_product_categories_primary_per_shop_update
                BEFORE UPDATE ON product_categories
                FOR EACH ROW
                BEGIN
                    IF NEW.is_primary = 1 AND OLD.is_primary = 0 THEN
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
            Log::info('Trigger restored: tr_product_categories_primary_per_shop_update');

            Log::info('Migration ROLLBACK COMPLETE');
        } catch (\Exception $e) {
            Log::error('Migration ROLLBACK FAILED: Error restoring triggers', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
};
