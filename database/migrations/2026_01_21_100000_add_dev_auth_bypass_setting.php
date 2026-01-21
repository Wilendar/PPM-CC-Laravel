<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SystemSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add DEV_AUTH_BYPASS setting to security category
        SystemSetting::set(
            key: 'dev_auth_bypass',
            value: false,
            category: 'security',
            type: 'boolean',
            description: 'Development Mode: Bypass authentication middleware (NEVER enable in production!)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SystemSetting::where('key', 'dev_auth_bypass')->delete();
    }
};
