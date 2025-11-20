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
            // Add optional default warehouse per shop
            $table->foreignId('default_warehouse_id')
                  ->nullable()
                  ->after('api_key')
                  ->constrained('warehouses')
                  ->onDelete('set null');

            // Performance index
            $table->index('default_warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            $table->dropForeign(['default_warehouse_id']);
            $table->dropIndex(['default_warehouse_id']);
            $table->dropColumn('default_warehouse_id');
        });
    }
};
