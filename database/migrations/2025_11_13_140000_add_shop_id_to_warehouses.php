<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            // Add type column if doesn't exist
            if (!Schema::hasColumn('warehouses', 'type')) {
                $table->enum('type', ['master', 'shop_linked', 'custom'])
                      ->default('custom')
                      ->after('code');
            }

            // Add shop_id column if doesn't exist
            if (!Schema::hasColumn('warehouses', 'shop_id')) {
                $table->foreignId('shop_id')
                      ->nullable()
                      ->after('country')
                      ->constrained('prestashop_shops')
                      ->onDelete('cascade');
            }

            // Add inherit_from_shop column if doesn't exist
            if (!Schema::hasColumn('warehouses', 'inherit_from_shop')) {
                $table->boolean('inherit_from_shop')
                      ->default(false)
                      ->after('default_minimum_stock');
            }

            // Add deleted_at for SoftDeletes support
            if (!Schema::hasColumn('warehouses', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (Schema::hasColumn('warehouses', 'shop_id')) {
                $table->dropForeign(['shop_id']);
                $table->dropColumn('shop_id');
            }

            if (Schema::hasColumn('warehouses', 'type')) {
                $table->dropColumn('type');
            }

            if (Schema::hasColumn('warehouses', 'inherit_from_shop')) {
                $table->dropColumn('inherit_from_shop');
            }

            if (Schema::hasColumn('warehouses', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
