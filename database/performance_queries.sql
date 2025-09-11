-- =====================================================
-- PERFORMANCE VERIFICATION QUERIES - FAZA A
-- PPM-CC-Laravel Core Database Schema
-- Target: <100ms dla standardowych operacji
-- =====================================================

-- === CORE PERFORMANCE TESTS ===
-- Run these after migration to verify index effectiveness

-- TEST 1: Product SKU Lookup (TARGET: <5ms)
-- Najczęstsza operacja w systemie PIM
EXPLAIN ANALYZE 
SELECT id, sku, name, product_type, is_active, is_variant_master
FROM products 
WHERE sku = 'SAMPLE-SKU-001';

-- Expected: Using index on sku (unique), rows examined = 1

-- TEST 2: Active Products Listing (TARGET: <100ms for 50 products)  
EXPLAIN ANALYZE
SELECT id, sku, name, short_description, product_type, manufacturer
FROM products 
WHERE is_active = 1 
ORDER BY created_at DESC 
LIMIT 50;

-- Expected: Using index on is_active, filesort acceptable for LIMIT

-- TEST 3: Product Search by Manufacturer (TARGET: <50ms)
EXPLAIN ANALYZE
SELECT id, sku, name, manufacturer, product_type
FROM products 
WHERE manufacturer = 'Sample Manufacturer' 
  AND is_active = 1
ORDER BY name
LIMIT 20;

-- Expected: Using compound index on manufacturer + is_active

-- TEST 4: Category Tree Root Loading (TARGET: <20ms)
EXPLAIN ANALYZE
SELECT id, name, slug, sort_order, level
FROM categories 
WHERE parent_id IS NULL 
  AND is_active = 1 
ORDER BY sort_order;

-- Expected: Using index on parent_id, fast for root categories

-- TEST 5: Category Children Loading (TARGET: <30ms)
EXPLAIN ANALYZE
SELECT id, name, slug, sort_order, level, path
FROM categories 
WHERE parent_id = 1 
  AND is_active = 1 
ORDER BY sort_order;

-- Expected: Using compound index parent_id + is_active + sort_order

-- TEST 6: Category Path Query (TARGET: <50ms)
EXPLAIN ANALYZE
SELECT id, name, path, level
FROM categories 
WHERE path LIKE '/1/2/%' 
  AND is_active = 1;

-- Expected: Using index on path for LIKE queries

-- TEST 7: Product Variants Loading (TARGET: <20ms)
EXPLAIN ANALYZE
SELECT pv.id, pv.variant_sku, pv.variant_name, pv.sort_order,
       pv.inherit_prices, pv.inherit_stock, pv.inherit_attributes
FROM product_variants pv
WHERE pv.product_id = 1 
  AND pv.is_active = 1
ORDER BY pv.sort_order;

-- Expected: Using compound index product_id + is_active + sort_order

-- TEST 8: Master Products with Variants (TARGET: <100ms)
EXPLAIN ANALYZE
SELECT p.id, p.sku, p.name, COUNT(pv.id) as variants_count
FROM products p
LEFT JOIN product_variants pv ON p.id = pv.product_id AND pv.is_active = 1
WHERE p.is_variant_master = 1 
  AND p.is_active = 1
GROUP BY p.id, p.sku, p.name
ORDER BY p.name
LIMIT 25;

-- Expected: Using index on is_variant_master + is_active

-- TEST 9: Product Categories Lookup (TARGET: <30ms)
EXPLAIN ANALYZE
SELECT c.id, c.name, c.path, pc.is_primary, pc.sort_order
FROM categories c
JOIN product_categories pc ON c.id = pc.category_id
WHERE pc.product_id = 1
ORDER BY pc.is_primary DESC, pc.sort_order;

-- Expected: Using index on product_id in product_categories

-- TEST 10: Category Products Listing (TARGET: <100ms)  
EXPLAIN ANALYZE
SELECT p.id, p.sku, p.name, p.short_description, pc.sort_order
FROM products p
JOIN product_categories pc ON p.id = pc.product_id
WHERE pc.category_id = 1 
  AND p.is_active = 1
ORDER BY pc.sort_order, p.name
LIMIT 50;

-- Expected: Using compound index category_id + sort_order

-- === FULL-TEXT SEARCH TESTS (if supported) ===
-- TEST 11: Product Name Search (TARGET: <200ms)
EXPLAIN ANALYZE
SELECT id, sku, name, short_description, 
       MATCH(name, short_description) AGAINST('filtr powietrza' IN BOOLEAN MODE) as relevance
FROM products 
WHERE MATCH(name, short_description) AGAINST('filtr powietrza' IN BOOLEAN MODE)
  AND is_active = 1
ORDER BY relevance DESC
LIMIT 50;

-- Expected: Using full-text index ft_products_main

-- TEST 12: Code Search (SKU, supplier_code) (TARGET: <100ms)
EXPLAIN ANALYZE  
SELECT id, sku, name, supplier_code,
       MATCH(sku, supplier_code) AGAINST('ABC123' IN BOOLEAN MODE) as relevance
FROM products
WHERE MATCH(sku, supplier_code) AGAINST('ABC123' IN BOOLEAN MODE)
  AND is_active = 1
