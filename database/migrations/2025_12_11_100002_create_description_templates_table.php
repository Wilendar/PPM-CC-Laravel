<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Visual Description Editor - Description Templates Table
     *
     * Szablony opisow, ktore moga byc globalne lub per-sklep.
     * Zawieraja predefiniowany uklad blokow z trescia i ustawieniami.
     */
    public function up(): void
    {
        Schema::create('description_templates', function (Blueprint $table) {
            $table->id();

            // Template info
            $table->string('name', 200)->comment('Nazwa szablonu');
            $table->text('description')->nullable()->comment('Opis szablonu');

            // Shop association (nullable = global template)
            $table->foreignId('shop_id')
                ->nullable()
                ->constrained('prestashop_shops')
                ->nullOnDelete()
                ->comment('Sklep (null = globalny szablon)');

            // Content
            $table->json('blocks_json')->comment('Tablica blokow z trescia i ustawieniami');

            // Visual preview
            $table->string('thumbnail_path', 500)->nullable()->comment('Sciezka do miniaturki');

            // Status
            $table->boolean('is_default')->default(false)->comment('Domyslny szablon dla sklepu');

            // Audit
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Uzytkownik ktory utworzyl');

            $table->timestamps();

            // Indexes
            $table->index(['shop_id', 'is_default'], 'idx_templates_shop_default');
            $table->index('created_by', 'idx_templates_creator');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('description_templates');
    }
};
