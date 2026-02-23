<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Reguły synchronizacji marek dla Smart Matching
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_sync_brand_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('prestashop_shops')->cascadeOnDelete();
            $table->string('brand', 100);
            $table->boolean('is_allowed')->default(true);
            $table->boolean('auto_sync')->default(false);
            $table->decimal('min_confidence', 3, 2)->default(0.50);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['shop_id', 'brand'], 'ssbr_unique_shop_brand');
            $table->index('is_allowed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_sync_brand_rules');
    }
};
