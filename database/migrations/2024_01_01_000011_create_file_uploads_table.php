<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * FAZA C: Media & Relations - Universal File Upload System
     * 
     * Tabela file_uploads obsługuje dokumenty dla:
     * - Container (dokumenty kontenerów - ZIP, PDF, XLSX, XML)
     * - Order (dokumenty zamówień)  
     * - Product (dokumenty produktów - instrukcje, certyfikaty)
     * - User (dokumenty użytkowników)
     * 
     * Security considerations:
     * - access_level dla kontroli dostępu
     * - uploaded_by dla audit trail
     * - file_type dla filtrowania
     * - description dla metadata
     */
    public function up(): void
    {
        Schema::create('file_uploads', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Polymorphic relationships - uniwersalny system plików
            $table->string('uploadable_type', 100)->comment('Container, Order, Product, User');
            $table->unsignedBigInteger('uploadable_id')->comment('ID powiązanego obiektu');
            
            // File core information
            $table->string('file_name', 300)->comment('Nazwa pliku w storage');
            $table->string('original_name', 300)->comment('Oryginalna nazwa uploadowanego pliku');
            $table->string('file_path', 500)->comment('Ścieżka do pliku w storage');
            $table->unsignedBigInteger('file_size')->comment('Rozmiar w bajtach');
            
            // File type classification
            $table->string('mime_type', 100)->comment('pdf, xlsx, zip, xml, docx, etc.');
            $table->enum('file_type', ['document', 'spreadsheet', 'archive', 'certificate', 'manual', 'other'])
                  ->default('document')
                  ->comment('Typ dokumentu dla filtrowania');
            
            // Access control - krytyczne dla bezpieczeństwa
            $table->enum('access_level', ['admin', 'manager', 'all'])
                  ->default('all')
                  ->comment('Kto może zobaczyć plik');
                  
            // Audit trail
            $table->foreignId('uploaded_by')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment('Kto uploadował plik');
            
            // File metadata and description
            $table->text('description')->nullable()->comment('Opis dokumentu');
            $table->json('metadata')->nullable()->comment('Dodatkowe metadane (rozmiar, hash, etc.)');
            
            // Status and timestamps
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Strategic indexes dla performance i security
            // 1. Polymorphic relation index - primary access pattern
            $table->index(['uploadable_type', 'uploadable_id'], 'idx_uploads_polymorphic');
            
            // 2. File type filtering
            $table->index(['uploadable_type', 'uploadable_id', 'file_type'], 'idx_uploads_type');
            
            // 3. Access level security filtering  
            $table->index(['uploadable_type', 'uploadable_id', 'access_level'], 'idx_uploads_access');
            
            // 4. Active files filtering
            $table->index(['uploadable_type', 'uploadable_id', 'is_active'], 'idx_uploads_active');
            
            // 5. Uploaded by audit queries
            $table->index(['uploaded_by'], 'idx_uploads_user');
            
            // 6. Time-based queries dla cleanup
            $table->index(['created_at'], 'idx_uploads_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_uploads');
    }
};