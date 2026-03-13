<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Extend ENUM 'action' in export_profile_logs with 'token_rotated'.
     *
     * Fixes 500 error when ExportManager::regenerateToken() logs
     * action = 'token_rotated' (not in original ENUM).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE export_profile_logs MODIFY COLUMN action ENUM('generated','downloaded','accessed','error','token_rotated') NOT NULL");
    }

    /**
     * Reverse: remove 'token_rotated' from ENUM.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE export_profile_logs MODIFY COLUMN action ENUM('generated','downloaded','accessed','error') NOT NULL");
    }
};
