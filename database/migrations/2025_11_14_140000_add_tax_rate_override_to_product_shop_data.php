<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_07 FAZA 5.3 - Tax Rules UI Enhancement - Per-Shop Tax Rate Override
     *
     * Problem: Products need per-shop tax rate override capability
     * - Default: Use products.tax_rate (global default)
     * - Override: Store in product_shop_data.tax_rate_override
     *
     * Examples:
     * - Product X: 23% VAT w Polsce (default), but 20% VAT w UK (override)
     * - Product Y: 8% VAT dla książek (default), but 5% VAT dla e-booków (override)
     * - Product Z: Różne stawki VAT dla różnych sklepów (B2B vs B2C)
     *
     * Use Cases:
     * 1. Cross-border sales - Różne stawki VAT per kraj
     * 2. B2B vs B2C shops - Różne traktowanie podatkowe
     * 3. Special product categories - Overrides dla specjalnych produktów
     * 4. Multi-country PrestaShop installations - Per-shop tax rules
     *
     * Integration:
     * - ProductForm UI: Dropdown per shop (NULL = use default)
     * - ProductTransformer: Effective tax rate = override ?? default
     * - Sync: Uses mapped tax_rules_group_id from PrestaShopShop
     *
     * @author laravel-expert
     * @date 2025-11-14
     * @version 1.0
     */
    public function up(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            // Per-shop tax rate override
            // NULL = use products.tax_rate (default behavior)
            // Non-NULL = per-shop override value
            $table->decimal('tax_rate_override', 5, 2)
                ->nullable()
                ->after('tax_rate')
                ->comment('Per-shop tax rate override (NULL = use products.tax_rate default)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            $table->dropColumn('tax_rate_override');
        });
    }
};
