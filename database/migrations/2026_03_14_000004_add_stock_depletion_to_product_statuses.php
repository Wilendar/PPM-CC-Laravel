<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_statuses', function (Blueprint $table) {
            $table->boolean('transition_on_stock_depleted')->default(false)->after('sort_order');
            $table->foreignId('transition_to_status_id')
                ->nullable()
                ->after('transition_on_stock_depleted')
                ->constrained('product_statuses')
                ->nullOnDelete();
            $table->foreignId('depletion_warehouse_id')
                ->nullable()
                ->after('transition_to_status_id')
                ->constrained('warehouses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_statuses', function (Blueprint $table) {
            $table->dropForeign(['transition_to_status_id']);
            $table->dropForeign(['depletion_warehouse_id']);
            $table->dropColumn([
                'transition_on_stock_depleted',
                'transition_to_status_id',
                'depletion_warehouse_id',
            ]);
        });
    }
};
