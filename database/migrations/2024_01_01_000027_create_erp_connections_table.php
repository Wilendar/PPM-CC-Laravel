<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * FAZA B: Shop & ERP Management - ERP Connections Table
     * 
     * Tabela erp_connections przechowuje konfiguracje wszystkich systemów ERP
     * podłączonych do PPM-CC-Laravel:
     * - Baselinker (priorytet #1)
     * - Subiekt GT (enterprise ERP)
     * - Microsoft Dynamics (business intelligence)
     * - Custom integrations (future extensibility)
     * 
     * Enterprise Features:
     * - Multi-instance support (wiele instancji tego samego ERP)
     * - Advanced authentication (OAuth2, API Keys, DLL bridges)
     * - Comprehensive error handling i retry logic
     * - Performance monitoring i health checks
     * - Data mapping i transformation configurations
     */
    public function up(): void
    {
        Schema::create('erp_connections', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // ERP System Identification
            $table->enum('erp_type', [
                'baselinker',      // Baselinker API
                'subiekt_gt',      // Subiekt GT DLL/API
                'dynamics',        // Microsoft Dynamics 365
                'insert',          // Insert.com.pl
                'custom'           // Custom ERP integration
            ])->comment('Typ systemu ERP');
            
            $table->string('instance_name', 200)->comment('Nazwa instancji (dla multi-instance)');
            $table->string('description', 1000)->nullable()->comment('Opis połączenia ERP');
            $table->boolean('is_active')->default(true)->comment('Czy połączenie jest aktywne');
            $table->integer('priority')->default(1)->comment('Priorytet synchronizacji (1=najwyższy)');
            
            // Connection Configuration (encrypted JSONB)
            $table->json('connection_config')->comment('Konfiguracja połączenia (encrypted)');
            /*
             * connection_config structure examples:
             * Baselinker: {"api_token": "...", "inventory_id": "...", "warehouse_mappings": {...}}
             * Subiekt GT: {"dll_path": "...", "database_name": "...", "server": "...", "credentials": {...}}
             * Dynamics: {"tenant_id": "...", "client_id": "...", "client_secret": "...", "odata_url": "..."}
             */
            
            // Authentication Status
            $table->enum('auth_status', [
                'authenticated',   // Uwierzytelniony pomyślnie
                'expired',         // Token/sesja wygasła
                'failed',          // Błąd uwierzytelnienia
                'pending'          // Oczekuje uwierzytelnienia
            ])->default('pending')->comment('Status uwierzytelnienia');
            
            $table->timestamp('auth_expires_at')->nullable()->comment('Wygaśnięcie uwierzytelnienia');
            $table->timestamp('last_auth_at')->nullable()->comment('Ostatnie uwierzytelnienie');
            
            // Connection Health Monitoring
            $table->enum('connection_status', [
                'connected',       // Połączenie działa
                'disconnected',    // Brak połączenia
                'error',           // Błędy połączenia
                'maintenance',     // Tryb konserwacji
                'rate_limited'     // Limit API przekroczony
            ])->default('disconnected')->comment('Status połączenia');
            
            $table->timestamp('last_health_check')->nullable()->comment('Ostatnie sprawdzenie zdrowia');
            $table->decimal('last_response_time', 8, 3)->nullable()->comment('Czas odpowiedzi (ms)');
            $table->integer('consecutive_failures')->default(0)->comment('Liczba niepowodzeń z rzędu');
            $table->text('last_error_message')->nullable()->comment('Ostatni błąd połączenia');
            
            // API Rate Limiting
            $table->integer('rate_limit_per_minute')->nullable()->comment('Limit zapytań per minuta');
            $table->integer('current_api_usage')->default(0)->comment('Aktualne wykorzystanie API');
            $table->timestamp('rate_limit_reset_at')->nullable()->comment('Reset limitu API');
            
            // Synchronization Configuration
            $table->enum('sync_mode', [
                'bidirectional',   // Dwukierunkowa synchronizacja
                'push_only',       // Tylko PPM → ERP
                'pull_only',       // Tylko ERP → PPM
                'disabled'         // Synchronizacja wyłączona
            ])->default('bidirectional')->comment('Tryb synchronizacji');
            
            $table->json('sync_settings')->nullable()->comment('Ustawienia synchronizacji');
            $table->boolean('auto_sync_products')->default(true)->comment('Auto sync produktów');
            $table->boolean('auto_sync_stock')->default(true)->comment('Auto sync stanów');
            $table->boolean('auto_sync_prices')->default(true)->comment('Auto sync cen');
            $table->boolean('auto_sync_orders')->default(false)->comment('Auto sync zamówień');
            
            // Data Mapping Configuration
            $table->json('field_mappings')->nullable()->comment('Mapowanie pól między systemami');
            $table->json('transformation_rules')->nullable()->comment('Reguły transformacji danych');
            $table->json('validation_rules')->nullable()->comment('Reguły walidacji danych');
            
            // Sync Statistics
            $table->timestamp('last_sync_at')->nullable()->comment('Ostatnia synchronizacja');
            $table->timestamp('next_scheduled_sync')->nullable()->comment('Następna zaplanowana synchronizacja');
            $table->integer('sync_success_count')->default(0)->comment('Liczba udanych synchronizacji');
            $table->integer('sync_error_count')->default(0)->comment('Liczba błędów synchronizacji');
            $table->integer('records_synced_total')->default(0)->comment('Łączna liczba zsynchronizowanych rekordów');
            
            // Performance Metrics
            $table->decimal('avg_sync_time', 10, 3)->nullable()->comment('Średni czas synchronizacji (s)');
            $table->decimal('avg_response_time', 8, 3)->nullable()->comment('Średni czas odpowiedzi API');
            $table->integer('data_volume_mb')->default(0)->comment('Wolumen przesłanych danych (MB)');
            
            // Error Handling Configuration
            $table->integer('max_retry_attempts')->default(3)->comment('Maksymalna liczba prób ponawiania');
            $table->integer('retry_delay_seconds')->default(60)->comment('Opóźnienie między próbami');
            $table->boolean('auto_disable_on_errors')->default(false)->comment('Auto wyłączenie przy błędach');
            $table->integer('error_threshold')->default(10)->comment('Próg błędów do auto wyłączenia');
            
            // Webhook Configuration (for real-time sync)
            $table->string('webhook_url', 500)->nullable()->comment('URL webhooka dla real-time updates');
            $table->string('webhook_secret', 200)->nullable()->comment('Secret dla weryfikacji webhook');
            $table->boolean('webhook_enabled')->default(false)->comment('Czy webhook jest włączony');
            
            // Notification Settings
            $table->json('notification_settings')->nullable()->comment('Ustawienia powiadomień');
            $table->boolean('notify_on_errors')->default(true)->comment('Powiadomienia o błędach');
            $table->boolean('notify_on_sync_complete')->default(false)->comment('Powiadomienia po sync');
            $table->boolean('notify_on_auth_expire')->default(true)->comment('Powiadomienia o wygaśnięciu auth');
            
            // Audit and timestamps
            $table->timestamps();
            
            // Strategic indexes dla performance
            $table->index(['erp_type'], 'idx_erp_type');
            $table->index(['is_active'], 'idx_erp_active');
            $table->index(['connection_status'], 'idx_erp_connection_status');
            $table->index(['auth_status'], 'idx_erp_auth_status');
            $table->index(['priority'], 'idx_erp_priority');
            $table->index(['last_sync_at'], 'idx_erp_last_sync');
            $table->index(['next_scheduled_sync'], 'idx_erp_scheduled_sync');
            $table->index(['consecutive_failures'], 'idx_erp_failures');
            $table->index(['auth_expires_at'], 'idx_erp_auth_expires');
            
            // Unique constraint
            $table->unique(['erp_type', 'instance_name'], 'unique_erp_instance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_connections');
    }
};