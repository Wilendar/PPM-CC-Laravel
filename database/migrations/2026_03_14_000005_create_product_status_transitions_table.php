<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_status_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_status_id')->constrained('product_statuses')->cascadeOnDelete();
            $table->foreignId('to_status_id')->constrained('product_statuses')->cascadeOnDelete();
            $table->string('trigger', 50)->default('stock_depleted');
            $table->integer('stock_at_transition')->default(0);
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->json('sync_results')->nullable();
            $table->timestamp('transitioned_at')->useCurrent();
            $table->timestamps();

            $table->index(['product_id', 'transitioned_at']);
            $table->index('trigger');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_status_transitions');
    }
};
