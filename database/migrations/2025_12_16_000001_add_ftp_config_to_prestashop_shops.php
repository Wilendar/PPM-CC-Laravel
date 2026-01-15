<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add FTP/SFTP configuration to PrestaShop shops for CSS/JS sync
 *
 * ETAP_07f: Visual Description Editor - CSS Integration
 * Enables reading and writing custom.css/custom.js files on PrestaShop servers
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            // CSS/JS URL Sync (for read-only fetch)
            $table->string('custom_css_url', 500)->nullable()->after('sync_settings')
                ->comment('URL to custom.css on PrestaShop (e.g., https://shop.com/themes/theme/assets/css/custom.css)');
            $table->string('custom_js_url', 500)->nullable()->after('custom_css_url')
                ->comment('URL to custom.js on PrestaShop');
            $table->timestamp('css_last_fetched_at')->nullable()->after('custom_js_url')
                ->comment('Last time CSS was fetched from URL');
            $table->mediumText('cached_custom_css')->nullable()->after('css_last_fetched_at')
                ->comment('Cached content of custom.css');
            $table->mediumText('cached_custom_js')->nullable()->after('cached_custom_css')
                ->comment('Cached content of custom.js');

            // FTP/SFTP Configuration (for read/write access)
            $table->json('ftp_config')->nullable()->after('cached_custom_js')
                ->comment('FTP/SFTP configuration: {protocol, host, port, user, password, css_path, js_path, theme_path}');

            // CSS Sync Status
            $table->timestamp('css_last_deployed_at')->nullable()->after('ftp_config')
                ->comment('Last time CSS was deployed to PrestaShop');
            $table->string('css_deploy_status', 50)->nullable()->after('css_last_deployed_at')
                ->comment('Status of last CSS deployment: success, failed, pending');
            $table->text('css_deploy_message')->nullable()->after('css_deploy_status')
                ->comment('Message from last CSS deployment');
        });
    }

    public function down(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            $table->dropColumn([
                'custom_css_url',
                'custom_js_url',
                'css_last_fetched_at',
                'cached_custom_css',
                'cached_custom_js',
                'ftp_config',
                'css_last_deployed_at',
                'css_deploy_status',
                'css_deploy_message',
            ]);
        });
    }
};
