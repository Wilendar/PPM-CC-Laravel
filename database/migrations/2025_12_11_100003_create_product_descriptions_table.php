<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Visual Description Editor - Product Descriptions Table
     *
     * Przechowuje opisy wizualne produktow per sklep.
     * Jeden produkt moze miec rozne opisy dla roznych sklepow.
     */
    public function up(): void
    {
        Schema::create('product_descriptions', function (Blueprint $table) {
            $table->id();

            // Product-Shop relation
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->comment('Produkt');

            $table->foreignId('shop_id')
                ->constrained('prestashop_shops')
                ->cascadeOnDelete()
                ->comment('Sklep PrestaShop');

            // Content
            $table->json('blocks_json')->comment('Tablica blokow z trescia i ustawieniami');

            // Rendered output
            $table->longText('rendered_html')->nullable()->comment('Wyrenderowany HTML');
            $table->timestamp('last_rendered_at')->nullable()->comment('Ostatnie renderowanie');

            // Template reference
            $table->foreignId('template_id')
                ->nullable()
                ->constrained('description_templates')
                ->nullOnDelete()
                ->comment('Szablon bazowy (jesli uzyty)');

            $table->timestamps();

            // Unique constraint - one description per product per shop
            $table->unique(['product_id', 'shop_id'], 'unique_product_shop_description');

            // Indexes
            $table->index('template_id', 'idx_descriptions_template');
            $table->index('last_rendered_at', 'idx_descriptions_rendered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_descriptions');
    }
};
