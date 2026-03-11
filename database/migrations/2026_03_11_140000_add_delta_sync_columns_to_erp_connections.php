<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_connections', function (Blueprint $table) {
            $table->unsignedBigInteger('last_change_number')->nullable()->after('last_sync_at');
            $table->timestamp('last_stock_checksum_at')->nullable()->after('last_change_number');
        });
    }

    public function down(): void
    {
        Schema::table('erp_connections', function (Blueprint $table) {
            $table->dropColumn(['last_change_number', 'last_stock_checksum_at']);
        });
    }
};
