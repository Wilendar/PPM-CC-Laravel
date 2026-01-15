<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_07e FAZA 1.2.1 - Create feature_groups table
     *
     * PURPOSE:
     * - Normalize feature grouping (replace string 'group' column)
     * - Store group metadata (icon, display order)
     * - Enable per-vehicle-type group filtering
     *
     * GROUPS (from Excel analysis):
     * - identyfikacja: Marka, Model, Typ, SKU
     * - silnik: Pojemnosc, Moc, Typ silnika
     * - naped: Skrzynia, Zebatki, Lancuch
     * - wymiary: Dlugosc, Szerokosc, Wysokosc, Waga
     * - zawieszenie: Amortyzatory, Rama, Wahacz
     * - hamulce: Zaciski, Tarcze, Uklad
     * - kola: Felgi, Opony, Obrecze
     * - elektryczne: Napiecie, Pojemnosc baterii, Zasieg
     * - spalinowe: Gaznik, Chlodzenie, Zbiornik
     * - dokumentacja: Instrukcje, Katalogi
     * - inne: Gwarancja, Wiek, Waga uzytkownika
     */
    public function up(): void
    {
        Schema::create('feature_groups', function (Blueprint $table) {
            $table->id();

            // Group identification
            $table->string('code', 50)->unique(); // 'silnik', 'wymiary'
            $table->string('name', 100);          // 'Silnik', 'Wymiary'
            $table->string('name_pl', 100)->nullable(); // Polish display name

            // Display
            $table->string('icon', 50)->nullable(); // 'engine', 'ruler', 'wheel'
            $table->string('color', 20)->nullable(); // Tailwind color: 'orange', 'blue'
            $table->integer('sort_order')->default(0);

            // Conditional display
            $table->string('vehicle_type_filter', 50)->nullable(); // 'elektryczne', 'spalinowe', null=all
            $table->text('description')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_collapsible')->default(true); // Accordion behavior

            $table->timestamps();

            // Indexes
            $table->index('code', 'idx_fg_code');
            $table->index('is_active', 'idx_fg_active');
            $table->index('sort_order', 'idx_fg_sort');
            $table->index('vehicle_type_filter', 'idx_fg_vehicle_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_groups');
    }
};
