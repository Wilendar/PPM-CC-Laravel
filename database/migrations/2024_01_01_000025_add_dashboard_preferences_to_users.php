<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FAZA A: Dashboard Core & Monitoring - Dashboard Preferences for Users
     * 
     * Dodaje kolumny potrzebne dla admin dashboard:
     * - dashboard_refresh_interval - częstotliwość odświeżania (30, 60, 300 sekund)
     * - dashboard_widget_preferences - JSONB z preferencjami widgetów
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Dashboard refresh settings
            $table->integer('dashboard_refresh_interval')
                  ->default(60)
                  ->after('ui_preferences')
                  ->comment('Dashboard auto-refresh interval in seconds');
                  
            // Widget preferences (JSON with widget visibility, positions, etc.)
            $table->json('dashboard_widget_preferences')
                  ->nullable()
                  ->after('dashboard_refresh_interval')
                  ->comment('Dashboard widget preferences and layout settings');
                  
            // Index for dashboard preferences queries
            $table->index('dashboard_refresh_interval', 'idx_users_dashboard_refresh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_dashboard_refresh');
            $table->dropColumn(['dashboard_refresh_interval', 'dashboard_widget_preferences']);
        });
    }
};