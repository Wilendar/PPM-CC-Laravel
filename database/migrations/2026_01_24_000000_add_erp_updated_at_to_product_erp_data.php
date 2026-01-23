<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add erp_updated_at to product_erp_data table
 *
 * ETAP_08.X: Auto Sync - ERP Change Detection
 *
 * Adds erp_updated_at column for tracking ERP source timestamp.
 * Used by needsRePull() to detect if ERP data has changed since last pull.
 *
 * @package Database\Migrations
 * @version 1.0
 * @since ETAP_08 - Subiekt GT Integration Fix
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_erp_data', function (Blueprint $table) {
            $table->timestamp('erp_updated_at')
                ->nullable()
                ->after('last_pull_at')
                ->comment('Cached ERP source timestamp for change detection');

            // Index for change detection queries
            $table->index(['erp_connection_id', 'erp_updated_at'], 'idx_erp_change_detection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_erp_data', function (Blueprint $table) {
            $table->dropIndex('idx_erp_change_detection');
            $table->dropColumn('erp_updated_at');
        });
    }
};
