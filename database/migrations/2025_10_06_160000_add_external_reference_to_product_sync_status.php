<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_07 REFACTOR: ProductShopData Architecture Fix
     *
     * Problem: ProductShopData była niepotrzebnie tworzona podczas importu z PrestaShop,
     * duplikując wszystkie dane z `products` table.
     *
     * Rozwiązanie: Dodajemy kolumnę `external_reference` (link_rewrite) do
     * `product_sync_status` aby móc generować PrestaShop URLs bez ProductShopData.
     *
     * Workflow po refactorze:
     * - products = "Domyślne dane" (wspólne dla wszystkich sklepów)
     * - product_sync_status = Relacja + external_id + external_reference (link_rewrite)
     * - product_shop_data = TYLKO override'y (różne dane per sklep)
     *
     * Migration adds:
     * - external_reference VARCHAR(255) - PrestaShop link_rewrite (product slug)
     *
     * Used for URL generation:
     * - {shop_url}/{prestashop_product_id}-{external_reference}.html
     * - Example: https://shop.com/123-product-name-slug.html
     */
    public function up(): void
    {
        Schema::table('product_sync_status', function (Blueprint $table) {
            $table->string('external_reference', 255)
                ->nullable()
                ->after('prestashop_product_id')
                ->comment('PrestaShop link_rewrite (product slug) for URL generation');

            // Index for URL generation queries
            $table->index(['shop_id', 'external_reference'], 'idx_shop_external_ref');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_sync_status', function (Blueprint $table) {
            $table->dropIndex('idx_shop_external_ref');
            $table->dropColumn('external_reference');
        });
    }
};
