<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Extend product_shop_data with all product fields
 *
 * Rozszerza tabelę product_shop_data o wszystkie kolumny z tabeli products
 * dla maksymalnej zgodności edycji między trybem domyślnym a sklepowym.
 *
 * Cel: Każdy sklep może mieć własne wartości dla WSZYSTKICH pól produktu,
 * a nie tylko dla nazwy i opisów.
 *
 * @package Database\Migrations
 * @version 1.0
 * @since 2025-09-19 - Product Shop Data Enhancement
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            // === MISSING BASIC FIELDS ===
            $table->string('sku', 100)->nullable()->after('slug')->comment('SKU specyficzne dla sklepu (override default)');

            // === PRODUCT CLASSIFICATION ===
            $table->unsignedBigInteger('product_type_id')->nullable()->after('meta_description')->comment('Typ produktu specyficzny dla sklepu');
            $table->string('manufacturer', 200)->nullable()->after('product_type_id')->comment('Producent specyficzny dla sklepu');
            $table->string('supplier_code', 100)->nullable()->after('manufacturer')->comment('Kod dostawcy specyficzny dla sklepu');

            // === PHYSICAL PROPERTIES ===
            $table->decimal('weight', 8, 3)->nullable()->after('supplier_code')->comment('Waga specyficzna dla sklepu (kg)');
            $table->decimal('height', 8, 2)->nullable()->after('weight')->comment('Wysokość specyficzna dla sklepu (cm)');
            $table->decimal('width', 8, 2)->nullable()->after('height')->comment('Szerokość specyficzna dla sklepu (cm)');
            $table->decimal('length', 8, 2)->nullable()->after('width')->comment('Długość specyficzna dla sklepu (cm)');
            $table->string('ean', 20)->nullable()->after('length')->comment('EAN specyficzny dla sklepu');
            $table->decimal('tax_rate', 5, 2)->nullable()->after('ean')->comment('Stawka VAT specyficzna dla sklepu (%)');

            // === PRODUCT STATUS & VARIANTS ===
            $table->boolean('is_active')->nullable()->after('tax_rate')->comment('Status aktywności specyficzny dla sklepu');
            $table->boolean('is_variant_master')->nullable()->after('is_active')->comment('Czy posiada warianty - specyficzne dla sklepu');
            $table->integer('sort_order')->nullable()->after('is_variant_master')->comment('Kolejność sortowania specyficzna dla sklepu');

            // === FOREIGN KEY FOR PRODUCT TYPE ===
            $table->foreign('product_type_id')->references('id')->on('product_types')->onDelete('set null');

            // === INDEXES FOR PERFORMANCE ===
            $table->index(['sku'], 'idx_shop_sku'); // Shop-specific SKU lookup
            $table->index(['product_type_id'], 'idx_shop_product_type'); // Product type filtering
            $table->index(['manufacturer'], 'idx_shop_manufacturer'); // Manufacturer filtering
            $table->index(['supplier_code'], 'idx_shop_supplier_code'); // Supplier code lookup
            $table->index(['is_active'], 'idx_shop_is_active'); // Active status filtering
            $table->index(['sort_order'], 'idx_shop_sort_order'); // Sorting optimization
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['product_type_id']);

            // Drop indexes
            $table->dropIndex('idx_shop_sku');
            $table->dropIndex('idx_shop_product_type');
            $table->dropIndex('idx_shop_manufacturer');
            $table->dropIndex('idx_shop_supplier_code');
            $table->dropIndex('idx_shop_is_active');
            $table->dropIndex('idx_shop_sort_order');

            // Drop columns
            $table->dropColumn([
                'sku',
                'product_type_id',
                'manufacturer',
                'supplier_code',
                'weight',
                'height',
                'width',
                'length',
                'ean',
                'tax_rate',
                'is_active',
                'is_variant_master',
                'sort_order',
            ]);
        });
    }
};