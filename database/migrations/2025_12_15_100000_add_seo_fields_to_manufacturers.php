<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add SEO and extended fields to manufacturers table
 * ETAP 07g: System Synchronizacji Marek z PrestaShop
 *
 * Adds fields for full PrestaShop manufacturer sync:
 * - short_description, meta_title, meta_description, meta_keywords
 * - ps_link_rewrite for SEO-friendly URLs
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manufacturers', function (Blueprint $table) {
            // SEO fields - after description
            $table->text('short_description')->nullable()->after('description');
            $table->string('meta_title', 255)->nullable()->after('short_description');
            $table->string('meta_description', 512)->nullable()->after('meta_title');
            $table->string('meta_keywords', 255)->nullable()->after('meta_description');

            // PrestaShop URL slug
            $table->string('ps_link_rewrite', 128)->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('manufacturers', function (Blueprint $table) {
            $table->dropColumn([
                'short_description',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'ps_link_rewrite',
            ]);
        });
    }
};
