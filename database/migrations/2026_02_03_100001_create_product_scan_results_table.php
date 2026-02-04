<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Product Scan Results - wyniki pojedynczych produktow ze skanowania
     * Przechowuje szczegolowe informacje o kazdym przeskanowanym produkcie:
     * - dane ze zrodla (source_data)
     * - dane z PPM jesli znaleziono (ppm_data)
     * - roznice miedzy systemami (diff_data)
     * - status rozwiazania konfliktu
     */
    public function up(): void
    {
        if (Schema::hasTable('product_scan_results')) {
            return;
        }

        Schema::create('product_scan_results', function (Blueprint $table) {
            $table->id();

            // Powiazanie z sesja
            $table->unsignedBigInteger('scan_session_id')
                ->comment('ID sesji skanowania');

            // Identyfikatory produktu
            $table->string('sku', 100)->nullable()
                ->comment('SKU produktu (glowny identyfikator)');
            $table->string('external_id', 100)->nullable()
                ->comment('ID produktu w zewnetrznym systemie');
            $table->string('name', 255)->nullable()
                ->comment('Nazwa produktu (dla latwiejszej identyfikacji)');

            // Status dopasowania
            $table->enum('match_status', ['matched', 'unmatched', 'conflict', 'multiple'])
                ->comment('Status dopasowania: matched=znaleziono, unmatched=brak, conflict=konflikt danych, multiple=wiele dopasowani');
            $table->unsignedBigInteger('ppm_product_id')->nullable()
                ->comment('ID produktu w PPM jesli znaleziono');
            $table->string('external_source_type', 50)->nullable()
                ->comment('Typ zewnetrznego zrodla (dla referencji)');
            $table->unsignedBigInteger('external_source_id')->nullable()
                ->comment('ID zewnetrznego zrodla (dla referencji)');

            // Dane szczegolowe (JSON)
            $table->json('source_data')->nullable()
                ->comment('Dane produktu ze zrodla zewnetrznego (JSON)');
            $table->json('ppm_data')->nullable()
                ->comment('Dane produktu z PPM jesli znaleziono (JSON)');
            $table->json('diff_data')->nullable()
                ->comment('Roznice miedzy zrodlem a PPM (JSON)');

            // Status rozwiazania
            $table->enum('resolution_status', ['pending', 'linked', 'created', 'ignored', 'error'])
                ->default('pending')
                ->comment('Status rozwiazania: pending=oczekuje, linked=powiazano, created=utworzono, ignored=zignorowano, error=blad');
            $table->timestamp('resolved_at')->nullable()
                ->comment('Czas rozwiazania');
            $table->unsignedBigInteger('resolved_by')->nullable()
                ->comment('ID uzytkownika ktory rozwiazal');
            $table->text('resolution_notes')->nullable()
                ->comment('Notatki dotyczace rozwiazania');

            // Tylko created_at (bez updated_at - wyniki nie sa modyfikowane czesto)
            $table->timestamp('created_at')->useCurrent();

            // Klucze obce
            $table->foreign('scan_session_id', 'psr_session_fk')
                ->references('id')
                ->on('product_scan_sessions')
                ->cascadeOnDelete();

            $table->foreign('ppm_product_id', 'psr_product_fk')
                ->references('id')
                ->on('products')
                ->nullOnDelete();

            $table->foreign('resolved_by', 'psr_resolved_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Indeksy
            $table->index('scan_session_id', 'psr_session_idx');
            $table->index('sku', 'psr_sku_idx');
            $table->index('match_status', 'psr_match_status_idx');
            $table->index('resolution_status', 'psr_resolution_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_scan_results');
    }
};
