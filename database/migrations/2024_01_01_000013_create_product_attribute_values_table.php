<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * FAZA C: Media & Relations - EAV Values Storage
     * 
     * Tabela product_attribute_values przechowuje rzeczywiste wartości atrybutów:
     * - Wspiera różne typy danych (text, number, boolean, date, json)
     * - Inheritance logic między Product a ProductVariant
     * - Optimized queries dla automotive compatibility (Model/Oryginał/Zamiennik)
     * - Strategic indexes dla EAV performance challenges
     * 
     * EAV Performance Critical Points:
     * - Compound indexes na (product_id, attribute_id)
     * - Specialized indexes na value_text(255) dla text searches
     * - GIN indexes na value_json dla complex queries
     * - Inheritance logic optimization
     */
    public function up(): void
    {
        Schema::create('product_attribute_values', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Foreign key relationships
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade')
                  ->comment('Produkt główny');
                  
            $table->foreignId('product_variant_id')
                  ->nullable()
                  ->constrained('product_variants')
                  ->onDelete('cascade')
                  ->comment('Wariant produktu (NULL = wartość dla produktu głównego)');
                  
            $table->foreignId('attribute_id')
                  ->constrained('product_attributes')
                  ->onDelete('cascade')
                  ->comment('Definicja atrybutu');
            
            // Value storage - różne typy danych
            $table->text('value_text')->nullable()->comment('Wartość tekstowa (Model: Yamaha YZ250F, Oryginał: OEM123, etc.)');
            $table->decimal('value_number', 15, 6)->nullable()->comment('Wartość numeryczna (waga, wymiary, etc.)');
            $table->boolean('value_boolean')->nullable()->comment('Wartość tak/nie');
            $table->date('value_date')->nullable()->comment('Wartość daty');
            $table->json('value_json')->nullable()->comment('Złożone dane (multiselect, structured data)');
            
            // Inheritance and override logic
            $table->boolean('is_inherited')->default(false)->comment('Czy dziedziczy z produktu głównego');
            $table->boolean('is_override')->default(false)->comment('Czy nadpisuje wartość z głównego produktu');
            
            // Validation and status
            $table->boolean('is_valid')->default(true)->comment('Czy wartość przeszła walidację');
            $table->text('validation_error')->nullable()->comment('Błędy walidacji');
            
            // Audit and timestamps
            $table->timestamps();
            
            // Business logic constraints
            // Ensure unique combination per product/variant/attribute
            $table->unique(['product_id', 'product_variant_id', 'attribute_id'], 'unique_product_variant_attribute');
            
            // Strategic indexes dla EAV performance optimization
            // 1. Primary access pattern - get attributes for product
            $table->index(['product_id', 'attribute_id'], 'idx_values_product_attribute');
            
            // 2. Variant-specific attributes
            $table->index(['product_variant_id', 'attribute_id'], 'idx_values_variant_attribute');
            
            // 3. Text value searches (limited to 255 chars dla index efficiency)
            $table->index([DB::raw('value_text(255)')], 'idx_values_text_search');
            
            // 4. Numeric value filtering and sorting
            $table->index(['value_number'], 'idx_values_number');
            
            // 5. Boolean filtering (availability, compatibility, etc.)
            $table->index(['value_boolean'], 'idx_values_boolean');
            
            // 6. Date range queries
            $table->index(['value_date'], 'idx_values_date');
            
            // 7. Inheritance queries dla effective value resolution
            $table->index(['product_id', 'is_inherited'], 'idx_values_inheritance');
            
            // 8. Validation status filtering
            $table->index(['is_valid'], 'idx_values_valid');
            
            // 9. JSON/GIN index dla complex queries (prepared for MariaDB 10.3+)
            // Note: JSON indexes support varies by MySQL/MariaDB version
            if (DB::connection()->getDriverName() === 'mysql') {
                // MariaDB/MySQL JSON index support check would go here
                // For now, we prepare the structure
                $table->index(['value_json'], 'idx_values_json');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attribute_values');
    }
};