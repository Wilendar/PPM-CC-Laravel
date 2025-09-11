<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * FAZA C: Media & Relations - Polymorphic Media System
     * 
     * Tabela media obsługuje zdjęcia dla:
     * - Product (główny produkt)
     * - ProductVariant (wariant produktu)
     * 
     * Performance considerations:
     * - Compound indexes dla polymorphic relations
     * - Optimized dla częste queries przez mediable_type/id
     * - Support dla multi-store PrestaShop mapping
     * - Prepared dla CDN integration
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Polymorphic relationships - kluczowe dla performance
            $table->string('mediable_type', 100)->comment('Product, ProductVariant');
            $table->unsignedBigInteger('mediable_id')->comment('ID powiązanego obiektu');
            
            // File information - core data
            $table->string('file_name', 300)->comment('Nazwa pliku w storage');
            $table->string('original_name', 300)->nullable()->comment('Oryginalna nazwa uploadowanego pliku');
            $table->string('file_path', 500)->comment('Ścieżka do pliku w storage');
            $table->unsignedInteger('file_size')->comment('Rozmiar w bajtach');
            
            // Image metadata - critical dla display
            $table->string('mime_type', 100)->comment('jpg, jpeg, png, webp, gif');
            $table->unsignedInteger('width')->nullable()->comment('Szerokość obrazu w pikselach');
            $table->unsignedInteger('height')->nullable()->comment('Wysokość obrazu w pikselach');
            $table->string('alt_text', 300)->nullable()->comment('Tekst alternatywny dla SEO/accessibility');
            
            // Ordering and primary image logic
            $table->integer('sort_order')->default(0)->comment('Kolejność wyświetlania');
            $table->boolean('is_primary')->default(false)->comment('Główne zdjęcie produktu/wariantu');
            
            // PrestaShop integration mapping - JSONB dla flexibility
            $table->json('prestashop_mapping')->nullable()->comment('Mapowanie per sklep PrestaShop');
            
            // Synchronization status dla external integrations
            $table->enum('sync_status', ['pending', 'synced', 'error', 'ignored'])
                  ->default('pending')
                  ->comment('Status synchronizacji z systemami zewnętrznymi');
            
            // Status and audit
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Strategic indexes dla performance
            // 1. Polymorphic relation index - MOST CRITICAL
            $table->index(['mediable_type', 'mediable_id'], 'idx_media_polymorphic');
            
            // 2. Primary image selection index
            $table->index(['mediable_type', 'mediable_id', 'is_primary'], 'idx_media_primary');
            
            // 3. Sort order dla gallery display
            $table->index(['mediable_type', 'mediable_id', 'sort_order'], 'idx_media_sort');
            
            // 4. Active media filtering
            $table->index(['mediable_type', 'mediable_id', 'is_active'], 'idx_media_active');
            
            // 5. Sync status dla integration monitoring
            $table->index(['sync_status'], 'idx_media_sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};