<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            $table->string('prestashop_version_exact', 20)->nullable()->after('prestashop_version')
                  ->comment('Exact PrestaShop version (e.g. 8.2.1)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            $table->dropColumn('prestashop_version_exact');
        });
    }
};
