<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Import Sessions Table
 *
 * ETAP_06 Import/Export - FAZA 1
 *
 * Tabela do sledzenia sesji importu produktow.
 * Grupuje wszystkie produkty zaimportowane w jednej akcji.
 *
 * @package Database\Migrations
 * @since 2025-12-08
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('session_name', 255);

            // Import method type
            $table->enum('import_method', [
                'paste_sku',        // Wklejenie listy SKU (jedna kolumna)
                'paste_sku_name',   // Wklejenie SKU + Nazwa (dwie kolumny)
                'csv',              // Import z pliku CSV
                'excel',            // Import z pliku Excel (XLSX)
                'erp',              // Import z ERP (future: Baselinker, Subiekt GT)
            ])->default('paste_sku');

            // Source file (dla CSV/Excel imports)
            $table->string('import_source_file', 512)->nullable();

            // Raw parsed data (JSON)
            $table->json('parsed_data')->nullable();

            // Statistics
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('products_created')->default(0);
            $table->unsignedInteger('products_published')->default(0);
            $table->unsignedInteger('products_failed')->default(0);
            $table->unsignedInteger('products_skipped')->default(0);

            // Status workflow
            $table->enum('status', [
                'parsing',      // Trwa parsowanie pliku/tekstu
                'ready',        // Gotowe do edycji/publikacji
                'publishing',   // Trwa publikacja produktow
                'completed',    // Wszystkie produkty opublikowane
                'failed',       // Blad krytyczny
                'cancelled',    // Anulowane przez uzytkownika
            ])->default('parsing');

            // Error tracking (JSON array)
            $table->json('error_log')->nullable();

            // User tracking
            $table->foreignId('imported_by')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('import_method');
            $table->index('imported_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_sessions');
    }
};
