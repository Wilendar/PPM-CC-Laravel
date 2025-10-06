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
        Schema::table('products', function (Blueprint $table) {
            $table->datetime('available_from')->nullable()->after('is_variant_master')
                  ->comment('Product available from this date/time');
            $table->datetime('available_to')->nullable()->after('available_from')
                  ->comment('Product available until this date/time');

            // Add index for performance when checking availability
            $table->index(['available_from', 'available_to'], 'products_availability_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_availability_index');
            $table->dropColumn(['available_from', 'available_to']);
        });
    }
};