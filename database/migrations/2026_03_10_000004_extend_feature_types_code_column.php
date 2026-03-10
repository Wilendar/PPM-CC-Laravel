<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX 2026-03-10: Extend feature_types.code from VARCHAR(50) to VARCHAR(255)
 *
 * Problem: Auto-generated codes from PrestaShop feature names can exceed 50 chars
 * Example: "typ_akumulatorabaterii_kwasowo_oowiowy_litow_jonowy" = 52 chars
 * This causes "Data too long for column 'code'" errors during import
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feature_types', function (Blueprint $table) {
            $table->string('code', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('feature_types', function (Blueprint $table) {
            $table->string('code', 50)->change();
        });
    }
};