ORDER BY relevance DESC
LIMIT 20;

-- Expected: Using full-text index ft_products_codes

-- === COMPLEX BUSINESS QUERIES ===
-- TEST 13: Product with All Relations (TARGET: <150ms)
EXPLAIN ANALYZE
SELECT 
    p.id, p.sku, p.name, p.product_type,
    GROUP_CONCAT(DISTINCT c.name ORDER BY pc.is_primary DESC, c.name SEPARATOR '; ') as categories,
    COUNT(DISTINCT pv.id) as variants_count
FROM products p
LEFT JOIN product_categories pc ON p.id = pc.product_id
LEFT JOIN categories c ON pc.category_id = c.id AND c.is_active = 1
LEFT JOIN product_variants pv ON p.id = pv.product_id AND pv.is_active = 1
WHERE p.sku = 'SAMPLE-SKU-001'
  AND p.is_active = 1
GROUP BY p.id, p.sku, p.name, p.product_type;

-- Expected: Starting with products.sku index, then JOINs

-- TEST 14: Category with Product Count (TARGET: <100ms)
EXPLAIN ANALYZE
SELECT 
    c.id, c.name, c.path, c.level,
    COUNT(DISTINCT pc.product_id) as products_count,
    COUNT(DISTINCT child.id) as children_count
FROM categories c
LEFT JOIN product_categories pc ON c.id = pc.category_id
LEFT JOIN products p ON pc.product_id = p.id AND p.is_active = 1
LEFT JOIN categories child ON c.id = child.parent_id AND child.is_active = 1
WHERE c.is_active = 1
  AND c.level <= 2
GROUP BY c.id, c.name, c.path, c.level
ORDER BY c.path, c.sort_order;

-- Expected: Starting with categories level index

-- === PERFORMANCE BENCHMARKING ===
-- Run these with actual data to measure performance

-- Benchmark 1: Bulk SKU Lookup
SELECT benchmark_start_time = NOW(6);
SELECT * FROM products WHERE sku IN ('SKU1', 'SKU2', 'SKU3', 'SKU4', 'SKU5');
SELECT TIMESTAMPDIFF(MICROSECOND, @benchmark_start_time, NOW(6)) as microseconds;

-- Benchmark 2: Category Tree Full Load
SELECT benchmark_start_time = NOW(6);
SELECT * FROM categories WHERE is_active = 1 ORDER BY path, sort_order;
SELECT TIMESTAMPDIFF(MICROSECOND, @benchmark_start_time, NOW(6)) as microseconds;

-- === INDEX USAGE ANALYSIS ===
-- Check if indexes are being used effectively

-- Show index usage statistics
SELECT 
    s.TABLE_NAME,
    s.INDEX_NAME,
    s.COLUMN_NAME,
    s.CARDINALITY,
    t.TABLE_ROWS,
    ROUND((s.CARDINALITY / t.TABLE_ROWS) * 100, 2) as selectivity_percent
FROM information_schema.STATISTICS s
JOIN information_schema.TABLES t ON s.TABLE_SCHEMA = t.TABLE_SCHEMA AND s.TABLE_NAME = t.TABLE_NAME
WHERE s.TABLE_SCHEMA = DATABASE()
  AND s.TABLE_NAME IN ('products', 'categories', 'product_variants', 'product_categories')
  AND t.TABLE_ROWS > 0
ORDER BY s.TABLE_NAME, s.INDEX_NAME, s.SEQ_IN_INDEX;

-- Check for unused indexes (requires performance_schema)
-- SELECT object_schema, object_name, index_name 
-- FROM performance_schema.table_io_waits_summary_by_index_usage 
-- WHERE index_name IS NOT NULL 
--   AND count_star = 0 
--   AND object_schema = DATABASE();

-- === QUERY OPTIMIZATION RECOMMENDATIONS ===
-- Based on EXPLAIN ANALYZE results:

-- 1. If products.sku lookup > 5ms:
--    - Check if unique index exists: SHOW INDEX FROM products WHERE Column_name = 'sku'
--    - Verify data distribution: SELECT COUNT(DISTINCT sku), COUNT(*) FROM products

-- 2. If category tree queries > 50ms:
--    - Check path index: SHOW INDEX FROM categories WHERE Column_name = 'path'  
--    - Consider path length: SELECT AVG(CHAR_LENGTH(path)), MAX(CHAR_LENGTH(path)) FROM categories

-- 3. If product-category JOIN queries > 100ms:
--    - Verify compound indexes on product_categories
--    - Check foreign key indexes are present

-- 4. If full-text search > 200ms:
--    - Verify full-text indexes: SHOW INDEX FROM products WHERE Index_type = 'FULLTEXT'
--    - Check ft_min_word_len settings

-- === EXPECTED RESULTS SUMMARY ===
-- Products SKU lookup: <5ms (1 row examined)
-- Active products list: <100ms (using is_active index + filesort)
-- Category children: <30ms (using parent_id + compound indexes)
-- Product variants: <20ms (using product_id + compound indexes)  
-- Product-category joins: <100ms (using proper foreign key indexes)
-- Full-text search: <200ms (using full-text indexes)
-- Complex queries: <150ms (multiple index usage)

-- =====================================================