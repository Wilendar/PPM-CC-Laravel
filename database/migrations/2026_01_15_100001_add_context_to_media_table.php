<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add context field to media table
 *
 * Purpose: Isolate UVE (Visual Editor) media from Product Gallery
 * - UVE uploads backgrounds/decorations that should NOT appear in product gallery
 * - Gallery shows only product-specific images (photos, main images)
 *
 * Context values:
 * - product_gallery: Main product images shown in gallery
 * - visual_description: UVE backgrounds, decorations, visual editor assets
 * - variant: Variant-specific images
 * - other: Uncategorized media
 *
 * @since ETAP_07h: UVE-Gallery Media Isolation
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            // Add context enum column after mime_type
            $table->enum('context', [
                'product_gallery',
                'visual_description',
                'variant',
                'other'
            ])
            ->default('product_gallery')
            ->after('mime_type')
            ->comment('Media context: product_gallery, visual_description, variant, other');

            // Add index for filtering by context
            $table->index(['mediable_type', 'mediable_id', 'context'], 'idx_media_context');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex('idx_media_context');
            $table->dropColumn('context');
        });
    }
};
