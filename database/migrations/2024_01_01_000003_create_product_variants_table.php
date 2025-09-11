<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Product Variants Table - Warianty produktów
     * Obsługuje: Hierarchię master-variant, selective inheritance,
     * dedykowane SKU, independent pricing/stock/attributes
     * 
     * Business Logic:
     * - inherit_prices=true -> dziedziczy ceny z master produktu
     * - inherit_stock=false -> ma własne stany magazynowe  
     * - inherit_attributes=true -> dziedziczy atrybuty + może mieć własne
     * 
     * Performance: Foreign key indexes, unique SKU constraint
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            // === PRIMARY IDENTITY ===
            $table->id(); // SERIAL PRIMARY KEY
            $table->unsignedBigInteger('product_id'); // FK do products
            $table->string('variant_sku', 100)->unique(); // Unikalny SKU wariantu
            
            // === VARIANT IDENTIFICATION ===
            $table->string('variant_name', 200); // Nazwa wariantu (np. "Czerwony L", "Wersja Pro")
            $table->string('ean', 20)->nullable(); // Dedykowany EAN dla wariantu
            $table->integer('sort_order')->default(0); // Kolejność wyświetlania wariantów
            
            // === INHERITANCE CONTROL ===
            // Te flagi kontrolują czy wariant dziedziczy właściwości z master produktu
            $table->boolean('inherit_prices')->default(true); // Czy dziedziczy ceny
            $table->boolean('inherit_stock')->default(false); // Czy dziedziczy stany (zazwyczaj NIE)
            $table->boolean('inherit_attributes')->default(true); // Czy dziedziczy atrybuty
            
            // === STATUS & VISIBILITY ===
            $table->boolean('is_active')->default(true)->index(); // Status wariantu
            
            // === AUDIT & TIMESTAMPS ===
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at for soft delete support
            
            // === FOREIGN KEY CONSTRAINTS ===
            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade') // Usuwa warianty gdy master produkt usunięty
                  ->onUpdate('cascade');
            
            // === PERFORMANCE INDEXES ===
            $table->index(['product_id']); // Częste lookup wariantów dla produktu
            $table->index(['variant_sku']); // SKU lookup (już unique ale dodatkowy index)
            $table->index(['is_active']); // Filtrowanie aktywnych wariantów
            $table->index(['product_id', 'sort_order']); // Compound dla sortowania wariantów
            $table->index(['product_id', 'is_active']); // Compound dla aktywnych wariantów produktu
            $table->index(['created_at']); // Chronological sorting
        });

        // === BUSINESS LOGIC CONSTRAINTS ===
        // Zapewnienie integralności danych biznesowych
        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT chk_variant_name_not_empty CHECK (CHAR_LENGTH(TRIM(variant_name)) > 0)');
        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT chk_variant_sku_format CHECK (variant_sku REGEXP "^[A-Z0-9\-_]+$")');
    }

    /**
     * Reverse the migrations.
     * 
     * Rollback support - usuwa tabelę product_variants z wszystkimi constraintami
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};