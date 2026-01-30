<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data migration: manufacturers -> business_partners
 * ETAP: BusinessPartner System (Dostawca/Producent/Importer)
 *
 * Preserves original IDs from manufacturers table for FK integrity.
 * All migrated records get type = 'manufacturer'.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('manufacturers')) {
            return;
        }

        $hasManufacturers = DB::table('manufacturers')->exists();
        if (!$hasManufacturers) {
            return;
        }

        // Determine which columns exist in manufacturers table
        $hasShortDescription = Schema::hasColumn('manufacturers', 'short_description');
        $hasMetaTitle = Schema::hasColumn('manufacturers', 'meta_title');
        $hasMetaDescription = Schema::hasColumn('manufacturers', 'meta_description');
        $hasMetaKeywords = Schema::hasColumn('manufacturers', 'meta_keywords');
        $hasPsLinkRewrite = Schema::hasColumn('manufacturers', 'ps_link_rewrite');

        // Build SELECT columns dynamically
        $selectColumns = [
            'id',
            "'manufacturer' as `type`",
            '`name`',
            '`code`',
            'description',
        ];

        if ($hasPsLinkRewrite) {
            $selectColumns[] = 'ps_link_rewrite';
        }
        if ($hasShortDescription) {
            $selectColumns[] = 'short_description';
        }
        if ($hasMetaTitle) {
            $selectColumns[] = 'meta_title';
        }
        if ($hasMetaDescription) {
            $selectColumns[] = 'meta_description';
        }
        if ($hasMetaKeywords) {
            $selectColumns[] = 'meta_keywords';
        }

        $selectColumns = array_merge($selectColumns, [
            'logo_path',
            'website',
            'is_active',
            'sort_order',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        // Build INSERT columns (matching SELECT)
        $insertColumns = [
            'id',
            '`type`',
            '`name`',
            '`code`',
            'description',
        ];

        if ($hasPsLinkRewrite) {
            $insertColumns[] = 'ps_link_rewrite';
        }
        if ($hasShortDescription) {
            $insertColumns[] = 'short_description';
        }
        if ($hasMetaTitle) {
            $insertColumns[] = 'meta_title';
        }
        if ($hasMetaDescription) {
            $insertColumns[] = 'meta_description';
        }
        if ($hasMetaKeywords) {
            $insertColumns[] = 'meta_keywords';
        }

        $insertColumns = array_merge($insertColumns, [
            'logo_path',
            'website',
            'is_active',
            'sort_order',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        $selectSql = implode(', ', $selectColumns);
        $insertSql = implode(', ', $insertColumns);

        // Insert with explicit IDs to preserve FK references
        DB::statement("INSERT INTO business_partners ({$insertSql}) SELECT {$selectSql} FROM manufacturers");

        // Reset AUTO_INCREMENT to max(id) + 1
        $maxId = DB::table('business_partners')->max('id');
        if ($maxId) {
            DB::statement("ALTER TABLE business_partners AUTO_INCREMENT = " . ($maxId + 1));
        }
    }

    public function down(): void
    {
        // Remove only manufacturer-type records that came from migration
        DB::table('business_partners')
            ->where('type', 'manufacturer')
            ->delete();
    }
};
