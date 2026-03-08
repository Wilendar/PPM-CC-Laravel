<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * System Eksportu i Feedow - Export Profile Logs Table
     *
     * Tabela export_profile_logs przechowuje logi aktywnosci profili eksportu:
     * - Generowanie plikow (generated)
     * - Pobrania przez uzytkownikow (downloaded)
     * - Dostep publiczny przez token (accessed)
     * - Bledy generowania (error)
     *
     * BUSINESS RULES:
     * - Cascade delete: usuniecie profilu usuwa wszystkie logi
     * - user_id nullable: dostep publiczny nie wymaga logowania
     * - ip_address/user_agent: pelny tracking dostepu do feedow
     * - error_message: diagnostyka bledow generowania
     *
     * RELATIONSHIPS:
     * - belongs to ExportProfile (cascade delete)
     * - belongs to User (set null on delete, nullable)
     */
    public function up(): void
    {
        Schema::create('export_profile_logs', function (Blueprint $table) {
            // === PRIMARY KEY ===
            $table->id();

            // === SEKCJA 1: POWIAZANIE Z PROFILEM ===
            $table->foreignId('export_profile_id')
                  ->constrained('export_profiles')
                  ->cascadeOnDelete()
                  ->comment('Profil eksportu ktorego dotyczy log');

            // === SEKCJA 2: RODZAJ AKCJI ===
            $table->enum('action', [
                'generated',    // Plik zostal wygenerowany
                'downloaded',   // Plik zostal pobrany przez uzytkownika
                'accessed',     // Feed zostal odczytany publicznie (token)
                'error',        // Blad podczas generowania
            ])->comment('Typ akcji wykonanej na profilu');

            // === SEKCJA 3: UZYTKOWNIK (opcjonalny) ===
            $table->foreignId('user_id')->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Uzytkownik ktory wykonal akcje (null = dostep publiczny/system)');

            // === SEKCJA 4: METADANE DOSTEPU ===
            $table->string('ip_address', 45)->nullable()
                  ->comment('Adres IP (IPv4/IPv6)');

            $table->string('user_agent', 500)->nullable()
                  ->comment('User-Agent przegladarki/klienta');

            // === SEKCJA 5: STATYSTYKI GENEROWANIA ===
            $table->unsignedInteger('product_count')->nullable()
                  ->comment('Liczba produktow w eksporcie');

            $table->unsignedBigInteger('file_size')->nullable()
                  ->comment('Rozmiar wygenerowanego pliku w bajtach');

            $table->unsignedInteger('duration')->nullable()
                  ->comment('Czas trwania operacji w sekundach');

            // === SEKCJA 6: OBSLUGA BLEDOW ===
            $table->text('error_message')->nullable()
                  ->comment('Komunikat bledu (dla akcji error)');

            // === TIMESTAMPS ===
            $table->timestamps();

            // === INDEXES ===
            $table->index(['export_profile_id', 'action'], 'idx_profile_action');
            $table->index('created_at', 'idx_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_profile_logs');
    }
};
