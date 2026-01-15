<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add orphan history tracking to media table
 *
 * Tracks where media was attached before becoming orphaned,
 * enabling users to see the history of orphaned media and
 * potentially reassign it to the original product.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            // JSON field to store orphan history
            // Structure: {
            //   "previous_type": "App\\Models\\Product",
            //   "previous_id": 12345,
            //   "previous_name": "Product Name",
            //   "previous_sku": "SKU-123",
            //   "orphaned_at": "2025-12-02T12:00:00Z",
            //   "orphan_reason": "product_deleted" | "manual_detach" | "bulk_operation"
            // }
            $table->json('orphan_history')->nullable()->after('sync_status')
                  ->comment('Historia osierocenia - gdzie zdjecie bylo wczesniej przypiete');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('orphan_history');
        });
    }
};
