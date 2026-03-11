<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_connections', function (Blueprint $table) {
            $table->dropColumn('last_change_number');
        });

        Schema::table('erp_connections', function (Blueprint $table) {
            $table->timestamp('last_change_time')->nullable()->after('last_sync_at');
        });
    }

    public function down(): void
    {
        Schema::table('erp_connections', function (Blueprint $table) {
            $table->dropColumn('last_change_time');
        });

        Schema::table('erp_connections', function (Blueprint $table) {
            $table->unsignedBigInteger('last_change_number')->nullable()->after('last_sync_at');
        });
    }
};
