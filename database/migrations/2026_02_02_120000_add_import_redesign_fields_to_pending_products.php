<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FAZA 9.1.1: Import Panel Redesign - New fields on pending_products
 *
 * Adds product fields (matching Product model), publication system,
 * scheduled publishing, and price data support.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_products', function (Blueprint $table) {
            // === PRODUCT FIELDS (matching Product model, missing from PendingProduct) ===
            $table->string('cn_code', 50)->nullable()->after('ean')
                ->comment('Kod CN (Combined Nomenclature)');
            $table->string('material', 50)->nullable()->after('cn_code')
                ->comment('Material produktu');
            $table->string('defect_symbol', 128)->nullable()->after('material')
                ->comment('Symbol produktu z wada');
            $table->string('application', 255)->nullable()->after('defect_symbol')
                ->comment('Zastosowanie produktu');

            // === SWITCHES ===
            $table->boolean('split_payment')->default(false)->after('application')
                ->comment('Mechanizm podzielonej platnosci');
            $table->boolean('shop_internet')->default(false)->after('split_payment')
                ->comment('Sklep Internetowy toggle');
            $table->boolean('is_variant_master')->default(false)->after('shop_internet')
                ->comment('Czy produkt wariantowy (master)');

            // === EXTENDED PRICES ===
            $table->json('price_data')->nullable()->after('purchase_price')
                ->comment('Ceny per grupa cenowa: {"groups":{"1":{"net":X,"gross":Y}}}');

            // === PUBLICATION SYSTEM ===
            $table->timestamp('scheduled_publish_at')->nullable()->after('published_at')
                ->comment('Zaplanowana data publikacji (null = natychmiast)');
            $table->json('publication_targets')->nullable()->after('scheduled_publish_at')
                ->comment('Targety: {"erp_primary":true,"prestashop_shops":[1,3]}');
            $table->string('publish_status', 32)->default('draft')->after('publication_targets')
                ->comment('draft|scheduled|publishing|published|failed');

            // === INDEXES ===
            $table->index('scheduled_publish_at', 'idx_pp_scheduled_publish');
            $table->index('publish_status', 'idx_pp_publish_status');
            $table->index('is_variant_master', 'idx_pp_variant_master');
        });
    }

    public function down(): void
    {
        Schema::table('pending_products', function (Blueprint $table) {
            $table->dropIndex('idx_pp_scheduled_publish');
            $table->dropIndex('idx_pp_publish_status');
            $table->dropIndex('idx_pp_variant_master');

            $table->dropColumn([
                'cn_code',
                'material',
                'defect_symbol',
                'application',
                'split_payment',
                'shop_internet',
                'is_variant_master',
                'price_data',
                'scheduled_publish_at',
                'publication_targets',
                'publish_status',
            ]);
        });
    }
};
