<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('backup_jobs', function (Blueprint $table) {
            $table->id();
            
            // Podstawowe informacje o backupie
            $table->string('name');
            $table->enum('type', ['database', 'files', 'full'])->default('database');
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            
            // Informacje o pliku backupu
            $table->bigInteger('size_bytes')->nullable();
            $table->string('file_path')->nullable();
            
            // Czasy wykonania
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Błędy i konfiguracja
            $table->text('error_message')->nullable();
            $table->json('configuration')->nullable();
            
            // Audyting
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            // Indeksy
            $table->index('status');
            $table->index('type');
            $table->index(['status', 'created_at']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_jobs');
    }
};