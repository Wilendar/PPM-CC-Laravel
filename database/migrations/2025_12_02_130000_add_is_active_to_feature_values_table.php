<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_07e FAZA 3.1 - Add is_active column to feature_values
     */
    public function up(): void
    {
        Schema::table('feature_values', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feature_values', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
