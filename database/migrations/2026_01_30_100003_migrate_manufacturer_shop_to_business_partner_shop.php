<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data migration: manufacturer_shop -> business_partner_shop
 * ETAP: BusinessPartner System (Dostawca/Producent/Importer)
 *
 * Migrates pivot data from manufacturer_shop to business_partner_shop.
 * manufacturer_id maps to business_partner_id (IDs preserved in Migration 3).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('manufacturer_shop')) {
            return;
        }

        $hasData = DB::table('manufacturer_shop')->exists();
        if (!$hasData) {
            return;
        }

        // Check which columns exist in source table
        $hasLogoSynced = Schema::hasColumn('manufacturer_shop', 'logo_synced');
        $hasLogoSyncedAt = Schema::hasColumn('manufacturer_shop', 'logo_synced_at');
        $hasSyncError = Schema::hasColumn('manufacturer_shop', 'sync_error');

        // Build columns for INSERT ... SELECT
        $insertColumns = [
            'business_partner_id',
            'prestashop_shop_id',
            'ps_manufacturer_id',
            'sync_status',
            'last_synced_at',
        ];

        $selectColumns = [
            'manufacturer_id',
            'prestashop_shop_id',
            'ps_manufacturer_id',
            'sync_status',
            'last_synced_at',
        ];

        if ($hasLogoSynced) {
            $insertColumns[] = 'logo_synced';
            $selectColumns[] = 'logo_synced';
        }
        if ($hasLogoSyncedAt) {
            $insertColumns[] = 'logo_synced_at';
            $selectColumns[] = 'logo_synced_at';
        }
        if ($hasSyncError) {
            $insertColumns[] = 'sync_error';
            $selectColumns[] = 'sync_error';
        }

        $insertColumns[] = 'created_at';
        $insertColumns[] = 'updated_at';
        $selectColumns[] = 'created_at';
        $selectColumns[] = 'updated_at';

        $insertSql = implode(', ', $insertColumns);
        $selectSql = implode(', ', $selectColumns);

        DB::statement("INSERT INTO business_partner_shop ({$insertSql}) SELECT {$selectSql} FROM manufacturer_shop");
    }

    public function down(): void
    {
        // Remove all migrated data from business_partner_shop
        DB::table('business_partner_shop')->truncate();
    }
};
