<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_history', function (Blueprint $table) {
            $table->tinyInteger('format_version')->default(2)->after('changed_fields');
        });

        // Oznacz istniejace rekordy jako stary format
        DB::table('price_history')->whereNull('format_version')->update(['format_version' => 1]);
    }

    public function down(): void
    {
        Schema::table('price_history', function (Blueprint $table) {
            $table->dropColumn('format_version');
        });
    }
};
