<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Visual Description Editor - Shop Stylesets Table
     *
     * Przechowuje zestawy stylow CSS per sklep.
     * Kazdy sklep moze miec wlasny zestaw zmiennych CSS i stylow.
     */
    public function up(): void
    {
        Schema::create('shop_stylesets', function (Blueprint $table) {
            $table->id();

            // Shop association
            $table->foreignId('shop_id')
                ->constrained('prestashop_shops')
                ->cascadeOnDelete()
                ->comment('Sklep PrestaShop');

            // Styleset info
            $table->string('name', 100)->comment('Nazwa zestawu stylow');
            $table->string('css_namespace', 20)->default('pd-')->comment('Prefix CSS (pd-*, blok-*)');

            // CSS content
            $table->longText('css_content')->comment('Pelna tresc CSS');
            $table->json('variables_json')->nullable()->comment('Zmienne CSS jako JSON');

            // Status
            $table->boolean('is_active')->default(true)->comment('Czy zestaw jest aktywny');

            $table->timestamps();

            // Unique constraint - unique name per shop
            $table->unique(['shop_id', 'name'], 'unique_shop_styleset_name');

            // Indexes
            $table->index(['shop_id', 'is_active'], 'idx_stylesets_shop_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_stylesets');
    }
};
