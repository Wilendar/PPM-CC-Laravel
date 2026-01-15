<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Change vehicle_model_id FK from vehicle_models to products.
     *
     * ETAP_05d - FK Architecture Change
     *
     * REASON:
     * - User wants to display products with type='pojazd' in compatibility panel
     * - vehicle_model_id should point to products (type='pojazd'), not vehicle_models catalog
     *
     * CHANGES:
     * 1. Truncate old data (tables are empty anyway after previous migration)
     * 2. Drop existing FK constraint on vehicle_model_id
     * 3. Change FK to point to products.id instead of vehicle_models.id
     * 4. Rebuild indexes/constraints
     */
    public function up(): void
    {
        // Step 1: Disable FK constraints and truncate
        Schema::disableForeignKeyConstraints();
        DB::table('vehicle_compatibility')->truncate();

        // Step 2: Safely drop FK (may already be dropped from previous attempt)
        try {
            Schema::table('vehicle_compatibility', function (Blueprint $table) {
                $table->dropForeign(['vehicle_model_id']);
            });
        } catch (\Exception $e) {
            // FK may not exist, continue
        }

        // Step 3: Safely drop indexes using raw SQL with IF EXISTS
        DB::statement('DROP INDEX IF EXISTS idx_compat_product_vehicle ON vehicle_compatibility');
        DB::statement('DROP INDEX IF EXISTS idx_compat_shop_vehicle ON vehicle_compatibility');
        DB::statement('DROP INDEX IF EXISTS uniq_compat_product_vehicle_shop ON vehicle_compatibility');

        // Step 4: Check if new FK already exists, if not create it
        $fkExists = DB::select("
            SELECT COUNT(*) as cnt FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = 'vehicle_compatibility'
            AND CONSTRAINT_NAME = 'vc_vehicle_product_fk'
        ");

        if (empty($fkExists) || $fkExists[0]->cnt == 0) {
            Schema::table('vehicle_compatibility', function (Blueprint $table) {
                $table->foreign('vehicle_model_id')
                      ->references('id')
                      ->on('products')
                      ->cascadeOnDelete()
                      ->name('vc_vehicle_product_fk');
            });
        }

        // Step 5: Check and rebuild indexes if they don't exist
        $this->createIndexIfNotExists('idx_compat_product_vehicle',
            'CREATE INDEX idx_compat_product_vehicle ON vehicle_compatibility (product_id, vehicle_model_id)');
        $this->createIndexIfNotExists('idx_compat_shop_vehicle',
            'CREATE INDEX idx_compat_shop_vehicle ON vehicle_compatibility (shop_id, vehicle_model_id)');
        $this->createIndexIfNotExists('uniq_compat_product_vehicle_shop',
            'CREATE UNIQUE INDEX uniq_compat_product_vehicle_shop ON vehicle_compatibility (product_id, vehicle_model_id, shop_id)');

        // Re-enable FK constraints
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Helper: Create index if it doesn't exist
     */
    private function createIndexIfNotExists(string $indexName, string $createSql): void
    {
        $exists = DB::select("
            SELECT COUNT(*) as cnt FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'vehicle_compatibility'
            AND INDEX_NAME = ?
        ", [$indexName]);

        if (empty($exists) || $exists[0]->cnt == 0) {
            DB::statement($createSql);
        }
    }

    /**
     * Reverse - restore FK to vehicle_models.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('vehicle_compatibility')->truncate();
        Schema::enableForeignKeyConstraints();

        Schema::table('vehicle_compatibility', function (Blueprint $table) {
            $table->dropIndex('idx_compat_product_vehicle');
            $table->dropIndex('idx_compat_shop_vehicle');
            $table->dropUnique('uniq_compat_product_vehicle_shop');
            $table->dropForeign('vc_vehicle_product_fk');
        });

        Schema::table('vehicle_compatibility', function (Blueprint $table) {
            $table->foreign('vehicle_model_id')
                  ->references('id')
                  ->on('vehicle_models')
                  ->cascadeOnDelete();

            $table->index(['product_id', 'vehicle_model_id'], 'idx_compat_product_vehicle');
            $table->index(['shop_id', 'vehicle_model_id'], 'idx_compat_shop_vehicle');
            $table->unique(
                ['product_id', 'vehicle_model_id', 'shop_id'],
                'uniq_compat_product_vehicle_shop'
            );
        });
    }
};
