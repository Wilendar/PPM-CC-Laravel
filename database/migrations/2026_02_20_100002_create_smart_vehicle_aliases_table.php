<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Aliasy pojazdów dla Smart Matching
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_vehicle_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('alias', 150);
            $table->string('alias_normalized', 150);
            $table->enum('alias_type', ['model_code', 'common_name', 'sku_pattern', 'abbreviation'])->default('model_code');
            $table->boolean('is_auto_generated')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['vehicle_product_id', 'alias_normalized'], 'sva_unique_vehicle_alias');
            $table->index('alias_normalized');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_vehicle_aliases');
    }
};
