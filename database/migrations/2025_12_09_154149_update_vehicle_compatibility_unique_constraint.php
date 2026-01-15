<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Update vehicle_compatibility unique constraint to include compatibility_attribute_id
 *
 * REASON: Same vehicle can be BOTH Original AND Zamiennik (replacement) for a product.
 * The old constraint (product_id, vehicle_model_id, shop_id) prevented this.
 * New constraint: (product_id, vehicle_model_id, shop_id, compatibility_attribute_id)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Get all indexes on the table
        $indexes = DB::select("SHOW INDEX FROM vehicle_compatibility WHERE Key_name LIKE 'uniq_compat%'");
        $indexNames = collect($indexes)->pluck('Key_name')->unique()->toArray();

        // Drop old unique constraints (try all possible names)
        $oldIndexes = [
            'uniq_compat_product_vehicle_shop',
            'uniq_compat_product_vehicle',
            'vehicle_compatibility_product_id_vehicle_model_id_shop_id_unique',
        ];

        foreach ($oldIndexes as $indexName) {
            if (in_array($indexName, $indexNames)) {
                try {
                    DB::statement("DROP INDEX {$indexName} ON vehicle_compatibility");
                } catch (\Exception $e) {
                    // Continue
                }
            }
        }

        // Create new unique constraint including compatibility_attribute_id
        // This allows: same product + vehicle + shop can have BOTH Original AND Zamiennik
        $newIndexExists = in_array('uniq_compat_product_vehicle_shop_attr', $indexNames);
        if (!$newIndexExists) {
            DB::statement('CREATE UNIQUE INDEX uniq_compat_product_vehicle_shop_attr
                ON vehicle_compatibility (product_id, vehicle_model_id, shop_id, compatibility_attribute_id)');
        }
    }

    public function down(): void
    {
        // Drop new constraint
        try {
            DB::statement('DROP INDEX IF EXISTS uniq_compat_product_vehicle_shop_attr ON vehicle_compatibility');
        } catch (\Exception $e) {
            // Continue
        }

        // Restore old constraint
        DB::statement('CREATE UNIQUE INDEX uniq_compat_product_vehicle_shop
            ON vehicle_compatibility (product_id, vehicle_model_id, shop_id)');
    }
};
