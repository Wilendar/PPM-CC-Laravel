<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ETAP_07f_P5 FAZA 1: Unified Visual Editor - ProductDescription fields
 *
 * Adds new columns for UVE (Unified Visual Editor) format:
 * - blocks_v2: New document structure with VBB nested elements
 * - format_version: Track which format is used (1.0 = legacy, 2.0 = UVE)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_descriptions', function (Blueprint $table) {
            // New UVE format columns
            $table->json('blocks_v2')->nullable()->after('blocks_json')
                ->comment('UVE format: blocks with document structure');

            $table->string('format_version', 10)->default('1.0')->after('blocks_v2')
                ->comment('Data format version: 1.0=legacy, 2.0=UVE');

            // Index for quick format queries
            $table->index('format_version');
        });
    }

    public function down(): void
    {
        Schema::table('product_descriptions', function (Blueprint $table) {
            $table->dropIndex(['format_version']);
            $table->dropColumn(['blocks_v2', 'format_version']);
        });
    }
};
