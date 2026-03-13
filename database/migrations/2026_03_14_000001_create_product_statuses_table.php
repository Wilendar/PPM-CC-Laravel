<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('color', 7)->default('#6b7280');
            $table->string('icon', 50)->nullable();
            $table->boolean('is_active_equivalent')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('sort_order');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_statuses');
    }
};
