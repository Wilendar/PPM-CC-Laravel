<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * FAZA C: Media & Relations - EAV System dla Atrybutów Produktów
     * 
     * Tabela product_attributes definiuje dostępne atrybuty:
     * - Model (multiselect) - Yamaha YZ250F 2023, Honda CRF450R, etc.
     * - Oryginał (text) - OEM part numbers
     * - Zamiennik (text) - aftermarket equivalents  
     * - Kolor (select) - Red, Blue, Black, etc.
     * - Rozmiar (text) - XS, S, M, L, XL dla odzieży
     * - Materiał (select) - Plastic, Metal, Carbon, etc.
     * 
     * EAV Performance Strategy:
     * - Strategic indexes na code (frequent lookups)
     * - attribute_type filtering dla form generation
     * - Validation rules w JSONB dla flexibility
     * - Options w JSONB dla select/multiselect types
     */
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Attribute definition - core identification
            $table->string('name', 200)->comment('Model, Oryginał, Zamiennik, Kolor, Rozmiar, Materiał');
            $table->string('code', 100)->unique()->comment('model, original, replacement, color, size, material');
            
            // Attribute type classification
            $table->enum('attribute_type', [
                'text',         // Free text input
                'number',       // Numeric input with validation
                'boolean',      // Yes/No checkbox
                'select',       // Single selection dropdown
                'multiselect',  // Multiple selection (Model compatibility)
                'date',         // Date picker
                'json'          // Complex structured data
            ])->comment('Typ pola dla formularzy');
            
            // Attribute behavior settings
            $table->boolean('is_required')->default(false)->comment('Czy wymagane przy dodawaniu produktu');
            $table->boolean('is_filterable')->default(true)->comment('Czy można filtrować w wyszukiwaniu');
            $table->boolean('is_variant_specific')->default(false)->comment('Czy może różnić się między wariantami');
            
            // Display and ordering
            $table->integer('sort_order')->default(0)->comment('Kolejność wyświetlania w formularzu');
            $table->string('display_group', 100)->default('general')->comment('Grupa wyświetlania: general, technical, compatibility');
            
            // Validation and options - JSONB dla flexibility
            $table->json('validation_rules')->nullable()->comment('Reguły walidacji: min, max, pattern, etc.');
            $table->json('options')->nullable()->comment('Opcje dla select/multiselect w formacie [{"value": "red", "label": "Czerwony"}]');
            
            // Default values and help
            $table->string('default_value', 500)->nullable()->comment('Domyślna wartość');
            $table->text('help_text')->nullable()->comment('Tekst pomocy dla użytkownika');
            
            // Unit and formatting dla numeric values
            $table->string('unit', 50)->nullable()->comment('Jednostka miary: kg, cm, L, etc.');
            $table->string('format_pattern', 100)->nullable()->comment('Pattern formatowania wyświetlania');
            
            // Status and audit
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Strategic indexes dla EAV performance
            // 1. Code lookup - most frequent access pattern
            $table->index(['code'], 'idx_attributes_code');
            
            // 2. Active attributes filtering
            $table->index(['is_active'], 'idx_attributes_active');
            
            // 3. Type-based form generation
            $table->index(['attribute_type', 'is_active'], 'idx_attributes_type_active');
            
            // 4. Filterable attributes dla search forms  
            $table->index(['is_filterable', 'is_active'], 'idx_attributes_filterable');
            
            // 5. Sort order dla form display
            $table->index(['sort_order'], 'idx_attributes_sort');
            
            // 6. Display group organization
            $table->index(['display_group', 'sort_order'], 'idx_attributes_group_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};