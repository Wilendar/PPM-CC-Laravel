<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add location sync configuration to ERP connections.
 * Enables bidirectional warehouse location synchronization PPM <-> ERP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_connections', function (Blueprint $table) {
            $table->string('location_sync_frequency')->default('daily')->after('basic_data_sync_frequency');
            $table->boolean('is_location_source')->default(false)->after('is_stock_source');
        });
    }

    public function down(): void
    {
        Schema::table('erp_connections', function (Blueprint $table) {
            $table->dropColumn(['location_sync_frequency', 'is_location_source']);
        });
    }
};
