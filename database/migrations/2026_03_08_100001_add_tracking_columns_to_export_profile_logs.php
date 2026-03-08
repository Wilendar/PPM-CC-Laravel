<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add enhanced tracking columns to export_profile_logs.
     *
     * Nowe kolumny umozliwiaja szczegolowe monitorowanie:
     * - response_time_ms: czas odpowiedzi HTTP
     * - served_from: zrodlo pliku (cache/generated/on_the_fly)
     * - http_status: kod statusu HTTP
     * - content_type: Content-Type odpowiedzi
     * - referer: HTTP Referer header
     * - is_bot/bot_name: wykrywanie botow
     */
    public function up(): void
    {
        Schema::table('export_profile_logs', function (Blueprint $table) {
            // === SEKCJA 7: TRACKING ODPOWIEDZI ===
            $table->unsignedInteger('response_time_ms')->nullable()
                  ->after('duration')
                  ->comment('Czas odpowiedzi HTTP w milisekundach');

            $table->enum('served_from', ['cache', 'generated', 'on_the_fly'])->nullable()
                  ->after('response_time_ms')
                  ->comment('Zrodlo pliku: cache, generated (zaplanowana), on_the_fly (na zadanie)');

            $table->unsignedSmallInteger('http_status')->nullable()
                  ->after('served_from')
                  ->comment('Kod statusu HTTP odpowiedzi');

            $table->string('content_type', 100)->nullable()
                  ->after('http_status')
                  ->comment('Content-Type odpowiedzi');

            // === SEKCJA 8: REFERER ===
            $table->string('referer', 500)->nullable()
                  ->after('content_type')
                  ->comment('HTTP Referer header');

            // === SEKCJA 9: BOT DETECTION ===
            $table->boolean('is_bot')->default(false)
                  ->after('referer')
                  ->comment('Czy request pochodzi od bota');

            $table->string('bot_name', 100)->nullable()
                  ->after('is_bot')
                  ->comment('Nazwa wykrytego bota (Googlebot, CeneoBot, etc.)');

            // === INDEXES ===
            $table->index('is_bot', 'idx_is_bot');
            $table->index('served_from', 'idx_served_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_profile_logs', function (Blueprint $table) {
            $table->dropIndex('idx_is_bot');
            $table->dropIndex('idx_served_from');

            $table->dropColumn([
                'response_time_ms',
                'served_from',
                'http_status',
                'content_type',
                'referer',
                'is_bot',
                'bot_name',
            ]);
        });
    }
};
