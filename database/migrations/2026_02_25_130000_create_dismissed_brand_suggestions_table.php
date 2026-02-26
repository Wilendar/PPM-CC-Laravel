<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Odrzucenia sugestii brandow w Cross-Source Matrix Panel
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dismissed_brand_suggestions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('brand');
            $table->timestamps();

            $table->unique(['user_id', 'shop_id', 'brand'], 'dbs_unique_user_shop_brand');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['shop_id', 'brand'], 'dbs_shop_brand_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dismissed_brand_suggestions');
    }
};
