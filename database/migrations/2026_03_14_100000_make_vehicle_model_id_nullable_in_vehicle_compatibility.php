<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('vehicle_compatibility', function (Blueprint $table) {
            // Drop existing FK constraint
            $table->dropForeign('vc_vehicle_product_fk');

            // Make nullable to allow phantom records
            $table->unsignedBigInteger('vehicle_model_id')->nullable()->change();

            // Re-add FK with SET NULL on delete
            $table->foreign('vehicle_model_id', 'vc_vehicle_product_fk')
                  ->references('id')->on('products')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_compatibility', function (Blueprint $table) {
            $table->dropForeign('vc_vehicle_product_fk');

            // Delete phantom records before making NOT NULL
            \App\Models\VehicleCompatibility::whereNull('vehicle_model_id')->delete();

            $table->unsignedBigInteger('vehicle_model_id')->nullable(false)->change();

            $table->foreign('vehicle_model_id', 'vc_vehicle_product_fk')
                  ->references('id')->on('products')
                  ->cascadeOnDelete();
        });
    }
};
