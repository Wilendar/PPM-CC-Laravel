<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Pending Products Table
 *
 * ETAP_06 Import/Export - FAZA 1
 *
 * Tabela dla produktow w stanie DRAFT - importowanych ale jeszcze nie opublikowanych.
 * Produkty z tej tabeli NIE pojawiaja sie w glownej liscie produktow
 * dopoki nie zostana opublikowane (przeniesione do tabeli products).
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
        Schema::create('pending_products', function (Blueprint $table) {
            $table->id();

            // ===========================================
            // BASIC PRODUCT DATA
            // ===========================================

            // SKU - moze byc NULL dla partial imports (uzupelniany pozniej)
            // UNIQUE w obrebie sesji importu (kombinacja sku + import_session_id)
            $table->string('sku', 128)->nullable();

            $table->string('name', 255)->nullable();
            $table->string('slug', 255)->nullable();

            // Product type (FK to product_types)
            $table->foreignId('product_type_id')
                  ->nullable()
                  ->constrained('product_types')
                  ->nullOnDelete();

            // Manufacturer & supplier info
            $table->string('manufacturer', 128)->nullable();
            $table->string('supplier_code', 128)->nullable();
            $table->string('ean', 64)->nullable();

            // ===========================================
            // CATEGORIES (JSON)
            // ===========================================
            // Array of category IDs [3, 7, 12] - poziomy L3-L7
            $table->json('category_ids')->nullable();

            // ===========================================
            // MEDIA (JSON)
            // ===========================================
            // Array of temporary upload paths ['tmp/uuid1.jpg', 'tmp/uuid2.jpg']
            $table->json('temp_media_paths')->nullable();

            // Primary image index (0-based, within temp_media_paths)
            $table->unsignedTinyInteger('primary_media_index')->default(0);

            // ===========================================
            // SHOPS (JSON)
            // ===========================================
            // Array of shop IDs [1, 3, 5] - gdzie produkt ma byc opublikowany
            $table->json('shop_ids')->nullable();

            // Shop-specific categories (optional override)
            // Format: {"1": [3, 7], "3": [12, 15]} - shop_id => [category_ids]
            $table->json('shop_categories')->nullable();

            // ===========================================
            // PHYSICAL ATTRIBUTES
            // ===========================================
            $table->decimal('weight', 10, 3)->nullable(); // kg
            $table->decimal('height', 10, 2)->nullable(); // cm
            $table->decimal('width', 10, 2)->nullable();  // cm
            $table->decimal('length', 10, 2)->nullable(); // cm
            $table->decimal('tax_rate', 5, 2)->default(23.00); // VAT %

            // ===========================================
            // DESCRIPTIONS
            // ===========================================
            $table->text('short_description')->nullable();
            $table->longText('long_description')->nullable();

            // SEO
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable(); // Array of keywords

            // ===========================================
            // VARIANTS (JSON)
            // ===========================================
            // Warianty przechowywane jako JSON dopoki nie zostanie opublikowany
            // Format: {"variants": [{"sku": "SKU-RED", "name": "Red", "attributes": {"color": "red"}}]}
            $table->json('variant_data')->nullable();

            // ===========================================
            // COMPATIBILITY / FEATURES (JSON)
            // ===========================================
            // Dopasowania do pojazdow (przechowywane jako JSON)
            // Format: {"vehicle_ids": [1, 5, 12], "models": ["Model X", "Model Y"]}
            $table->json('compatibility_data')->nullable();

            // Technical features (cechy techniczne)
            // Format: {"Moc silnika": "150 KM", "Rok produkcji": "2020"}
            $table->json('feature_data')->nullable();

            // ===========================================
            // PRICING (OPTIONAL - defaults applied on publish)
            // ===========================================
            $table->decimal('base_price', 12, 2)->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();

            // ===========================================
            // COMPLETION TRACKING
            // ===========================================

            // Per-field completion status
            // Format: {"sku": true, "name": true, "category_ids": false, "shop_ids": true, ...}
            $table->json('completion_status')->nullable();

            // Overall completion percentage (0-100)
            $table->unsignedTinyInteger('completion_percentage')->default(0);

            // Flag indicating all required fields are filled
            $table->boolean('is_ready_for_publish')->default(false);

            // ===========================================
            // IMPORT SESSION TRACKING
            // ===========================================
            $table->foreignId('import_session_id')
                  ->nullable()
                  ->constrained('import_sessions')
                  ->nullOnDelete();

            $table->foreignId('imported_by')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->timestamp('imported_at')->nullable();

            // ===========================================
            // PUBLICATION TRACKING
            // ===========================================
            $table->timestamp('published_at')->nullable();

            // FK to products table (after publication)
            $table->foreignId('published_as_product_id')
                  ->nullable()
                  ->constrained('products')
                  ->nullOnDelete();

            // ===========================================
            // STANDARD FIELDS
            // ===========================================
            $table->timestamps();
            $table->softDeletes();

            // ===========================================
            // INDEXES
            // ===========================================
            $table->index('sku');
            $table->index('name');
            $table->index('product_type_id');
            $table->index('is_ready_for_publish');
            $table->index('completion_percentage');
            $table->index('import_session_id');
            $table->index('imported_by');
            $table->index('published_at');
            $table->index('created_at');

            // Composite unique: SKU musi byc unikalne w obrebie sesji importu
            // (ten sam SKU moze byc w roznych sesjach, np. rozne importy)
            $table->unique(['sku', 'import_session_id'], 'unique_sku_per_session');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_products');
    }
};
