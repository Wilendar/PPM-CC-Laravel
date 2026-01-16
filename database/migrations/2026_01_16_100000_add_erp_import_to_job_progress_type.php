<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FAZA 10: Add 'erp_import' to job_type ENUM in job_progress table
 *
 * Required for ERP Import in ProductList to track import progress
 * from BaseLinker and other ERP systems.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Extend job_type enum with erp_import
        DB::statement("ALTER TABLE job_progress MODIFY COLUMN job_type ENUM(
            'import',
            'sync',
            'export',
            'category_delete',
            'category_analysis',
            'bulk_export',
            'bulk_update',
            'stock_sync',
            'price_sync',
            'erp_import'
        ) NOT NULL");
    }

    public function down(): void
    {
        // Revert - remove erp_import from enum
        DB::statement("ALTER TABLE job_progress MODIFY COLUMN job_type ENUM(
            'import',
            'sync',
            'export',
            'category_delete',
            'category_analysis',
            'bulk_export',
            'bulk_update',
            'stock_sync',
            'price_sync'
        ) NOT NULL");
    }
};
