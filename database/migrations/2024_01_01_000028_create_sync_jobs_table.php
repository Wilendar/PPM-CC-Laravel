<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * FAZA B: Shop & ERP Management - Sync Jobs Table
     * 
     * Tabela sync_jobs przechowuje historię i status wszystkich zadań synchronizacji
     * między PPM a systemami zewnętrznymi (PrestaShop, ERP). Umożliwia:
     * - Monitoring postępu synchronizacji w real-time
     * - Tracking błędów i retry logic
     * - Performance metrics i analytics
     * - Audit trail dla wszystkich operacji sync
     * 
     * Enterprise Features:
     * - Batch processing z progress tracking
     * - Detailed error logging z stack traces
     * - Performance profiling i bottleneck detection
     * - Queue integration z Laravel Jobs
     */
    public function up(): void
    {
        Schema::create('sync_jobs', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Job Identification
            $table->string('job_id', 100)->unique()->comment('Unique job identifier (UUID)');
            $table->string('job_type', 50)->comment('Typ zadania (product_sync, category_sync, etc.)');
            $table->string('job_name', 200)->comment('Nazwa zadania dla użytkownika');
            
            // Source and Target System
            $table->enum('source_type', [
                'ppm',           // PPM system
                'prestashop',    // PrestaShop store
                'baselinker',    // Baselinker
                'subiekt_gt',    // Subiekt GT
                'dynamics',      // Microsoft Dynamics
                'manual',        // Manual trigger
                'scheduled'      // Scheduled job
            ])->comment('Źródło synchronizacji');
            
            $table->string('source_id', 200)->nullable()->comment('ID źródła (shop_id, erp_id, user_id)');
            
            $table->enum('target_type', [
                'ppm',           // PPM system
                'prestashop',    // PrestaShop store
                'baselinker',    // Baselinker
                'subiekt_gt',    // Subiekt GT
                'dynamics',      // Microsoft Dynamics
                'multiple'       // Multiple targets
            ])->comment('Cel synchronizacji');
            
            $table->string('target_id', 200)->nullable()->comment('ID celu (shop_id, erp_id)');
            
            // Job Status and Progress
            $table->enum('status', [
                'pending',       // Oczekuje na wykonanie
                'running',       // W trakcie wykonania
                'paused',        // Zatrzymane (możliwe wznowienie)
                'completed',     // Ukończone pomyślnie
                'failed',        // Nieudane
                'cancelled',     // Anulowane przez użytkownika
                'timeout'        // Przekroczono limit czasu
            ])->default('pending')->comment('Status zadania');
            
            // Progress Tracking
            $table->integer('total_items')->default(0)->comment('Łączna liczba elementów do przetworzenia');
            $table->integer('processed_items')->default(0)->comment('Liczba przetworzonych elementów');
            $table->integer('successful_items')->default(0)->comment('Liczba pomyślnie przetworzonych');
            $table->integer('failed_items')->default(0)->comment('Liczba błędnych elementów');
            $table->decimal('progress_percentage', 5, 2)->default(0)->comment('Procent ukończenia');
            
            // Timing Information
            $table->timestamp('scheduled_at')->nullable()->comment('Kiedy zadanie było zaplanowane');
            $table->timestamp('started_at')->nullable()->comment('Kiedy zadanie zostało rozpoczęte');
            $table->timestamp('completed_at')->nullable()->comment('Kiedy zadanie zostało ukończone');
            $table->integer('duration_seconds')->nullable()->comment('Czas wykonania w sekundach');
            $table->integer('timeout_seconds')->default(3600)->comment('Limit czasu wykonania');
            
            // Job Configuration and Data
            $table->json('job_config')->nullable()->comment('Konfiguracja zadania');
            $table->json('job_data')->nullable()->comment('Dane wejściowe zadania');
            $table->json('filters')->nullable()->comment('Filtry dla synchronizacji');
            $table->json('mapping_rules')->nullable()->comment('Reguły mapowania danych');
            
            // Error Handling
            $table->text('error_message')->nullable()->comment('Główny komunikat błędu');
            $table->longText('error_details')->nullable()->comment('Szczegółowe informacje o błędzie');
            $table->text('stack_trace')->nullable()->comment('Stack trace błędu');
            $table->integer('retry_count')->default(0)->comment('Liczba prób ponawiania');
            $table->integer('max_retries')->default(3)->comment('Maksymalna liczba prób');
            $table->timestamp('next_retry_at')->nullable()->comment('Kiedy następna próba');
            
            // Performance Metrics
            $table->decimal('avg_item_processing_time', 8, 3)->nullable()->comment('Średni czas przetwarzania elementu (ms)');
            $table->integer('memory_peak_mb')->nullable()->comment('Szczytowe zużycie pamięci (MB)');
            $table->decimal('cpu_time_seconds', 10, 3)->nullable()->comment('Czas CPU (sekundy)');
            $table->integer('api_calls_made')->default(0)->comment('Liczba wykonanych wywołań API');
            $table->integer('database_queries')->default(0)->comment('Liczba zapytań do bazy');
            
            // Result Summary
            $table->json('result_summary')->nullable()->comment('Podsumowanie wyników');
            $table->json('affected_records')->nullable()->comment('Lista przetworzonych rekordów');
            $table->json('validation_errors')->nullable()->comment('Błędy walidacji');
            $table->json('warnings')->nullable()->comment('Ostrzeżenia');
            
            // User and Trigger Information
            $table->foreignId('user_id')->nullable()->constrained()->comment('Użytkownik który uruchomił zadanie');
            $table->enum('trigger_type', [
                'manual',        // Ręcznie uruchomione
                'scheduled',     // Zaplanowane automatycznie
                'webhook',       // Triggered przez webhook
                'event',         // Triggered przez event
                'api'            // Triggered przez API call
            ])->comment('Sposób uruchomienia zadania');
            
            // Queue Integration
            $table->string('queue_name', 100)->nullable()->comment('Nazwa kolejki Laravel');
            $table->string('queue_job_id', 200)->nullable()->comment('ID zadania w kolejce');
            $table->integer('queue_attempts')->default(0)->comment('Liczba prób w kolejce');
            
            // Notification and Alerts
            $table->boolean('notify_on_completion')->default(false)->comment('Powiadomienie po zakończeniu');
            $table->boolean('notify_on_failure')->default(true)->comment('Powiadomienie przy błędzie');
            $table->json('notification_channels')->nullable()->comment('Kanały powiadomień');
            $table->timestamp('last_notification_sent')->nullable()->comment('Ostatnie wysłane powiadomienie');
            
            // Dependencies and Relationships
            $table->string('parent_job_id', 100)->nullable()->comment('ID zadania nadrzędnego');
            $table->json('dependent_jobs')->nullable()->comment('Lista zadań zależnych');
            $table->string('batch_id', 100)->nullable()->comment('ID batch dla grupowych operacji');
            
            // Audit and timestamps
            $table->timestamps();
            
            // Strategic indexes dla performance i monitoring
            $table->index(['status'], 'idx_sync_jobs_status');
            $table->index(['job_type'], 'idx_sync_jobs_type');
            $table->index(['source_type', 'source_id'], 'idx_sync_jobs_source');
            $table->index(['target_type', 'target_id'], 'idx_sync_jobs_target');
            $table->index(['scheduled_at'], 'idx_sync_jobs_scheduled');
            $table->index(['started_at'], 'idx_sync_jobs_started');
            $table->index(['completed_at'], 'idx_sync_jobs_completed');
            $table->index(['user_id'], 'idx_sync_jobs_user');
            $table->index(['trigger_type'], 'idx_sync_jobs_trigger');
            $table->index(['batch_id'], 'idx_sync_jobs_batch');
            $table->index(['parent_job_id'], 'idx_sync_jobs_parent');
            $table->index(['next_retry_at'], 'idx_sync_jobs_retry');
            $table->index(['queue_name', 'queue_job_id'], 'idx_sync_jobs_queue');
            
            // Compound indexes dla complex queries
            $table->index(['status', 'scheduled_at'], 'idx_sync_jobs_status_scheduled');
            $table->index(['source_type', 'status'], 'idx_sync_jobs_source_status');
            $table->index(['job_type', 'status', 'created_at'], 'idx_sync_jobs_type_status_created');
            
            // Foreign key constraints - Remove self-referencing FK to avoid issues
            // Self-referencing FK can be problematic in this context, use application-level logic instead
            // $table->foreign('parent_job_id')->references('job_id')->on('sync_jobs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_jobs');
    }
};