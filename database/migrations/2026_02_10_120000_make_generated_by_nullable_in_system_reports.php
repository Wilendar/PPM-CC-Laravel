<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_reports', function (Blueprint $table) {
            $table->foreignId('generated_by')
                  ->nullable()
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('system_reports', function (Blueprint $table) {
            $table->foreignId('generated_by')
                  ->nullable(false)
                  ->change();
        });
    }
};
