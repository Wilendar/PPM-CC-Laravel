<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * FAZA B: Shop & ERP Management - Integration Logs Table
     * 
     * Tabela integration_logs przechowuje kompletne logi wszystkich operacji
     * integracji z systemami zewnętrznymi. Umożliwia:
     * - Detailed troubleshooting i debugging
     * - Compliance i audit requirements
     * - Performance analysis i optimization
     * - Security monitoring i threat detection
     * 
     * Enterprise Features:
     * - Structured logging z JSON payloads
     * - Automatic log rotation i archival
     * - Advanced filtering i searching
     * - Integration z monitoring systems
     */
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Log Classification
            $table->enum('log_level', [
                'debug',         // Szczegółowe informacje debug
                'info',          // Informacje ogólne
                'notice',        // Ważne zdarzenia
                'warning',       // Ostrzeżenia
                'error',         // Błędy nieblokujące
                'critical',      // Błędy krytyczne
                'alert',         // Wymagają natychmiastowej akcji
                'emergency'      // System niestabilny
            ])->comment('Poziom ważności logu');
            
            $table->string('log_type', 100)->comment('Typ operacji (api_call, sync, auth, webhook)');
            $table->string('category', 100)->comment('Kategoria (prestashop, baselinker, subiekt_gt, dynamics)');
            $table->string('subcategory', 100)->nullable()->comment('Podkategoria (products, orders, stock)');
            
            // Integration Context
            $table->enum('integration_type', [
                'prestashop',    // PrestaShop API
                'baselinker',    // Baselinker
                'subiekt_gt',    // Subiekt GT
                'dynamics',      // Microsoft Dynamics
                'internal',      // Internal operations
                'webhook'        // Webhook operations
            ])->comment('Typ integracji');
            
            $table->string('integration_id', 200)->nullable()->comment('ID instancji integracji');
            $table->string('external_system', 200)->nullable()->comment('Nazwa systemu zewnętrznego');
            
            // Operation Details
            $table->string('operation', 200)->comment('Nazwa operacji');
            $table->string('method', 20)->nullable()->comment('HTTP method (GET, POST, PUT, DELETE)');
            $table->string('endpoint', 500)->nullable()->comment('Endpoint URL lub ścieżka');
            $table->text('description')->nullable()->comment('Opis operacji');
            
            // Request/Response Data
            $table->json('request_data')->nullable()->comment('Dane żądania (headers, params, body)');
            $table->json('response_data')->nullable()->comment('Dane odpowiedzi');
            $table->integer('http_status')->nullable()->comment('Status HTTP odpowiedzi');
            $table->integer('response_time_ms')->nullable()->comment('Czas odpowiedzi w ms');
            $table->integer('response_size_bytes')->nullable()->comment('Rozmiar odpowiedzi w bajtach');
            
            // Error Information
            $table->string('error_code', 100)->nullable()->comment('Kod błędu');
            $table->text('error_message')->nullable()->comment('Komunikat błędu');
            $table->longText('error_details')->nullable()->comment('Szczegółowe informacje o błędzie');
            $table->text('stack_trace')->nullable()->comment('Stack trace dla błędów');
            
            // Business Context
            $table->string('entity_type', 100)->nullable()->comment('Typ encji (Product, Category, Order)');
            $table->string('entity_id', 200)->nullable()->comment('ID encji');
            $table->string('entity_reference', 300)->nullable()->comment('Referencja encji (SKU, name, code)');
            $table->integer('affected_records')->default(0)->comment('Liczba przetworzonych rekordów');
            
            // Job and User Context
            $table->string('sync_job_id', 100)->nullable()->comment('ID zadania synchronizacji');
            $table->foreignId('user_id')->nullable()->constrained()->comment('Użytkownik inicjujący operację');
            $table->string('session_id', 200)->nullable()->comment('ID sesji użytkownika');
            $table->ipAddress('ip_address')->nullable()->comment('Adres IP klienta');
            $table->string('user_agent', 500)->nullable()->comment('User agent klienta');
            
            // Security and Compliance
            $table->boolean('sensitive_data')->default(false)->comment('Czy log zawiera dane wrażliwe');
            $table->boolean('gdpr_relevant')->default(false)->comment('Czy dotyczy GDPR');
            $table->timestamp('retention_until')->nullable()->comment('Data usunięcia logu (retention policy)');
            
            // Performance Metrics
            $table->integer('memory_usage_mb')->nullable()->comment('Zużycie pamięci w MB');
            $table->decimal('cpu_time_ms', 10, 3)->nullable()->comment('Czas CPU w ms');
            $table->integer('database_queries')->nullable()->comment('Liczba zapytań DB');
            $table->decimal('database_time_ms', 10, 3)->nullable()->comment('Czas zapytań DB w ms');
            
            // Correlation and Tracing
            $table->string('correlation_id', 100)->nullable()->comment('ID korelacji dla powiązanych operacji');
            $table->string('trace_id', 100)->nullable()->comment('ID trace dla distributed tracing');
            $table->string('span_id', 100)->nullable()->comment('ID span w distributed tracing');
            $table->string('parent_span_id', 100)->nullable()->comment('ID parent span');
            
            // Tags and Metadata
            $table->json('tags')->nullable()->comment('Tagi dla kategoryzacji i filtrowania');
            $table->json('metadata')->nullable()->comment('Dodatkowe metadane');
            $table->json('custom_fields')->nullable()->comment('Custom fields dla specific integrations');
            
            // Alert and Notification
            $table->boolean('alert_triggered')->default(false)->comment('Czy wywołano alert');
            $table->string('alert_rule', 200)->nullable()->comment('Nazwa reguły alertu');
            $table->timestamp('alert_sent_at')->nullable()->comment('Kiedy wysłano alert');
            
            // Environment Information
            $table->string('environment', 50)->default('production')->comment('Środowisko (dev, staging, production)');
            $table->string('server_name', 200)->nullable()->comment('Nazwa serwera');
            $table->string('application_version', 50)->nullable()->comment('Wersja aplikacji');
            
            // Processing Status
            $table->enum('processing_status', [
                'raw',           // Surowy log, nieprocessowany
                'processed',     // Przetworzony przez agregatory
                'archived',      // Zarchiwizowany
                'deleted'        // Oznaczony do usunięcia
            ])->default('raw')->comment('Status przetwarzania logu');
            
            $table->timestamp('processed_at')->nullable()->comment('Kiedy przetworzono log');
            $table->timestamp('archived_at')->nullable()->comment('Kiedy zarchiwizowano');
            
            // Timestamps
            $table->timestamp('logged_at')->useCurrent()->comment('Kiedy wystąpiło zdarzenie');
            $table->timestamps();
            
            // Strategic indexes dla performance
            $table->index(['log_level'], 'idx_logs_level');
            $table->index(['log_type'], 'idx_logs_type');
            $table->index(['category'], 'idx_logs_category');
            $table->index(['integration_type'], 'idx_logs_integration');
            $table->index(['integration_id'], 'idx_logs_integration_id');
            $table->index(['operation'], 'idx_logs_operation');
            $table->index(['logged_at'], 'idx_logs_logged_at');
            $table->index(['user_id'], 'idx_logs_user');
            $table->index(['sync_job_id'], 'idx_logs_sync_job');
            $table->index(['entity_type', 'entity_id'], 'idx_logs_entity');
            $table->index(['correlation_id'], 'idx_logs_correlation');
            $table->index(['trace_id'], 'idx_logs_trace');
            $table->index(['processing_status'], 'idx_logs_processing_status');
            $table->index(['retention_until'], 'idx_logs_retention');
            
            // Compound indexes dla complex queries
            $table->index(['log_level', 'logged_at'], 'idx_logs_level_time');
            $table->index(['integration_type', 'log_level'], 'idx_logs_integration_level');
            $table->index(['category', 'operation', 'logged_at'], 'idx_logs_cat_op_time');
            $table->index(['error_code', 'logged_at'], 'idx_logs_error_time');
            $table->index(['alert_triggered', 'logged_at'], 'idx_logs_alert_time');
            
            // Full-text search index dla error messages i descriptions
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->fullText(['error_message', 'description'], 'fulltext_logs_error_desc');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};