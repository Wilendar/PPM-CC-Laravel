<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_category_type_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')
                ->constrained('prestashop_shops')
                ->cascadeOnDelete();
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();
            $table->foreignId('product_type_id')
                ->constrained('product_types')
                ->cascadeOnDelete();
            $table->boolean('include_children')->default(true);
            $table->smallInteger('priority')->default(50);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['shop_id', 'category_id']);
            $table->index(['shop_id', 'is_active']);
            $table->index(['priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_category_type_mappings');
    }
};
