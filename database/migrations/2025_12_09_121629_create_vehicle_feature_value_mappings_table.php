<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ETAP_05d FAZA 4.5.2 - Vehicle Feature Value Mappings
 *
 * Maps PPM vehicle products to PrestaShop feature values for compatibility sync.
 * Enables bidirectional lookup:
 * - PPM vehicle_product_id -> PrestaShop feature_value_id (for export)
 * - PrestaShop feature_value_id -> PPM vehicle_product_id (for import)
 *
 * @since 2025-12-09
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicle_feature_value_mappings', function (Blueprint $table) {
            $table->id();

            // PPM vehicle product (type='pojazd')
            $table->foreignId('vehicle_product_id')
                ->comment('FK to products (pojazd)')
                ->constrained('products')
                ->cascadeOnDelete();

            // PrestaShop feature ID (431=Oryginal, 432=Model, 433=Zamiennik)
            $table->integer('prestashop_feature_id')
                ->comment('PrestaShop ps_feature.id (431/432/433)');

            // PrestaShop feature value ID
            $table->integer('prestashop_feature_value_id')
                ->comment('PrestaShop ps_feature_value.id');

            // Shop context (per-shop mappings)
            $table->foreignId('shop_id')
                ->comment('FK to prestashop_shops')
                ->constrained('prestashop_shops')
                ->cascadeOnDelete();

            $table->timestamp('created_at')->useCurrent();

            // Unique constraint: one mapping per vehicle+feature+shop
            $table->unique(
                ['vehicle_product_id', 'prestashop_feature_id', 'shop_id'],
                'uniq_vehicle_feature_shop'
            );

            // Index for reverse lookup (PS feature value -> PPM vehicle)
            $table->index('prestashop_feature_value_id', 'idx_ps_feature_value');

            // Index for shop queries
            $table->index(['shop_id', 'prestashop_feature_id'], 'idx_shop_feature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_feature_value_mappings');
    }
};
