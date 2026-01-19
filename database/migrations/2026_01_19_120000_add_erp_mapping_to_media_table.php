<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ETAP_08.6: Add ERP mapping column to media table
 *
 * Adds erp_mapping JSON column for storing ERP integration data (Baselinker, Subiekt GT, etc.)
 * Structure mirrors prestashop_mapping but for ERP systems:
 * {
 *   "baselinker_1": {
 *     "product_id": "358946840",
 *     "image_position": 0,
 *     "synced_at": "2026-01-19T14:30:00Z"
 *   },
 *   "subiekt_gt_1": {...}
 * }
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->json('erp_mapping')
                  ->nullable()
                  ->after('prestashop_mapping')
                  ->comment('Mapowanie per ERP connection (Baselinker, Subiekt GT, etc.)');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('erp_mapping');
        });
    }
};
