<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_status_id')
                ->nullable()
                ->after('is_active')
                ->constrained('product_statuses')
                ->nullOnDelete();

            $table->index('product_status_id');
        });

        // Data migration: map existing is_active to statuses
        $activeStatus = DB::table('product_statuses')->where('slug', 'aktywny')->first();
        $inactiveStatus = DB::table('product_statuses')->where('slug', 'nieaktywny')->first();

        if ($activeStatus && $inactiveStatus) {
            DB::table('products')
                ->where('is_active', true)
                ->whereNull('product_status_id')
                ->update(['product_status_id' => $activeStatus->id]);

            DB::table('products')
                ->where('is_active', false)
                ->whereNull('product_status_id')
                ->update(['product_status_id' => $inactiveStatus->id]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_status_id']);
            $table->dropColumn('product_status_id');
        });
    }
};
