<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_07 FAZA 1: PrestaShop API Integration - Shop Mappings Table
     *
     * Tabela shop_mappings przechowuje mapowania między encjami PPM a PrestaShop.
     *
     * Rodzaje mapowań (mapping_type):
     * - 'category' - Mapowanie kategorii PPM → PrestaShop category_id
     * - 'attribute' - Mapowanie atrybutów produktów
     * - 'feature' - Mapowanie cech produktów
     * - 'warehouse' - Mapowanie magazynów PPM → PrestaShop warehouse_id
     * - 'price_group' - Mapowanie grup cenowych PPM → PrestaShop customer_group
     * - 'tax_rule' - Mapowanie reguł podatkowych
     *
     * Przykład użycia:
     * Kategoria "Motocykle" (id=5 w PPM) → category_id=42 w PrestaShop Shop A
     * Grupa cenowa "Detaliczna" → customer_group_id=2 w PrestaShop Shop A
     *
     * UNIQUE constraint zapewnia że każde mapowanie PPM → PrestaShop jest unikalne per sklep.
     */
    public function up(): void
    {
        Schema::create('shop_mappings', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key do prestashop_shops (CASCADE on delete - usunięcie sklepu usuwa mapowania)
            $table->foreignId('shop_id')
                ->constrained('prestashop_shops')
                ->onDelete('cascade')
                ->comment('ID sklepu PrestaShop');

            // Typ mapowania
            $table->enum('mapping_type', [
                'category',
                'attribute',
                'feature',
                'warehouse',
                'price_group',
                'tax_rule'
            ])->comment('Typ mapowania');

            // PPM wartość (może być ID lub nazwa)
            $table->string('ppm_value', 255)->comment('Wartość w systemie PPM (ID lub nazwa)');

            // PrestaShop ID (zawsze integer ID w PS)
            $table->unsignedBigInteger('prestashop_id')->comment('ID encji w PrestaShop');

            // PrestaShop wartość (opcjonalnie nazwa/label dla referencji)
            $table->string('prestashop_value', 255)->nullable()->comment('Wartość w PrestaShop (opcjonalna nazwa)');

            // Status mapowania
            $table->boolean('is_active')->default(true)->comment('Czy mapowanie jest aktywne');

            // Timestamps
            $table->timestamps();

            // UNIQUE constraint - jedno mapowanie per (shop_id, mapping_type, ppm_value)
            $table->unique(['shop_id', 'mapping_type', 'ppm_value'], 'unique_shop_mapping');

            // Indexes dla performance
            $table->index(['shop_id', 'mapping_type'], 'idx_shop_type');
            $table->index(['mapping_type', 'ppm_value'], 'idx_type_ppm_value');
            $table->index(['is_active'], 'idx_mapping_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_mappings');
    }
};
