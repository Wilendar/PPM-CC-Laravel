<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_reports', function (Blueprint $table) {
            $table->dropUnique('unique_report_per_period');
        });
    }

    public function down(): void
    {
        Schema::table('system_reports', function (Blueprint $table) {
            $table->unique(['type', 'period', 'report_date'], 'unique_report_per_period');
        });
    }
};
