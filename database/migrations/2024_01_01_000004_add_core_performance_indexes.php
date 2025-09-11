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
     * Compound indexes dla częstych query patterns PPM-CC-Laravel
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
            // Compound indexes dla najczęstszych query patterns
            $table->index(['is_active', 'product_type', 'manufacturer']); // Filtrowanie produktów
            $table->index(['is_variant_master', 'is_active']); // Master produkty z wariantami
            $table->index(['supplier_code', 'is_active']); // Wyszukiwanie po kodzie dostawcy
            
            // Partial indexes dla optymalizacji (MySQL 8.0+, fallback dla MariaDB)
            // $table->index(['name'], 'active_products_name')->where('is_active', true);
        });

        // === CATEGORIES TABLE TREE OPTIMIZATION ===
        Schema::table('categories', function (Blueprint $table) {
            // Critical compound indexes dla tree operations
            $table->index(['parent_id', 'is_active', 'sort_order']); // Children loading
            $table->index(['path', 'is_active']); // Ancestor/descendant queries
            $table->index(['level', 'is_active', 'sort_order']); // Level-based listing
            
            // Path length optimization index
            $table->index([DB::raw('CHAR_LENGTH(path)')], 'categories_path_length_idx');
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

        // === QUERY CACHE OPTIMIZATION HINTS ===
        // Ustawienia dla shared hosting Hostido
        try {
            // Optymalizacja dla częstych queries
            DB::statement('/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */');
            DB::statement('/*!40103 SET TIME_ZONE="+00:00" */');
            
            // Query cache dla powtarzalnych operacji
            DB::statement('SET SESSION query_cache_type = ON');
            
        } catch (Exception $e) {
            // Ignore na shared hosting gdzie nie mamy kontroli nad ustawieniami
        }

        // === PERFORMANCE MONITORING PREPARATION ===
        // Create performance tracking views (optional, może być przydatne)
        try {
            DB::statement('
                CREATE OR REPLACE VIEW v_product_performance AS
                SELECT 
                    p.id, p.sku, p.name,
                    COUNT(pv.id) as variants_count,
                    p.is_variant_master,
                    p.is_active,
                    p.created_at
                FROM products p
                LEFT JOIN product_variants pv ON p.id = pv.product_id AND pv.is_active = 1
                WHERE p.is_active = 1
                GROUP BY p.id, p.sku, p.name, p.is_variant_master, p.is_active, p.created_at
            ');
        } catch (Exception $e) {
            // View creation optional
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
            $table->dropIndex('categories_path_length_idx');
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

        // Drop performance views
        try {
            DB::statement('DROP VIEW IF EXISTS v_product_performance');
        } catch (Exception $e) {
            // Ignore
        }
    }
};