<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FAZA D: Integration & System Tables
     * 5.2.1 Audit Logs Table - kompletny audit trail system
     * 
     * System śledzenia wszystkich zmian w aplikacji PPM:
     * - Kto wykonał operację (user_id)
     * - Na jakim obiekcie (auditable_type, auditable_id) 
     * - Jakie zmiany (old_values, new_values - JSONB)
     * - Kiedy i skąd (timestamp, IP, user_agent)
     * - Źródło zmiany (web, api, import, sync)
     * 
     * Performance: Strategiczne indeksy dla szybkich zapytań audit
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            // === PRIMARY KEY ===
            $table->id(); // SERIAL PRIMARY KEY
            
            // === SEKCJA 1: KTO WYKONAŁ OPERACJĘ ===
            // NULL = operacja systemowa (import, sync)
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            // === SEKCJA 2: NA JAKIM OBIEKCIE ===
            // Polymorphic relation - może być Product, Category, User, etc.
            $table->string('auditable_type', 100)->index(); // Product, Category, User
            $table->unsignedBigInteger('auditable_id')->index(); // ID obiektu
            
            // === SEKCJA 3: RODZAJ OPERACJI ===
            $table->string('event', 50)->index(); // created, updated, deleted, restored
            
            // === SEKCJA 4: DANE ZMIAN ===
            // JSONB dla wydajności zapytań na danych JSON
            $table->json('old_values')->nullable(); // Stare wartości pól
            $table->json('new_values')->nullable(); // Nowe wartości pól
            
            // === SEKCJA 5: METADANE SESJI ===
            // Informacje o źródle zmiany
            $table->string('ip_address', 45)->nullable(); // IPv4/IPv6 support
            $table->text('user_agent')->nullable(); // Browser/API client info
            
            // === SEKCJA 6: ŹRÓDŁO ZMIANY ===
            // Rozróżnienie źródła operacji dla różnych strategii auditu
            $table->enum('source', ['web', 'api', 'import', 'sync'])->default('web')->index();
            
            // === SEKCJA 7: OPCJONALNE KOMENTARZE ===
            // Dla operacji wymagających uzasadnienia
            $table->text('comment')->nullable();
            
            // === TIMESTAMPS ===
            $table->timestamp('created_at')->useCurrent();
            
            // === COMPOSITE INDEXES DLA PERFORMANCE ===
            // Najczęściej używane zapytania audit
            $table->index(['auditable_type', 'auditable_id'], 'idx_audit_polymorphic');
            $table->index(['user_id', 'created_at'], 'idx_audit_user_time');
            $table->index(['event', 'created_at'], 'idx_audit_event_time');
            $table->index(['created_at', 'source'], 'idx_audit_time_source');
            
            // Index dla archiwizacji starych rekordów
            $table->index('created_at', 'idx_audit_created_at');
        });
        
        // === KOMENTARZE TABELI ===
        DB::statement("ALTER TABLE audit_logs COMMENT = 'Complete audit trail system for tracking all changes in PPM application'");
        
        // === PERFORMANCE CONSIDERATIONS ===
        // Dla shared hosting - optymalizacja storage i memory
        DB::statement("ALTER TABLE audit_logs ENGINE=InnoDB ROW_FORMAT=COMPRESSED");
    }

    /**
     * Cofnięcie zmian - usuwa tabelę audit_logs
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};