<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prestashop_shop_price_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestashop_shop_id')->constrained('prestashop_shops')->onDelete('cascade');
            $table->unsignedBigInteger('prestashop_price_group_id');
            $table->string('prestashop_price_group_name');
            $table->string('ppm_price_group_name');
            $table->timestamps();

            // Unique constraint: one PS price group can only be mapped once per shop
            $table->unique(['prestashop_shop_id', 'prestashop_price_group_id'], 'shop_ps_group_unique');

            // Index for faster lookups
            $table->index('prestashop_shop_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestashop_shop_price_mappings');
    }
};
