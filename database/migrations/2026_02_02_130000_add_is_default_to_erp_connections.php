<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FAZA 9.7 - Add is_default flag to ERP connections.
 *
 * Default ERP is ALWAYS enabled for new imported products
 * and cannot be unchecked per product.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_connections', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)
                ->after('is_active')
                ->comment('Default ERP - always enabled for new products, cannot be unchecked');
        });
    }

    public function down(): void
    {
        Schema::table('erp_connections', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
