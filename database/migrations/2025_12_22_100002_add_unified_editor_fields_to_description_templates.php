<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ETAP_07f_P5 FAZA 1: Unified Visual Editor - DescriptionTemplate fields
 *
 * Extends templates for UVE:
 * - source_type: How template was created (import, manual, auto)
 * - source_shop_id: Original shop (for imports)
 * - source_product_id: Original product (for imports)
 * - structure_signature: MD5 hash for deduplication
 * - labels: JSON array of labels (tags)
 * - variables: JSON schema of editable variables
 * - css_classes: JSON array of required CSS classes
 * - document_json: New UVE document structure (replaces blocks_json)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('description_templates', function (Blueprint $table) {
            // Source tracking
            $table->string('source_type', 20)->default('manual')->after('blocks_json')
                ->comment('How template was created: import, manual, auto');

            $table->unsignedBigInteger('source_shop_id')->nullable()->after('source_type')
                ->comment('Shop ID where template originated');

            $table->unsignedBigInteger('source_product_id')->nullable()->after('source_shop_id')
                ->comment('Product ID if created from product description');

            // Deduplication
            $table->string('structure_signature', 32)->nullable()->after('source_product_id')
                ->comment('MD5 hash of document structure for deduplication');

            // UVE document structure
            $table->json('document_json')->nullable()->after('structure_signature')
                ->comment('UVE document structure (replaces blocks_json)');

            // Template metadata
            $table->json('labels')->nullable()->after('document_json')
                ->comment('JSON array of labels/tags');

            $table->json('variables')->nullable()->after('labels')
                ->comment('JSON schema of editable template variables');

            $table->json('css_classes')->nullable()->after('variables')
                ->comment('JSON array of required CSS classes');

            // Usage tracking
            $table->unsignedInteger('usage_count')->default(0)->after('css_classes')
                ->comment('Number of products using this template');

            // Indexes
            $table->index('source_type');
            $table->index('structure_signature');
            $table->index(['shop_id', 'source_type']);

            // Foreign keys
            $table->foreign('source_shop_id')
                ->references('id')->on('prestashop_shops')
                ->onDelete('set null');

            $table->foreign('source_product_id')
                ->references('id')->on('products')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('description_templates', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['source_shop_id']);
            $table->dropForeign(['source_product_id']);

            // Drop indexes
            $table->dropIndex(['source_type']);
            $table->dropIndex(['structure_signature']);
            $table->dropIndex(['shop_id', 'source_type']);

            // Drop columns
            $table->dropColumn([
                'source_type',
                'source_shop_id',
                'source_product_id',
                'structure_signature',
                'document_json',
                'labels',
                'variables',
                'css_classes',
                'usage_count',
            ]);
        });
    }
};
