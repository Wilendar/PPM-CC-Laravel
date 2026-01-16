<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix method column length in integration_logs table.
 *
 * Current: varchar(20)
 * Required: varchar(100) - to accommodate method names like "getInventoryProductsList" (24 chars)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE integration_logs MODIFY COLUMN method VARCHAR(100) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE integration_logs MODIFY COLUMN method VARCHAR(20) NULL");
    }
};
