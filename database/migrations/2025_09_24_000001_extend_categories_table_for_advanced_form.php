<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extend Categories Table for Advanced CategoryForm
 *
 * Adds fields for CategoryForm component features:
 * - Short description for listings
 * - Featured category flag
 * - Extended SEO fields (keywords, canonical, OG tags)
 * - File storage paths (icon, banner)
 * - JSON settings (visual, visibility, defaults)
 *
 * @since ETAP_05 - FAZA 3: Category Form Management (2.1.2)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Extended description fields
            $table->text('short_description')->nullable()->after('description');

            // Featured category flag
            $table->boolean('is_featured')->default(false)->after('is_active');

            // Extended SEO fields
            $table->string('meta_keywords', 500)->nullable()->after('meta_description');
            $table->string('canonical_url', 500)->nullable()->after('meta_keywords');

            // OpenGraph / Social Media fields
            $table->string('og_title', 300)->nullable()->after('canonical_url');
            $table->string('og_description', 300)->nullable()->after('og_title');
            $table->string('og_image', 500)->nullable()->after('og_description');

            // File storage paths
            $table->string('icon_path', 500)->nullable()->after('icon');
            $table->string('banner_path', 500)->nullable()->after('icon_path');

            // JSON configuration fields
            $table->json('visual_settings')->nullable()->after('banner_path');
            $table->json('visibility_settings')->nullable()->after('visual_settings');
            $table->json('default_values')->nullable()->after('visibility_settings');

            // Indexes for performance
            $table->index('is_featured', 'categories_is_featured_index');
            $table->index(['is_active', 'is_featured'], 'categories_active_featured_index');

            // Full-text index for enhanced search
            $table->fullText(['name', 'description', 'short_description', 'meta_keywords'], 'categories_content_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('categories_is_featured_index');
            $table->dropIndex('categories_active_featured_index');
            $table->dropFullText('categories_content_fulltext');

            // Drop columns
            $table->dropColumn([
                'short_description',
                'is_featured',
                'meta_keywords',
                'canonical_url',
                'og_title',
                'og_description',
                'og_image',
                'icon_path',
                'banner_path',
                'visual_settings',
                'visibility_settings',
                'default_values',
            ]);
        });
    }
};