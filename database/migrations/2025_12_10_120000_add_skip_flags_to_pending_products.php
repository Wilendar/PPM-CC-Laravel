<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Skip Flags to Pending Products
 *
 * ETAP_06 Import/Export - Quick Actions Enhancement
 *
 * Dodaje flagi "Brak X" (skip_features, skip_compatibility, skip_images)
 * wraz z historia zmian (kto i kiedy zaznaczyl).
 *
 * Te flagi:
 * - Color-koduja ikone na czerwono w UI
 * - Traktuja dany element jako "wypelniony" w obliczeniu % progress
 * - Sa zapisywane z historia (timestamp + user_id)
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
            // ===========================================
            // SKIP FLAGS (Brak X)
            // ===========================================

            // Brak cech (dla typu "Pojazd")
            $table->boolean('skip_features')->default(false)->after('feature_data');

            // Brak dopasowan (dla typu "Czesc zamiennicza")
            $table->boolean('skip_compatibility')->default(false)->after('skip_features');

            // Publikuj bez zdjec
            $table->boolean('skip_images')->default(false)->after('skip_compatibility');

            // ===========================================
            // SKIP HISTORY (JSON)
            // ===========================================
            // Format: {
            //   "skip_features": {"set_at": "2025-12-10T12:00:00", "set_by": 8, "set_by_name": "Admin"},
            //   "skip_compatibility": null,
            //   "skip_images": {"set_at": "2025-12-10T12:05:00", "set_by": 8, "set_by_name": "Admin"}
            // }
            $table->json('skip_history')->nullable()->after('skip_images');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_products', function (Blueprint $table) {
            $table->dropColumn([
                'skip_features',
                'skip_compatibility',
                'skip_images',
                'skip_history',
            ]);
        });
    }
};
