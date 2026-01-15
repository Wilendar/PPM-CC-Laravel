<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Visual Description Editor - Description Blocks Table
     *
     * Tabela przechowuje definicje blokow dostepnych w edytorze opisow.
     * Kazdy blok ma typ, kategorie, domyslne ustawienia i schemat.
     */
    public function up(): void
    {
        Schema::create('description_blocks', function (Blueprint $table) {
            $table->id();

            // Block identification
            $table->string('name', 100)->comment('Wyswietlana nazwa bloku');
            $table->string('type', 50)->unique()->comment('Unikalny identyfikator typu bloku');

            // Categorization
            $table->enum('category', [
                'layout',      // Bloki ukladu: hero, columns, grid
                'content',     // Bloki tresci: heading, text, cards
                'media',       // Bloki mediow: image, gallery, video
                'interactive'  // Bloki interaktywne: slider, tabs, accordion
            ])->comment('Kategoria bloku');

            // Visual
            $table->string('icon', 50)->nullable()->comment('Klasa ikony lub sciezka do ikony');

            // Configuration
            $table->json('default_settings')->nullable()->comment('Domyslne ustawienia bloku (JSON)');
            $table->json('schema')->nullable()->comment('Schemat bloku dla panelu wlasciwosci (JSON)');

            // Status
            $table->boolean('is_active')->default(true)->comment('Czy blok jest dostepny');
            $table->integer('sort_order')->default(0)->comment('Kolejnosc wyswietlania');

            $table->timestamps();

            // Indexes
            $table->index(['category', 'is_active'], 'idx_blocks_category_active');
            $table->index('sort_order', 'idx_blocks_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('description_blocks');
    }
};
