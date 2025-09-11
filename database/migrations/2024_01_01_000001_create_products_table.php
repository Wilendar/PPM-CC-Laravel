<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Core Products Table - centrum systemu PIM PPM-CC-Laravel
     * Obsługuje: SKU jako primary identifier, opisy wielojęzyczne, 
     * metadane techniczne, SEO, soft deletes, warianty produktów
     * 
     * Performance: Indeksy na SKU, slug, supplier_code dla <5ms lookup
     * Scalability: Zaprojektowane dla 100K+ produktów
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            // === PRIMARY IDENTITY ===
            $table->id(); // SERIAL PRIMARY KEY
            $table->string('sku', 100)->unique()->index(); // Główny identyfikator produktu
            $table->string('slug', 500)->unique()->nullable(); // URL-friendly slug
            
            // === BASIC PRODUCT INFO ===
            $table->string('name', 500); // Nazwa produktu
            $table->text('short_description')->nullable(); // Max 800 znaków w walidacji
            $table->longText('long_description')->nullable(); // Max 21844 znaków w walidacji
            
            // === PRODUCT CLASSIFICATION ===
            $table->enum('product_type', ['vehicle', 'spare_part', 'clothing', 'other'])->default('spare_part');
            $table->string('manufacturer', 200)->nullable(); // Producent
            $table->string('supplier_code', 100)->nullable()->index(); // Kod dostawcy - często używany w searches
            
            // === PHYSICAL PROPERTIES ===
            $table->decimal('weight', 8, 3)->nullable(); // kg
            $table->decimal('height', 8, 2)->nullable(); // cm
            $table->decimal('width', 8, 2)->nullable(); // cm  
            $table->decimal('length', 8, 2)->nullable(); // cm
            $table->string('ean', 20)->nullable(); // EAN barcode
            $table->decimal('tax_rate', 5, 2)->default(23.00); // VAT % stawka
            
            // === PRODUCT STATUS & VARIANTS ===
            $table->boolean('is_active')->default(true)->index(); // Performance index dla filtrowania
            $table->boolean('is_variant_master')->default(false); // Czy ma warianty
            $table->integer('sort_order')->default(0);
            
            // === SEO METADATA ===
            $table->string('meta_title', 300)->nullable();
            $table->string('meta_description', 300)->nullable();
            
            // === AUDIT & TIMESTAMPS ===
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at for soft delete support
            
            // === PERFORMANCE INDEXES ===
            // SKU już ma unique index wyżej
            $table->index(['is_active', 'product_type']); // Compound index dla filtrowania
            $table->index(['manufacturer']); // Producent często filtrowany
            $table->index(['created_at']); // Sortowanie chronologiczne
            $table->index(['deleted_at']); // Soft delete queries optimization
        });
        
        // === FULL-TEXT SEARCH PREPARATION ===
        // MySQL/MariaDB full-text index dla intelligent search
        DB::statement('ALTER TABLE products ADD FULLTEXT search_index (name, short_description)');
        DB::statement('ALTER TABLE products ADD FULLTEXT code_search (sku, supplier_code)');
    }

    /**
     * Reverse the migrations.
     * 
     * Rollback support - usuwa tabelę products i wszystkie indeksy
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};