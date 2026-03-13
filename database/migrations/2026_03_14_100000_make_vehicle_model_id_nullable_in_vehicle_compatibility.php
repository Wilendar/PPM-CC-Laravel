<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make vehicle_model_id nullable in vehicle_compatibility table.
 *
 * Allows "phantom" records where the vehicle doesn't exist in PPM yet
 * but was imported from PrestaShop features. Vehicle data is stored in
 * the metadata JSON column.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Drop any existing FK on vehicle_model_id
        $fkName = $this->findForeignKeyName();
        if ($fkName) {
            Schema::table('vehicle_compatibility', function (Blueprint $table) use ($fkName) {
                $table->dropForeign($fkName);
            });
        }

        // Step 2: Drop UNIQUE constraint that includes vehicle_model_id (NULL not allowed in UNIQUE)
        $hasUnique = DB::select("
            SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'vehicle_compatibility'
            AND CONSTRAINT_NAME = 'uniq_compat_product_vehicle_shop_attr'
            AND CONSTRAINT_TYPE = 'UNIQUE'
        ");
        if (!empty($hasUnique)) {
            Schema::table('vehicle_compatibility', function (Blueprint $table) {
                $table->dropUnique('uniq_compat_product_vehicle_shop_attr');
            });
        }

        // Step 3: Make column nullable
        Schema::table('vehicle_compatibility', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_model_id')->nullable()->change();
        });

        // Step 4: Clean orphaned records (vehicle_model_id pointing to non-existent products)
        $orphanedCount = DB::update("
            UPDATE vehicle_compatibility
            SET vehicle_model_id = NULL
            WHERE vehicle_model_id IS NOT NULL
            AND vehicle_model_id NOT IN (SELECT id FROM products)
        ");

        if ($orphanedCount > 0) {
            \Illuminate\Support\Facades\Log::info("[MIGRATION] Nullified {$orphanedCount} orphaned vehicle_model_id records");
        }

        // Step 5: Add FK with SET NULL
        Schema::table('vehicle_compatibility', function (Blueprint $table) {
            $table->foreign('vehicle_model_id', 'vc_vehicle_product_fk')
                  ->references('id')->on('products')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_compatibility', function (Blueprint $table) {
            $table->dropForeign('vc_vehicle_product_fk');
        });

        // Delete phantom records before making NOT NULL
        DB::table('vehicle_compatibility')->whereNull('vehicle_model_id')->delete();

        Schema::table('vehicle_compatibility', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_model_id')->nullable(false)->change();

            $table->foreign('vehicle_model_id', 'vc_vehicle_product_fk')
                  ->references('id')->on('products')
                  ->cascadeOnDelete();

            $table->unique(
                ['product_id', 'vehicle_model_id', 'shop_id', 'compatibility_attribute_id'],
                'uniq_compat_product_vehicle_shop_attr'
            );
        });
    }

    private function findForeignKeyName(): ?string
    {
        $results = DB::select("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'vehicle_compatibility'
            AND COLUMN_NAME = 'vehicle_model_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        return !empty($results) ? $results[0]->CONSTRAINT_NAME : null;
    }
};
