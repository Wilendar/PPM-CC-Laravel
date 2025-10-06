<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Extend api_key column to support encrypted API keys.
     * Laravel encryption creates long JSON strings that exceed 200 chars.
     */
    public function up(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            // Change api_key from string(200) to text to support encryption
            $table->text('api_key')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            // Revert back to string(200) - NOTE: This may cause data loss if encrypted keys are longer
            $table->string('api_key', 200)->change();
        });
    }
};
