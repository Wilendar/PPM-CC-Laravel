<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ETAP_07h v2.0: UVE CSS-First Architecture
 *
 * Adds columns for CSS class generation instead of inline styles:
 * - css_rules: JSON with CSS class definitions (per-style hash: .uve-s{hash})
 * - css_class_map: JSON mapping elementId -> className
 * - css_mode: Delivery mode (pending or external - inline_style_block ELIMINATED)
 * - css_migrated_at: When inline styles were migrated
 * - css_synced_at: When CSS was synced to PrestaShop
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_descriptions', function (Blueprint $table) {
            // CSS rules storage (JSON format)
            // v2.0: Per-style hash naming .uve-s{hash}
            // Example: {".uve-s7f3a2b1": {"font-size": "56px", "font-weight": "800"}}
            $table->json('css_rules')->nullable()->after('blocks_v2')
                ->comment('CSS class definitions for UVE elements (per-style hash)');

            // Element ID to class name mapping
            // Example: {"block-0-heading-0": "uve-s7f3a2b1", "block-0-text-0": "uve-s8c4d5e2"}
            $table->json('css_class_map')->nullable()->after('css_rules')
                ->comment('Mapping elementId -> CSS className');

            // CSS delivery mode (v2.0 - inline_style_block ELIMINATED)
            // - pending: FTP not configured, CSS sync blocked
            // - external: External uve-custom.css file via FTP
            // Note: 'inline' kept for backward compatibility during migration
            $table->enum('css_mode', ['inline', 'pending', 'external'])
                ->default('pending')
                ->after('css_class_map')
                ->comment('CSS delivery mode: pending (blocked) or external (FTP)');

            // Migration tracking - when inline styles were converted
            $table->timestamp('css_migrated_at')->nullable()->after('css_mode')
                ->comment('Timestamp when inline styles were migrated to CSS classes');

            // Sync tracking - when CSS was uploaded to PrestaShop
            $table->timestamp('css_synced_at')->nullable()->after('css_migrated_at')
                ->comment('Timestamp when CSS was synced to PrestaShop via FTP');

            // Index for finding descriptions needing migration/sync
            $table->index('css_mode');
        });
    }

    public function down(): void
    {
        Schema::table('product_descriptions', function (Blueprint $table) {
            $table->dropIndex(['css_mode']);
            $table->dropColumn([
                'css_rules',
                'css_class_map',
                'css_mode',
                'css_migrated_at',
                'css_synced_at',
            ]);
        });
    }
};
