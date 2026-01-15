<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05d FAZA 1 - Migration 2/4
     *
     * Adds brand restrictions and compatibility settings to prestashop_shops.
     *
     * PURPOSE:
     * - Allow per-shop vehicle brand filtering (e.g., YCF banned on Pitbike.pl)
     * - Store compatibility-related shop settings
     *
     * BUSINESS RULES:
     * - allowed_vehicle_brands:
     *   - NULL = all brands allowed (no restrictions)
     *   - [] (empty array) = NO brands allowed (disable compatibility)
     *   - ["YCF", "KAYO"] = only these brands visible
     *
     * - compatibility_settings JSON:
     *   {
     *     "enable_smart_suggestions": true,
     *     "auto_apply_suggestions": false,
     *     "min_confidence_score": 0.75,
     *     "show_model_badge": true
     *   }
     *
     * EXAMPLES:
     * - B2B Test DEV: allowed_vehicle_brands = NULL (all brands)
     * - Pitbike.pl: allowed_vehicle_brands = ["YCF", "KAYO", "MRF"] (no Honda)
     */
    public function up(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            // Brand restrictions (JSON array of allowed brands)
            $table->json('allowed_vehicle_brands')
                  ->nullable()
                  ->after('is_active')
                  ->comment('JSON array of allowed vehicle brands (null = all, [] = none)');

            // Compatibility settings (JSON object)
            $table->json('compatibility_settings')
                  ->nullable()
                  ->after('allowed_vehicle_brands')
                  ->comment('Smart suggestions and display settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            $table->dropColumn(['allowed_vehicle_brands', 'compatibility_settings']);
        });
    }
};
