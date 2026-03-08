<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * System Eksportu i Feedow - Export Profiles Table
     *
     * Tabela export_profiles przechowuje konfiguracje profili eksportu produktow:
     * - Rozne formaty: CSV, XLSX, JSON, XML (Google, Ceneo, PrestaShop)
     * - Konfigurowalne pola, filtry, grupy cenowe, magazyny, sklepy
     * - Harmonogram generowania: reczny lub automatyczny (1h-24h)
     * - Publiczny dostep przez token (dla feedow zewnetrznych)
     * - Pelny audit trail (created_by, updated_by)
     *
     * BUSINESS RULES:
     * - Token jest unikalny i sluzy do publicznego dostepu do feedu
     * - Slug jest unikalny i sluzy do identyfikacji profilu w URL
     * - is_public=true umozliwia dostep przez token bez logowania
     * - schedule okresla czestotliwosc automatycznego generowania
     * - Soft deletes zachowuja historie eksportow
     *
     * RELATIONSHIPS:
     * - created_by/updated_by -> users (set null on delete)
     * - has many export_profile_logs
     */
    public function up(): void
    {
        Schema::create('export_profiles', function (Blueprint $table) {
            // === PRIMARY KEY ===
            $table->id();

            // === SEKCJA 1: IDENTYFIKACJA PROFILU ===
            $table->string('name', 255)
                  ->comment('Nazwa profilu eksportu');

            $table->string('slug', 255)->unique()
                  ->comment('Unikalny slug do URL');

            $table->string('token', 64)->unique()
                  ->comment('Token dostepu do publicznego feedu');

            // === SEKCJA 2: FORMAT EKSPORTU ===
            $table->enum('format', [
                'csv',              // Eksport CSV
                'xlsx',             // Eksport Excel XLSX
                'json',             // Eksport JSON
                'xml_google',       // Google Shopping XML Feed
                'xml_ceneo',        // Ceneo.pl XML Feed
                'xml_prestashop',   // PrestaShop XML Import
            ])->comment('Format pliku eksportu');

            // === SEKCJA 3: KONFIGURACJA EKSPORTU (JSON) ===
            $table->json('field_config')
                  ->comment('Wybrane pola do eksportu: {"fields": ["sku", "name", "price", ...]}');

            $table->json('filter_config')
                  ->comment('Filtry produktow: {"category_id": 5, "is_active": true, "has_stock": true}');

            $table->json('price_groups')
                  ->comment('Wybrane grupy cenowe: ["detaliczna", "dealer_standard"]');

            $table->json('warehouses')
                  ->comment('Wybrane magazyny: ["MPPTRADE", "Pitbike.pl"]');

            $table->json('shop_ids')
                  ->comment('Powiazane sklepy: [1, 3, 5]');

            // === SEKCJA 4: HARMONOGRAM ===
            $table->enum('schedule', [
                'manual',   // Reczne generowanie
                '1h',       // Co godzine
                '6h',       // Co 6 godzin
                '12h',      // Co 12 godzin
                '24h',      // Raz dziennie
            ])->comment('Czestotliwosc automatycznego generowania');

            // === SEKCJA 5: STATUS I DOSTEP ===
            $table->boolean('is_active')->default(true)
                  ->comment('Czy profil jest aktywny');

            $table->boolean('is_public')->default(false)
                  ->comment('Czy feed jest publicznie dostepny przez token');

            // === SEKCJA 6: STATYSTYKI GENEROWANIA ===
            $table->string('file_path')->nullable()
                  ->comment('Sciezka do wygenerowanego pliku');

            $table->unsignedBigInteger('file_size')->nullable()
                  ->comment('Rozmiar pliku w bajtach');

            $table->unsignedInteger('product_count')->nullable()
                  ->comment('Liczba produktow w ostatnim eksporcie');

            $table->unsignedInteger('generation_duration')->nullable()
                  ->comment('Czas generowania w sekundach');

            // === SEKCJA 7: TIMESTAMPS GENEROWANIA ===
            $table->timestamp('last_generated_at')->nullable()
                  ->comment('Kiedy ostatnio wygenerowano plik');

            $table->timestamp('next_generation_at')->nullable()
                  ->comment('Kiedy nastepne automatyczne generowanie');

            // === SEKCJA 8: AUDIT TRAIL ===
            $table->foreignId('created_by')->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Uzytkownik ktory utworzyl profil');

            $table->foreignId('updated_by')->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Uzytkownik ktory ostatnio zaktualizowal profil');

            // === TIMESTAMPS & SOFT DELETES ===
            $table->timestamps();
            $table->softDeletes();

            // === INDEXES ===
            $table->index(['is_active', 'is_public'], 'idx_active_public');
            $table->index(['schedule', 'next_generation_at'], 'idx_schedule');
            $table->index('token', 'idx_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_profiles');
    }
};
