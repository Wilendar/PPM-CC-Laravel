<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FAZA D: Integration & System Tables
     * 5.1.1 Extended Users Table - rozszerzenie istniejącej users table Laravel
     * 
     * Rozszerza standardową users table o pola wymagane przez system PPM:
     * - Dane osobowe (first_name, last_name, phone, company, position)
     * - Status i aktywność (is_active, last_login_at, avatar)
     * - Preferencje użytkownika (język, strefa czasowa, format daty)
     * - Ustawienia UI i powiadomień (JSONB)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // === SEKCJA 1: DANE OSOBOWE ===
            // Pola wymagane przez system PPM dla kompletnego profilu użytkownika
            $table->string('first_name', 100)->nullable()->after('name');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('company', 200)->nullable()->after('phone');
            $table->string('position', 100)->nullable()->after('company');
            
            // === SEKCJA 2: STATUS I AKTYWNOŚĆ ===
            // Zarządzanie aktywnością użytkowników i śledzenie logowań
            $table->boolean('is_active')->default(true)->after('position');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('avatar', 300)->nullable()->after('last_login_at');
            
            // === SEKCJA 3: PREFERENCJE UŻYTKOWNIKA ===
            // Ustawienia lokalizacji i formatowania dla aplikacji wielojęzycznej
            $table->string('preferred_language', 5)->default('pl')->after('avatar');
            $table->string('timezone', 50)->default('Europe/Warsaw')->after('preferred_language');
            $table->string('date_format', 20)->default('Y-m-d')->after('timezone');
            
            // === SEKCJA 4: USTAWIENIA UI I POWIADOMIEŃ ===
            // JSONB dla przechowywania elastycznych ustawień interfejsu
            $table->json('ui_preferences')->nullable()->after('date_format');
            $table->json('notification_settings')->nullable()->after('ui_preferences');
            
            // === INDEKSY WYDAJNOŚCIOWE ===
            // Indeksy dla często używanych zapytań
            $table->index(['is_active', 'last_login_at'], 'idx_users_activity');
            $table->index('company', 'idx_users_company');
            $table->index(['preferred_language', 'timezone'], 'idx_users_locale');
        });
        
        // === UNIQUE CONSTRAINTS ===
        // Zapobieganie duplikatom telefonów (jeśli podany)
        Schema::table('users', function (Blueprint $table) {
            $table->unique('phone', 'uk_users_phone');
        });
        
        // === KOMENTARZE TABELI ===
        DB::statement("ALTER TABLE users COMMENT = 'Extended users table for PPM system with personal data, preferences and settings'");
    }

    /**
     * Cofnięcie zmian - usuwa wszystkie dodane kolumny
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Usuń indeksy przed usunięciem kolumn
            $table->dropIndex('idx_users_activity');
            $table->dropIndex('idx_users_company');
            $table->dropIndex('idx_users_locale');
            $table->dropUnique('uk_users_phone');
            
            // Usuń kolumny w odwrotnej kolejności
            $table->dropColumn([
                'notification_settings',
                'ui_preferences',
                'date_format',
                'timezone',
                'preferred_language',
                'avatar',
                'last_login_at',
                'is_active',
                'position',
                'company',
                'phone',
                'last_name',
                'first_name'
            ]);
        });
    }
};