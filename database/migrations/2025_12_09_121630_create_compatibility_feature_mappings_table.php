<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ETAP_05d FAZA 4.5.2 - Compatibility Feature Mappings
 *
 * Maps PPM compatibility attributes (original/replacement) to PrestaShop features.
 * Supports per-shop configuration (different feature IDs for different shops).
 *
 * Default mappings for B2B Test DEV:
 * - original -> Feature 431 (Oryginal)
 * - replacement -> Feature 433 (Zamiennik)
 * - Model (432) is computed automatically
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
        Schema::create('compatibility_feature_mappings', function (Blueprint $table) {
            $table->id();

            // PPM compatibility attribute (original, replacement, etc.)
            $table->unsignedBigInteger('compatibility_attribute_id')
                ->comment('FK to compatibility_attributes');

            $table->foreign('compatibility_attribute_id', 'fk_cfm_compat_attr')
                ->references('id')
                ->on('compatibility_attributes')
                ->cascadeOnDelete();

            // PrestaShop feature ID
            $table->integer('prestashop_feature_id')
                ->comment('PrestaShop ps_feature.id (e.g., 431, 432, 433)');

            // Shop context
            $table->unsignedBigInteger('shop_id')
                ->comment('FK to prestashop_shops');

            $table->foreign('shop_id', 'fk_cfm_shop')
                ->references('id')
                ->on('prestashop_shops')
                ->cascadeOnDelete();

            // Sync settings
            $table->boolean('is_active')->default(true)
                ->comment('Whether this mapping is active');

            $table->string('sync_direction', 20)->default('both')
                ->comment('ppm_to_ps, ps_to_ppm, both');

            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_sync_error')->nullable();

            $table->timestamps();

            // Unique: one mapping per attribute+shop
            $table->unique(
                ['compatibility_attribute_id', 'shop_id'],
                'uniq_compat_attr_shop'
            );

            // Index for shop queries
            $table->index('shop_id', 'idx_cfm_shop');
        });

        // Seed default mappings for existing shops
        $this->seedDefaultMappings();
    }

    /**
     * Seed default compatibility -> feature mappings
     */
    protected function seedDefaultMappings(): void
    {
        // Get all shops
        $shops = DB::table('prestashop_shops')->get();

        if ($shops->isEmpty()) {
            return;
        }

        // Get compatibility attributes
        $originalAttr = DB::table('compatibility_attributes')
            ->where('code', 'original')
            ->first();

        $replacementAttr = DB::table('compatibility_attributes')
            ->where('code', 'replacement')
            ->first();

        if (!$originalAttr || !$replacementAttr) {
            return;
        }

        $now = now();

        foreach ($shops as $shop) {
            // Mapping: original -> Feature 431 (Oryginal)
            DB::table('compatibility_feature_mappings')->insert([
                'compatibility_attribute_id' => $originalAttr->id,
                'prestashop_feature_id' => 431,
                'shop_id' => $shop->id,
                'is_active' => true,
                'sync_direction' => 'both',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Mapping: replacement -> Feature 433 (Zamiennik)
            DB::table('compatibility_feature_mappings')->insert([
                'compatibility_attribute_id' => $replacementAttr->id,
                'prestashop_feature_id' => 433,
                'shop_id' => $shop->id,
                'is_active' => true,
                'sync_direction' => 'both',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compatibility_feature_mappings');
    }
};
