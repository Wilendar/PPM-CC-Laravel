<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FAZA 9.7 - Add import column preferences to users.
 *
 * Stores per-user column layout for import modal column mode:
 * {"active_columns": [...], "price_display_mode": "net", "updated_at": "..."}
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('import_column_preferences')->nullable()
                ->after('dashboard_widget_preferences')
                ->comment('Saved column layout for import modal column mode');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('import_column_preferences');
        });
    }
};
