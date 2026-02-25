<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_filter_presets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('context', 50)->default('product_list');
            $table->json('filters');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'name', 'context']);
            $table->index(['user_id', 'context']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_filter_presets');
    }
};
