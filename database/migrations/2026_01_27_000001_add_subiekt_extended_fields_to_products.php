<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Subiekt GT Extended Fields to Products
 *
 * FAZA 2: Rozszerzona integracja Subiekt GT
 *
 * Dodaje pola mapowane z Subiekt GT:
 * - shop_internet (tw_SklepInternet) - Widocznosc w sklepie internetowym
 * - split_payment (tw_MechanizmPodzielonejPlatnosci) - Mechanizm podzielonej platnosci
 * - cn_code (tw_Pole5) - Kod CN (Combined Nomenclature) dla celow celnych
 * - material (tw_Pole1) - Material produktu
 * - defect_symbol (tw_Pole3) - Symbol produktu z wada
 * - application (tw_Pole4) - Zastosowanie produktu
 *
 * @package Database\Migrations
 * @version FAZA 2 - Subiekt GT Integration
 * @since 2026-01-27
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Subiekt GT extended fields to products table
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // === SUBIEKT GT INTEGRATION FLAGS ===
            $table->boolean('shop_internet')
                ->default(false)
                ->after('is_active')
                ->comment('tw_SklepInternet - Widocznosc produktu w sklepie internetowym');

            $table->boolean('split_payment')
                ->default(false)
                ->after('shop_internet')
                ->comment('tw_MechanizmPodzielonejPlatnosci - Mechanizm podzielonej platnosci VAT');

            // === SUBIEKT GT CUSTOM FIELDS (tw_Pole1-5) ===
            $table->string('cn_code', 50)
                ->nullable()
                ->after('split_payment')
                ->comment('tw_Pole5 - Kod CN (Combined Nomenclature) dla celow celnych');

            $table->string('material', 50)
                ->nullable()
                ->after('cn_code')
                ->comment('tw_Pole1 - Material produktu');

            $table->string('defect_symbol', 50)
                ->nullable()
                ->after('material')
                ->comment('tw_Pole3 - Symbol produktu z wada');

            $table->string('application', 255)
                ->nullable()
                ->after('defect_symbol')
                ->comment('tw_Pole4 - Zastosowanie produktu');

            // === PERFORMANCE INDEXES ===
            $table->index(['shop_internet'], 'idx_products_shop_internet');
            $table->index(['cn_code'], 'idx_products_cn_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Removes Subiekt GT extended fields from products table
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_products_shop_internet');
            $table->dropIndex('idx_products_cn_code');

            // Drop columns
            $table->dropColumn([
                'shop_internet',
                'split_payment',
                'cn_code',
                'material',
                'defect_symbol',
                'application',
            ]);
        });
    }
};
