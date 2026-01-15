<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add CSS cache columns for multi-file support
 *
 * ETAP_07f_P3: Visual Description Editor - CSS Integration
 *
 * Adds columns for caching theme.css, modules CSS, and asset manifest.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            // Theme CSS cache (main theme stylesheet)
            $table->longText('cached_theme_css')->nullable()->after('cached_custom_css');
            $table->timestamp('theme_css_fetched_at')->nullable()->after('cached_theme_css');

            // Asset manifest (list of all CSS/JS files discovered)
            $table->json('css_asset_manifest')->nullable()->after('theme_css_fetched_at');
            $table->timestamp('css_manifest_fetched_at')->nullable()->after('css_asset_manifest');

            // Selected modules for preview (URLs)
            $table->json('selected_css_modules')->nullable()->after('css_manifest_fetched_at');
        });
    }

    public function down(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            $table->dropColumn([
                'cached_theme_css',
                'theme_css_fetched_at',
                'css_asset_manifest',
                'css_manifest_fetched_at',
                'selected_css_modules',
            ]);
        });
    }
};
