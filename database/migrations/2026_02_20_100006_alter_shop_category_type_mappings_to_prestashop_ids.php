<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Change category_id from FK(categories) to PrestaShop category ID
 *
 * Reason: User wants to map PrestaShop categories → ProductTypes,
 * not PPM categories. The tree picker shows PS categories from API.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_category_type_mappings', function (Blueprint $table) {
            // Drop FK constraint on category_id (was referencing PPM categories)
            $table->dropForeign(['category_id']);

            // Add category_name to cache PS category name for display
            $table->string('category_name', 255)->nullable()->after('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('shop_category_type_mappings', function (Blueprint $table) {
            $table->dropColumn('category_name');

            // Restore FK constraint
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->cascadeOnDelete();
        });
    }
};
