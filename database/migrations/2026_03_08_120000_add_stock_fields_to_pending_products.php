<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add minimum_stock and location fields to pending_products table.
 *
 * These fields allow import panel users to specify stock-related data
 * that gets transferred to product_stock upon publication.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_products', function (Blueprint $table) {
            $table->unsignedInteger('minimum_stock')->nullable()->after('application');
            $table->string('location', 50)->nullable()->after('minimum_stock');
        });
    }

    public function down(): void
    {
        Schema::table('pending_products', function (Blueprint $table) {
            $table->dropColumn(['minimum_stock', 'location']);
        });
    }
};
