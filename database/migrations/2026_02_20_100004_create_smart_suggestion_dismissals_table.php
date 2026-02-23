<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Odrzucenia sugestii Smart Matching
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_suggestion_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedBigInteger('vehicle_product_id');
            $table->foreign('vehicle_product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->text('dismissal_reason')->nullable();
            $table->boolean('is_permanent')->default(false);
            $table->foreignId('dismissed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dismissed_at')->useCurrent();
            $table->timestamp('restored_at')->nullable();
            $table->foreignId('restored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'vehicle_product_id'], 'ssd_unique_product_vehicle');
            $table->index('is_permanent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_suggestion_dismissals');
    }
};
