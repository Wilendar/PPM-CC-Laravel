<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds auto prefix/suffix fields to attribute_values table
     * for automatic SKU naming when creating variants with this attribute value.
     *
     * FIELDS:
     * - auto_prefix: String to prepend to SKU (e.g., "XXX" -> "XXX-SKU")
     * - auto_suffix: String to append to SKU (e.g., "XXX" -> "SKU-XXX")
     * - auto_prefix_enabled: Whether to auto-apply prefix
     * - auto_suffix_enabled: Whether to auto-apply suffix
     *
     * @version 1.0
     * @since 2025-12-09
     */
    public function up(): void
    {
        Schema::table('attribute_values', function (Blueprint $table) {
            // Auto prefix for SKU generation
            $table->string('auto_prefix', 20)->nullable()->after('color_hex');
            $table->boolean('auto_prefix_enabled')->default(false)->after('auto_prefix');

            // Auto suffix for SKU generation
            $table->string('auto_suffix', 20)->nullable()->after('auto_prefix_enabled');
            $table->boolean('auto_suffix_enabled')->default(false)->after('auto_suffix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropColumn([
                'auto_prefix',
                'auto_prefix_enabled',
                'auto_suffix',
                'auto_suffix_enabled',
            ]);
        });
    }
};
