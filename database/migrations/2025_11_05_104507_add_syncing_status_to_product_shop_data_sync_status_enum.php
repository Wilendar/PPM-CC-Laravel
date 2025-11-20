<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * BUGFIX 2025-11-05: Add 'syncing' to sync_status ENUM
     *
     * PROBLEM:
     * - ProductShopData model has STATUS_SYNCING constant
     * - markSyncing() method tries to set sync_status = 'syncing'
     * - But ENUM in database only has: pending, synced, error, conflict, disabled
     * - Result: SQL error "Data truncated for column 'sync_status'"
     *
     * SOLUTION:
     * - ALTER ENUM to include 'syncing' between 'pending' and 'synced'
     * - Order: pending, syncing, synced, error, conflict, disabled
     *
     * NOTE: MySQL requires all ENUM values to be re-specified during ALTER
     */
    public function up(): void
    {
        // MySQL: ALTER COLUMN to add 'syncing' to ENUM
        DB::statement("
            ALTER TABLE product_shop_data
            MODIFY COLUMN sync_status ENUM(
                'pending',
                'syncing',
                'synced',
                'error',
                'conflict',
                'disabled'
            ) NOT NULL DEFAULT 'pending'
            COMMENT 'Status synchronizacji z tym sklepem'
        ");
    }

    /**
     * Reverse the migrations.
     *
     * Removes 'syncing' from ENUM (any rows with 'syncing' will error!)
     */
    public function down(): void
    {
        // WARNING: This will fail if any rows have sync_status = 'syncing'
        // Convert any 'syncing' to 'pending' before rollback
        DB::table('product_shop_data')
            ->where('sync_status', 'syncing')
            ->update(['sync_status' => 'pending']);

        // Remove 'syncing' from ENUM
        DB::statement("
            ALTER TABLE product_shop_data
            MODIFY COLUMN sync_status ENUM(
                'pending',
                'synced',
                'error',
                'conflict',
                'disabled'
            ) NOT NULL DEFAULT 'pending'
            COMMENT 'Status synchronizacji z tym sklepem'
        ");
    }
};
