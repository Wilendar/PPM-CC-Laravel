-- =====================================================
-- MySQL/MariaDB Configuration dla PPM-CC-Laravel
-- Hostido.net.pl shared hosting optimization
-- Database: host379076_ppm
-- =====================================================

-- === CHARACTER SET & COLLATION OPTIMIZATION ===
-- UTF8MB4 dla emoji support w opisach produktów
ALTER DATABASE host379076_ppm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- === SESSION OPTIMIZATION dla PPM Performance ===
-- Optymalizacja dla aplikacji PIM z dużą ilością produktów

-- Query Cache Configuration (jeśli dostępne na shared hosting)
SET SESSION query_cache_type = ON;
SET SESSION query_cache_limit = 2097152; -- 2MB per query
SET SESSION query_cache_min_res_unit = 4096;

-- Connection & Buffer Optimization
SET SESSION sort_buffer_size = 2097152; -- 2MB dla sortowania kategorii
SET SESSION read_buffer_size = 131072; -- 128KB
SET SESSION join_buffer_size = 262144; -- 256KB dla JOIN operations
SET SESSION tmp_table_size = 33554432; -- 32MB dla temporary tables
SET SESSION max_heap_table_size = 33554432; -- 32MB

-- InnoDB Optimization
SET SESSION innodb_buffer_pool_size = 134217728; -- 128MB (jeśli możliwe)
SET SESSION innodb_flush_log_at_trx_commit = 2; -- Performance vs durability balance

-- === TIMEZONE & CHARSET SETTINGS ===
SET SESSION time_zone = '+01:00'; -- Poland timezone
SET SESSION character_set_client = utf8mb4;
SET SESSION character_set_connection = utf8mb4;
SET SESSION character_set_results = utf8mb4;
SET SESSION collation_connection = utf8mb4_unicode_ci;

-- === PERFORMANCE MONITORING QUERIES ===
-- Use these to monitor performance on production

-- 1. Check index usage on products table
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    SEQ_IN_INDEX,
    COLUMN_NAME,
    CARDINALITY
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'host379076_ppm' 
    AND TABLE_NAME = 'products'
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- 2. Monitor slow queries (jeśli dostępne)
-- SELECT * FROM mysql.slow_log WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 DAY);

-- 3. Check table sizes
SELECT 
    TABLE_NAME as 'Table',
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as 'Size (MB)',
    TABLE_ROWS as 'Rows'
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'host379076_ppm'
ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;

-- 4. Index efficiency check
SELECT 
    t.TABLE_NAME,
    t.TABLE_ROWS,
    s.INDEX_NAME,
    s.COLUMN_NAME,
    s.CARDINALITY,
    ROUND(s.CARDINALITY / t.TABLE_ROWS * 100, 2) as 'Selectivity %'
FROM INFORMATION_SCHEMA.TABLES t
JOIN INFORMATION_SCHEMA.STATISTICS s ON t.TABLE_NAME = s.TABLE_NAME
WHERE t.TABLE_SCHEMA = 'host379076_ppm'
    AND s.TABLE_SCHEMA = 'host379076_ppm'
    AND t.TABLE_ROWS > 0
ORDER BY t.TABLE_NAME, s.INDEX_NAME;

-- === PERFORMANCE TEST QUERIES ===
-- Run these after data seeding to verify performance

-- Test 1: Product by SKU lookup (target <5ms)
-- EXPLAIN SELECT * FROM products WHERE sku = 'TEST-SKU-001';

-- Test 2: Category tree loading (target <50ms)
-- EXPLAIN SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order;

-- Test 3: Product variants loading (target <20ms)
-- EXPLAIN SELECT * FROM product_variants WHERE product_id = 1 AND is_active = 1 ORDER BY sort_order;

-- Test 4: Category products with pagination (target <100ms)
-- EXPLAIN SELECT p.* FROM products p 
-- JOIN product_categories pc ON p.id = pc.product_id 
-- WHERE pc.category_id = 1 AND p.is_active = 1 
-- ORDER BY pc.sort_order LIMIT 50;

-- Test 5: Full-text search (target <200ms)
-- EXPLAIN SELECT * FROM products 
-- WHERE MATCH(name, short_description) AGAINST('filtr powietrza' IN BOOLEAN MODE)
-- AND is_active = 1 LIMIT 50;

-- === MAINTENANCE QUERIES ===
-- Run periodically for optimal performance

-- 1. Optimize all PPM tables
-- OPTIMIZE TABLE products, categories, product_variants, product_categories;

-- 2. Update table statistics
-- ANALYZE TABLE products, categories, product_variants, product_categories;

-- 3. Check table integrity
-- CHECK TABLE products, categories, product_variants, product_categories;

-- === BACKUP PREPARATION ===
-- Commands for backup before major migrations

-- 1. Structure backup
-- mysqldump -u host379076_ppm -p --no-data host379076_ppm > ppm_structure_backup.sql

-- 2. Data backup (only core tables)
-- mysqldump -u host379076_ppm -p --single-transaction host379076_ppm products categories product_variants product_categories > ppm_core_data_backup.sql

-- 3. Full backup
-- mysqldump -u host379076_ppm -p --single-transaction host379076_ppm > ppm_full_backup.sql

-- =====================================================
-- NOTES FOR PRODUCTION DEPLOYMENT:
-- 
-- 1. Run charset conversion after deployment
-- 2. Monitor query performance first week
-- 3. Adjust buffer sizes based on actual usage
-- 4. Set up slow query monitoring if available
-- 5. Schedule weekly OPTIMIZE TABLE operations
-- =====================================================