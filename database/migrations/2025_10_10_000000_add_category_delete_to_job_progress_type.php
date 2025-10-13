<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add 'category_delete' to job_type ENUM in job_progress table
     *
     * REASON: BulkDeleteCategoriesJob needs to track progress
     * but job_type ENUM was limited to ['import', 'sync', 'export']
     *
     * @return void
     */
    public function up(): void
    {
        // MySQL ENUM modification requires raw SQL
        DB::statement("ALTER TABLE `job_progress`
                      MODIFY COLUMN `job_type`
                      ENUM('import', 'sync', 'export', 'category_delete')
                      NOT NULL
                      COMMENT 'Operation type'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Remove 'category_delete' from ENUM
        DB::statement("ALTER TABLE `job_progress`
                      MODIFY COLUMN `job_type`
                      ENUM('import', 'sync', 'export')
                      NOT NULL
                      COMMENT 'Operation type'");
    }
};
