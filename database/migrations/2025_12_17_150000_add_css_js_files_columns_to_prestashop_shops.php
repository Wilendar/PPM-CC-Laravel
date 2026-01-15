<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add css_files and js_files columns to prestashop_shops table.
 *
 * These columns store arrays of CSS/JS files to sync with PrestaShop.
 * Each file entry contains: url, name, type (theme/custom/module), enabled, cached_content
 *
 * Replaces single custom_css_url/custom_js_url with multi-file support.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            // Array of CSS files to sync
            // Structure: [{url, name, type, enabled, cached_content, last_fetched_at}]
            $table->json('css_files')->nullable()->after('css_asset_manifest');

            // Array of JS files to sync
            // Structure: [{url, name, type, enabled, cached_content, last_fetched_at}]
            $table->json('js_files')->nullable()->after('css_files');

            // Last time files were scanned from PrestaShop
            $table->timestamp('files_scanned_at')->nullable()->after('js_files');
        });
    }

    public function down(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            $table->dropColumn(['css_files', 'js_files', 'files_scanned_at']);
        });
    }
};
