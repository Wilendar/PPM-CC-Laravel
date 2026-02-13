<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add 'modal_import' to import_method ENUM in import_sessions table.
     *
     * Required for the new ProductImportModal column-mode import.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE import_sessions MODIFY COLUMN import_method ENUM('paste_sku','paste_sku_name','csv','excel','erp','modal_import') NOT NULL DEFAULT 'paste_sku'");
    }

    /**
     * Remove 'modal_import' from import_method ENUM.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE import_sessions MODIFY COLUMN import_method ENUM('paste_sku','paste_sku_name','csv','excel','erp') NOT NULL DEFAULT 'paste_sku'");
    }
};
