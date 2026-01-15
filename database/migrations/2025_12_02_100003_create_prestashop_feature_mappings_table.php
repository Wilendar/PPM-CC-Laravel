<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_07e FAZA 1.3.1 - Create PrestaShop feature mappings table
     *
     * PURPOSE:
     * - Map PPM FeatureTypes to PrestaShop features (per shop)
     * - Store PrestaShop feature IDs for sync
     * - Track sync status and direction
     *
     * PRESTASHOP TABLES (reference):
     * - ps_feature: id_feature, position
     * - ps_feature_lang: id_feature, id_lang, name
     * - ps_feature_value: id_feature_value, id_feature, custom
     * - ps_feature_value_lang: id_feature_value, id_lang, value
     * - ps_feature_product: id_feature, id_product, id_feature_value
     *
     * SYNC DIRECTIONS:
     * - both: Full bidirectional sync
     * - ppm_to_ps: Only push from PPM to PrestaShop
     * - ps_to_ppm: Only pull from PrestaShop to PPM
     */
    public function up(): void
    {
        Schema::create('prestashop_feature_mappings', function (Blueprint $table) {
            $table->id();

            // PPM side
            $table->foreignId('feature_type_id')
                  ->constrained('feature_types')
                  ->cascadeOnDelete();

            // Shop context (different PS features per shop possible)
            $table->foreignId('shop_id')
                  ->constrained('prestashop_shops')
                  ->cascadeOnDelete();

            // PrestaShop side
            $table->unsignedBigInteger('prestashop_feature_id')
                  ->comment('id_feature from ps_feature table');

            $table->string('prestashop_feature_name', 128)
                  ->nullable()
                  ->comment('Feature name in PS (for reference)');

            // Sync configuration
            $table->enum('sync_direction', ['both', 'ppm_to_ps', 'ps_to_ppm'])
                  ->default('both');

            $table->boolean('auto_create_values')
                  ->default(true)
                  ->comment('Auto-create PS feature values if not exists');

            $table->boolean('is_active')
                  ->default(true);

            // Sync tracking
            $table->timestamp('last_synced_at')->nullable();
            $table->integer('sync_count')->default(0);
            $table->text('last_sync_error')->nullable();

            $table->timestamps();

            // Unique constraint: one mapping per feature_type + shop
            $table->unique(
                ['feature_type_id', 'shop_id'],
                'uk_psfm_feature_shop'
            );

            // Indexes
            $table->index('prestashop_feature_id', 'idx_psfm_ps_feature');
            $table->index('shop_id', 'idx_psfm_shop');
            $table->index('is_active', 'idx_psfm_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestashop_feature_mappings');
    }
};
