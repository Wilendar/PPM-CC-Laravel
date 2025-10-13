<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_04 Panel Administracyjny - Sekcja 2.2.2.2: Import Management
     *
     * Tabela import_jobs przechowuje informacje o zadaniach importu danych
     * z PrestaShop stores z obsługą scheduling, validation, rollback.
     */
    public function up(): void
    {
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();

            // Job identifiers
            $table->uuid('job_id')->unique()->comment('UUID zadania');
            $table->string('job_type')->default('prestashop_import')->comment('Typ zadania');
            $table->string('job_name')->comment('Nazwa zadania');

            // Source and target
            $table->string('source_type')->default('prestashop')->comment('Źródło danych');
            $table->string('target_type')->default('ppm')->comment('Cel danych');
            $table->unsignedBigInteger('source_id')->comment('ID sklepu PrestaShop');
            $table->foreign('source_id')->references('id')->on('prestashop_shops')->onDelete('cascade');

            // Trigger and user
            $table->string('trigger_type')->default('manual')->comment('Sposób wywołania');
            $table->unsignedBigInteger('user_id')->nullable()->comment('ID użytkownika');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Timing
            $table->timestamp('scheduled_at')->nullable()->comment('Data zaplanowania');
            $table->timestamp('started_at')->nullable()->comment('Data rozpoczęcia');
            $table->timestamp('completed_at')->nullable()->comment('Data zakończenia');

            // Configuration and data
            $table->json('job_config')->nullable()->comment('Konfiguracja importu');
            $table->json('rollback_data')->nullable()->comment('Dane dla rollback');

            // Status and progress
            $table->string('status')->default('pending')->index()->comment('Status zadania');
            $table->unsignedTinyInteger('progress')->nullable()->comment('Postęp w procentach (0-100)');

            // Error handling
            $table->text('error_message')->nullable()->comment('Komunikat błędu');

            // Statistics
            $table->unsignedInteger('records_total')->nullable()->comment('Całkowita liczba rekordów');
            $table->unsignedInteger('records_processed')->nullable()->comment('Liczba przetworzonych');
            $table->unsignedInteger('records_failed')->nullable()->comment('Liczba błędnych');

            $table->timestamps();

            // Indexes for performance
            $table->index('job_type');
            $table->index('source_id');
            $table->index('user_id');
            $table->index('scheduled_at');
            $table->index('created_at');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};
