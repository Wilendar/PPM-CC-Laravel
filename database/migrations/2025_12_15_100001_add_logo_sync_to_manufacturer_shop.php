<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add logo sync tracking fields to manufacturer_shop pivot table
 * ETAP 07g: System Synchronizacji Marek z PrestaShop
 *
 * Tracks logo synchronization status per shop
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manufacturer_shop', function (Blueprint $table) {
            // Logo sync tracking
            $table->boolean('logo_synced')->default(false)->after('sync_status');
            $table->timestamp('logo_synced_at')->nullable()->after('logo_synced');

            // Error tracking for better debugging
            $table->text('sync_error')->nullable()->after('logo_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('manufacturer_shop', function (Blueprint $table) {
            $table->dropColumn([
                'logo_synced',
                'logo_synced_at',
                'sync_error',
            ]);
        });
    }
};
