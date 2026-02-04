<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Product Scan Sessions - glowna tabela sesji skanowania produktow
     * Przechowuje informacje o kazdym uruchomieniu skanowania:
     * - links: skanowanie powiazani SKU miedzy systemami
     * - missing_in_ppm: produkty w zrodle, ktorych brak w PPM
     * - missing_in_source: produkty w PPM, ktorych brak w zrodle
     */
    public function up(): void
    {
        if (Schema::hasTable('product_scan_sessions')) {
            return;
        }

        Schema::create('product_scan_sessions', function (Blueprint $table) {
            $table->id();

            // Typ skanowania
            $table->enum('scan_type', ['links', 'missing_in_ppm', 'missing_in_source'])
                ->comment('Typ skanowania: links=powiazania, missing_in_ppm=brakujace w PPM, missing_in_source=brakujace w zrodle');

            // Zrodlo danych
            $table->string('source_type', 50)
                ->comment('Typ zrodla: subiekt_gt, baselinker, prestashop');
            $table->unsignedBigInteger('source_id')->nullable()
                ->comment('ID polaczenia ERP (erp_connections.id) lub sklepu (prestashop_shops.id)');

            // Status sesji
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled'])
                ->default('pending')
                ->comment('Status sesji skanowania');
            $table->timestamp('started_at')->nullable()
                ->comment('Czas rozpoczecia skanowania');
            $table->timestamp('completed_at')->nullable()
                ->comment('Czas zakonczenia skanowania');

            // Statystyki
            $table->unsignedInteger('total_scanned')->default(0)
                ->comment('Laczna liczba przeskanowanych produktow');
            $table->unsignedInteger('matched_count')->default(0)
                ->comment('Liczba dopasowanych produktow');
            $table->unsignedInteger('unmatched_count')->default(0)
                ->comment('Liczba niedopasowanych produktow');
            $table->unsignedInteger('errors_count')->default(0)
                ->comment('Liczba bledow podczas skanowania');

            // Wyniki i bledy
            $table->json('result_summary')->nullable()
                ->comment('Podsumowanie wynikow skanowania (JSON)');
            $table->text('error_message')->nullable()
                ->comment('Komunikat bledu jesli status=failed');

            // Powiazania
            $table->unsignedBigInteger('sync_job_id')->nullable()
                ->comment('ID powiazanego job-a synchronizacji (jesli dotyczy)');
            $table->unsignedBigInteger('user_id')->nullable()
                ->comment('ID uzytkownika ktory uruchomil skanowanie');

            $table->timestamps();

            // Indeksy
            $table->index('scan_type', 'pss_scan_type_idx');
            $table->index(['source_type', 'source_id'], 'pss_source_idx');
            $table->index('status', 'pss_status_idx');
            $table->index('created_at', 'pss_created_at_idx');

            // Klucz obcy
            $table->foreign('user_id', 'pss_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_scan_sessions');
    }
};
