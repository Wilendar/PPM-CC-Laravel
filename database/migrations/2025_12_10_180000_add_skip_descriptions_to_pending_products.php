<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Skip Descriptions Flag to Pending Products
 *
 * ETAP_06 FAZA 6.5.4 - DescriptionModal Enhancement
 *
 * Dodaje flage "Publikuj bez opisow" (skip_descriptions)
 * analogicznie do istniejacych skip_features, skip_compatibility, skip_images.
 *
 * @package Database\Migrations
 * @since 2025-12-10
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pending_products', function (Blueprint $table) {
            // Skip descriptions flag (after skip_images)
            if (!Schema::hasColumn('pending_products', 'skip_descriptions')) {
                $table->boolean('skip_descriptions')->default(false)->after('skip_images');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_products', function (Blueprint $table) {
            $table->dropColumn('skip_descriptions');
        });
    }
};
