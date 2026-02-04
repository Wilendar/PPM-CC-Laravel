<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add label customization columns to ERP connections and PrestaShop shops
 *
 * ETAP_10: Product Scan System - Customizable integration labels
 *
 * Allows users to set custom colors and icons for each integration,
 * which are displayed in the "Powiazania" column and other places.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add to erp_connections table
        Schema::table('erp_connections', function (Blueprint $table) {
            $table->string('label_color', 7)->nullable()->after('is_active')
                ->comment('Hex color for label badge (e.g., #f97316)');
            $table->string('label_icon', 50)->nullable()->after('label_color')
                ->comment('Icon name for label (e.g., database, cloud, server)');
        });

        // Add to prestashop_shops table
        Schema::table('prestashop_shops', function (Blueprint $table) {
            $table->string('label_color', 7)->nullable()->after('is_active')
                ->comment('Hex color for label badge (e.g., #06b6d4)');
            $table->string('label_icon', 50)->nullable()->after('label_color')
                ->comment('Icon name for label (e.g., shopping-cart, store)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_connections', function (Blueprint $table) {
            $table->dropColumn(['label_color', 'label_icon']);
        });

        Schema::table('prestashop_shops', function (Blueprint $table) {
            $table->dropColumn(['label_color', 'label_icon']);
        });
    }
};
