<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ETAP_08: Add notes field to products table
 *
 * Maps to tw_Uwagi column in Subiekt GT database.
 * Contains product notes/remarks from ERP system.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('notes')->nullable()
                  ->after('application')
                  ->comment('tw_Uwagi - Uwagi/notatki z ERP Subiekt GT');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
