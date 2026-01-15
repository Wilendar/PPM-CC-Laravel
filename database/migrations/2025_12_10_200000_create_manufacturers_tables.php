<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create manufacturers and manufacturer_shop pivot tables
 * ETAP: Panel Zarządzania Parametrami Produktu
 *
 * Manufacturer = Marka produktu z przypisaniem do sklepów PrestaShop
 */
return new class extends Migration
{
    public function up(): void
    {
        // Main manufacturers table
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->string('website', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('code');
            $table->index('sort_order');
        });

        // Pivot table: manufacturer <-> prestashop_shop
        Schema::create('manufacturer_shop', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturer_id')->constrained('manufacturers')->cascadeOnDelete();
            $table->foreignId('prestashop_shop_id')->constrained('prestashop_shops')->cascadeOnDelete();
            $table->unsignedInteger('ps_manufacturer_id')->nullable()->comment('PrestaShop manufacturer ID');
            $table->enum('sync_status', ['pending', 'synced', 'error'])->default('pending');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['manufacturer_id', 'prestashop_shop_id'], 'manufacturer_shop_unique');
        });

        // Add manufacturer_id to products table (nullable - backward compatible)
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('manufacturer_id')->nullable()->after('product_type_id')
                ->constrained('manufacturers')->nullOnDelete();
            $table->index('manufacturer_id');
        });

        // Add manufacturer_id to pending_products table
        Schema::table('pending_products', function (Blueprint $table) {
            $table->foreignId('manufacturer_id')->nullable()->after('product_type_id')
                ->constrained('manufacturers')->nullOnDelete();
            $table->index('manufacturer_id');
        });
    }

    public function down(): void
    {
        // Remove foreign keys and columns from products
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['manufacturer_id']);
            $table->dropColumn('manufacturer_id');
        });

        Schema::table('pending_products', function (Blueprint $table) {
            $table->dropForeign(['manufacturer_id']);
            $table->dropColumn('manufacturer_id');
        });

        Schema::dropIfExists('manufacturer_shop');
        Schema::dropIfExists('manufacturers');
    }
};
