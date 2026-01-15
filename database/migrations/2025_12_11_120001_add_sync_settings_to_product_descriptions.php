<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_07f Faza 8.2: PrestaShop Description Sync
     *
     * Adds sync settings for Visual Description Editor integration
     * with PrestaShop product sync.
     */
    public function up(): void
    {
        Schema::table('product_descriptions', function (Blueprint $table) {
            // Sync settings
            $table->boolean('sync_to_prestashop')
                ->default(true)
                ->after('template_id')
                ->comment('Auto-sync to PrestaShop on product sync');

            $table->enum('target_field', ['description', 'description_short', 'both'])
                ->default('description')
                ->after('sync_to_prestashop')
                ->comment('PrestaShop target field for visual description');

            $table->boolean('include_inline_css')
                ->default(true)
                ->after('target_field')
                ->comment('Include inline styles in exported HTML');

            // Sync status tracking
            $table->timestamp('last_synced_at')
                ->nullable()
                ->after('include_inline_css')
                ->comment('Last successful sync to PrestaShop');

            $table->string('sync_checksum', 64)
                ->nullable()
                ->after('last_synced_at')
                ->comment('MD5 hash of synced content for change detection');
        });

        // Add index for sync queries
        Schema::table('product_descriptions', function (Blueprint $table) {
            $table->index('sync_to_prestashop', 'idx_descriptions_sync_enabled');
            $table->index('last_synced_at', 'idx_descriptions_last_synced');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_descriptions', function (Blueprint $table) {
            $table->dropIndex('idx_descriptions_sync_enabled');
            $table->dropIndex('idx_descriptions_last_synced');

            $table->dropColumn([
                'sync_to_prestashop',
                'target_field',
                'include_inline_css',
                'last_synced_at',
                'sync_checksum',
            ]);
        });
    }
};
