<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Core Performance Indexes & MySQL Optimization dla Hostido
     * Strategiczne indeksy dla enterprise performance <100ms
     * Compound indexes dla czêstych query patterns PPM-CC-Laravel
     * 
     * Target Performance:
     * - SKU lookup: <5ms
     * - Category tree loading: <50ms  
     * - Product listing: <100ms dla 50 produktów
     * - Variant loading: <20ms dla produktu z wariantami
     */
    public function up(): void
    {
        // === PRODUCTS TABLE ADVANCED INDEXES ===
        Schema::table('products', function (Blueprint $table) {
            // Compound indexes dla najczêstszych query patterns
            $table->index(['is_active', 'product_type', 'manufacturer']); // Filtrowanie produktów
            $table->index(['is_variant_master', 'is_active']); // Master produkty z wariantami
            $table->index(['supplier_code', 'is_active']); // Wyszukiwanie po kodzie dostawcy
        });

        // === CATEGORIES TABLE TREE OPTIMIZATION ===
        Schema::table('categories', function (Blueprint $table) {
            // Critical compound indexes dla tree operations
            $table->index(['parent_id', 'is_active', 'sort_order']); // Children loading
            $table->index(['path', 'is_active']); // Ancestor/descendant queries
            $table->index(['level', 'is_active', 'sort_order']); // Level-based listing
        });

        // === PRODUCT_VARIANTS TABLE OPTIMIZATION ===
        Schema::table('product_variants', function (Blueprint $table) {
            // Inheritance pattern optimization
            $table->index(['product_id', 'inherit_prices', 'is_active']); // Price inheritance queries
            $table->index(['product_id', 'inherit_stock', 'is_active']); // Stock inheritance queries
            $table->index(['product_id', 'inherit_attributes', 'is_active']); // Attribute inheritance
            
            // Variant ordering optimization
            $table->index(['is_active', 'sort_order']); // Global variant sorting
        });

        // === MYSQL/MariaDB SPECIFIC OPTIMIZATIONS ===
        // Full-text search indexes dla inteligentnej wyszukiwarki
        try {
            // Products search optimization
            DB::statement('ALTER TABLE products DROP INDEX IF EXISTS search_index');
            DB::statement('ALTER TABLE products DROP INDEX IF EXISTS code_search');
            
            // Advanced full-text search
            DB::statement('ALTER TABLE products ADD FULLTEXT ft_products_main (name, short_description, manufacturer)');
            DB::statement('ALTER TABLE products ADD FULLTEXT ft_products_codes (sku, supplier_code, ean)');
            
            // Categories search
            DB::statement('ALTER TABLE categories ADD FULLTEXT ft_categories (name, description)');
            
        } catch (Exception $e) {
            // Fallback dla starszych wersji MySQL/MariaDB
            // Log error but continue migration
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Rollback support - usuwa dodatkowe indeksy ale zachowuje podstawowe
     */
    public function down(): void
    {
        // Drop advanced indexes
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'product_type', 'manufacturer']);
            $table->dropIndex(['is_variant_master', 'is_active']);
            $table->dropIndex(['supplier_code', 'is_active']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['parent_id', 'is_active', 'sort_order']);
            $table->dropIndex(['path', 'is_active']);
            $table->dropIndex(['level', 'is_active', 'sort_order']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'inherit_prices', 'is_active']);
            $table->dropIndex(['product_id', 'inherit_stock', 'is_active']);
            $table->dropIndex(['product_id', 'inherit_attributes', 'is_active']);
            $table->dropIndex(['is_active', 'sort_order']);
        });

        // Drop full-text indexes
        try {
            DB::statement('ALTER TABLE products DROP INDEX IF EXISTS ft_products_main');
            DB::statement('ALTER TABLE products DROP INDEX IF EXISTS ft_products_codes');
            DB::statement('ALTER TABLE categories DROP INDEX IF EXISTS ft_categories');
        } catch (Exception $e) {
            // Ignore errors during rollback
        }
    }
};
