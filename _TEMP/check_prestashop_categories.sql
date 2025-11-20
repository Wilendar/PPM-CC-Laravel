SELECT
    p.id_product,
    p.reference AS sku,
    p.id_category_default,
    GROUP_CONCAT(DISTINCT cp.id_category ORDER BY cp.id_category) AS all_categories,
    GROUP_CONCAT(DISTINCT CONCAT(c.id_category, ':', cl.name, ' (parent:', c.id_parent, ')') ORDER BY c.id_category SEPARATOR ' | ') AS category_details
FROM ps_product p
LEFT JOIN ps_category_product cp ON p.id_product = cp.id_product
LEFT JOIN ps_category c ON cp.id_category = c.id_category
LEFT JOIN ps_category_lang cl ON c.id_category = cl.id_category AND cl.id_lang = 1
WHERE p.reference = 'PB-KAYO-E-KMB'
GROUP BY p.id_product;
