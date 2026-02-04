<?php
/**
 * Subiekt GT REST API Wrapper - Repository
 *
 * Database access layer for Subiekt GT SQL Server database.
 * Provides read operations for products, stock, prices, warehouses, etc.
 *
 * IMPORTANT: This class is READ-ONLY by design for SQL direct mode.
 * Write operations require Sfera API integration.
 *
 * Table Reference (Subiekt GT):
 * - tw__Towar: Products (tw_Id, tw_Symbol, tw_Nazwa, tw_DataMod)
 * - tw_Cena: Product prices (tc_TowId, tc_CenaNetto, tc_CenaBrutto, tc_RodzCenyId)
 * - tw_Stan: Stock levels (st_TowId, st_Stan, st_MagId)
 * - sl_Magazyn: Warehouses (mag_Id, mag_Symbol, mag_Nazwa)
 * - sl_RodzajCeny: Price types (rc_Id, rc_Symbol, rc_Nazwa)
 *
 * @package SubiektGT_REST_Wrapper
 * @version 1.0.0
 */

class SubiektRepository
{
    private ?PDO $pdo = null;
    private array $config;

    /**
     * Constructor
     *
     * @param array $config Database configuration
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get PDO connection (lazy initialization)
     *
     * @return PDO
     * @throws PDOException
     */
    protected function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $dsn = sprintf(
                'sqlsrv:Server=%s,%s;Database=%s;TrustServerCertificate=%s;ConnectionPooling=0',
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['trust_certificate'] ? '1' : '0'
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_TIMEOUT => $this->config['connection_timeout'] ?? 10,
                PDO::SQLSRV_ATTR_QUERY_TIMEOUT => $this->config['query_timeout'] ?? 30,
            ];

            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $options
            );
        }

        return $this->pdo;
    }

    /**
     * Close connection
     */
    public function closeConnection(): void
    {
        $this->pdo = null;
    }

    // ==========================================
    // HEALTH & CONNECTION TESTS
    // ==========================================

    /**
     * Test database connection and return server info
     *
     * @return array Health check result
     */
    public function healthCheck(): array
    {
        $startTime = microtime(true);

        try {
            $stmt = $this->getConnection()->query('SELECT @@VERSION as version, GETDATE() as server_time');
            $result = $stmt->fetch();

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => 'ok',
                'database' => $this->config['database'],
                'server_version' => $result->version ?? 'Unknown',
                'server_time' => $result->server_time ?? null,
                'response_time_ms' => $responseTime,
            ];
        } catch (PDOException $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTime,
            ];
        }
    }

    /**
     * Get database statistics
     *
     * @return array Statistics
     */
    public function getStats(): array
    {
        try {
            $conn = $this->getConnection();

            return [
                'total_products' => $this->countProducts(),
                'active_products' => $this->countProducts(true),
                'warehouses' => $this->countWarehouses(),
                'price_types' => $this->countPriceTypes(),
            ];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // ==========================================
    // PRODUCT QUERIES
    // ==========================================

    /**
     * Get products with pagination
     *
     * @param int $page Page number (1-based)
     * @param int $pageSize Items per page
     * @param int|null $priceTypeId Price type ID for pricing
     * @param int|null $warehouseId Warehouse ID for stock
     * @param array $filters Optional filters (sku, name, modified_since, active_only)
     * @return array Products with pagination info
     */
    public function getProducts(
        int $page = 1,
        int $pageSize = 100,
        ?int $priceTypeId = null,
        ?int $warehouseId = null,
        array $filters = []
    ): array {
        $offset = ($page - 1) * $pageSize;

        // Base query
        $sql = "
            SELECT
                t.tw_Id as id,
                t.tw_Symbol as sku,
                t.tw_Nazwa as name,
                t.tw_NazwaFiskalna as fiscal_name,
                t.tw_Opis as description,
                t.tw_KodKreskowy as ean,
                t.tw_WagaNetto as weight_net,
                t.tw_WagaBrutto as weight_gross,
                t.tw_JednMiaryId as unit_id,
                t.tw_Aktywny as is_active,
                t.tw_DataMod as updated_at,
                t.tw_DataDod as created_at,
                t.tw_ProducentId as manufacturer_id,
                t.tw_GrupaId as category_id,
                t.tw_StawkaVatSprzId as vat_rate_id,
                c.tc_CenaNetto as price_net,
                c.tc_CenaBrutto as price_gross,
                ISNULL(s.st_Stan, 0) as stock_quantity
            FROM tw__Towar t
            LEFT JOIN tw_Cena c ON t.tw_Id = c.tc_TowId AND c.tc_RodzCenyId = :price_type_id
            LEFT JOIN tw_Stan s ON t.tw_Id = s.st_TowId AND s.st_MagId = :warehouse_id
            WHERE 1=1
        ";

        $params = [
            'price_type_id' => $priceTypeId ?? 1,
            'warehouse_id' => $warehouseId ?? 1,
        ];

        // Apply filters
        if (!empty($filters['active_only'])) {
            $sql .= " AND t.tw_Aktywny = 1";
        }

        if (!empty($filters['sku'])) {
            $sql .= " AND t.tw_Symbol LIKE :sku";
            $params['sku'] = '%' . $filters['sku'] . '%';
        }

        if (!empty($filters['name'])) {
            $sql .= " AND t.tw_Nazwa LIKE :name";
            $params['name'] = '%' . $filters['name'] . '%';
        }

        if (!empty($filters['modified_since'])) {
            $sql .= " AND t.tw_DataMod > :modified_since";
            $params['modified_since'] = $filters['modified_since'];
        }

        if (!empty($filters['ean'])) {
            $sql .= " AND t.tw_KodKreskowy = :ean";
            $params['ean'] = $filters['ean'];
        }

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM tw__Towar t WHERE 1=1";
        if (!empty($filters['active_only'])) {
            $countSql .= " AND t.tw_Aktywny = 1";
        }
        if (!empty($filters['modified_since'])) {
            $countSql .= " AND t.tw_DataMod > :modified_since";
        }

        $countParams = [];
        if (!empty($filters['modified_since'])) {
            $countParams['modified_since'] = $filters['modified_since'];
        }

        $countStmt = $this->getConnection()->prepare($countSql);
        $countStmt->execute($countParams);
        $totalCount = (int) $countStmt->fetch()->total;

        // Add pagination
        $sql .= " ORDER BY t.tw_Id OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";
        $params['offset'] = $offset;
        $params['limit'] = $pageSize;

        $stmt = $this->getConnection()->prepare($sql);

        // Bind parameters with proper types
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();
        $products = $stmt->fetchAll();

        return [
            'data' => $products,
            'pagination' => [
                'current_page' => $page,
                'page_size' => $pageSize,
                'total_items' => $totalCount,
                'total_pages' => (int) ceil($totalCount / $pageSize),
                'has_next' => ($page * $pageSize) < $totalCount,
                'has_previous' => $page > 1,
            ],
        ];
    }

    /**
     * Get single product by ID
     *
     * @param int $productId Subiekt GT product ID
     * @param int|null $priceTypeId Price type ID
     * @param int|null $warehouseId Warehouse ID
     * @return object|null Product or null if not found
     */
    public function getProductById(int $productId, ?int $priceTypeId = null, ?int $warehouseId = null): ?object
    {
        $sql = "
            SELECT
                t.tw_Id as id,
                t.tw_Symbol as sku,
                t.tw_Nazwa as name,
                t.tw_NazwaFiskalna as fiscal_name,
                t.tw_Opis as description,
                t.tw_OpisEtykiety as label_description,
                t.tw_KodKreskowy as ean,
                t.tw_WagaNetto as weight_net,
                t.tw_WagaBrutto as weight_gross,
                t.tw_JednMiaryId as unit_id,
                t.tw_Aktywny as is_active,
                t.tw_DataMod as updated_at,
                t.tw_DataDod as created_at,
                t.tw_ProducentId as manufacturer_id,
                t.tw_GrupaId as category_id,
                t.tw_StawkaVatSprzId as vat_rate_id,
                c.tc_CenaNetto as price_net,
                c.tc_CenaBrutto as price_gross,
                ISNULL(s.st_Stan, 0) as stock_quantity
            FROM tw__Towar t
            LEFT JOIN tw_Cena c ON t.tw_Id = c.tc_TowId AND c.tc_RodzCenyId = :price_type_id
            LEFT JOIN tw_Stan s ON t.tw_Id = s.st_TowId AND s.st_MagId = :warehouse_id
            WHERE t.tw_Id = :product_id
        ";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            'product_id' => $productId,
            'price_type_id' => $priceTypeId ?? 1,
            'warehouse_id' => $warehouseId ?? 1,
        ]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Get single product by SKU
     *
     * @param string $sku Product SKU
     * @param int|null $priceTypeId Price type ID
     * @param int|null $warehouseId Warehouse ID
     * @return object|null Product or null if not found
     */
    public function getProductBySku(string $sku, ?int $priceTypeId = null, ?int $warehouseId = null): ?object
    {
        $sql = "
            SELECT
                t.tw_Id as id,
                t.tw_Symbol as sku,
                t.tw_Nazwa as name,
                t.tw_NazwaFiskalna as fiscal_name,
                t.tw_Opis as description,
                t.tw_OpisEtykiety as label_description,
                t.tw_KodKreskowy as ean,
                t.tw_WagaNetto as weight_net,
                t.tw_WagaBrutto as weight_gross,
                t.tw_JednMiaryId as unit_id,
                t.tw_Aktywny as is_active,
                t.tw_DataMod as updated_at,
                t.tw_DataDod as created_at,
                t.tw_ProducentId as manufacturer_id,
                t.tw_GrupaId as category_id,
                t.tw_StawkaVatSprzId as vat_rate_id,
                c.tc_CenaNetto as price_net,
                c.tc_CenaBrutto as price_gross,
                ISNULL(s.st_Stan, 0) as stock_quantity
            FROM tw__Towar t
            LEFT JOIN tw_Cena c ON t.tw_Id = c.tc_TowId AND c.tc_RodzCenyId = :price_type_id
            LEFT JOIN tw_Stan s ON t.tw_Id = s.st_TowId AND s.st_MagId = :warehouse_id
            WHERE t.tw_Symbol = :sku
        ";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            'sku' => $sku,
            'price_type_id' => $priceTypeId ?? 1,
            'warehouse_id' => $warehouseId ?? 1,
        ]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Count products
     *
     * @param bool $activeOnly Count only active products
     * @return int Count
     */
    public function countProducts(bool $activeOnly = false): int
    {
        $sql = "SELECT COUNT(*) as total FROM tw__Towar";
        if ($activeOnly) {
            $sql .= " WHERE tw_Aktywny = 1";
        }

        $stmt = $this->getConnection()->query($sql);
        return (int) $stmt->fetch()->total;
    }

    // ==========================================
    // STOCK QUERIES
    // ==========================================

    /**
     * Get stock levels for all products (with pagination)
     *
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @param int|null $warehouseId Filter by warehouse
     * @return array Stock data with pagination
     */
    public function getStock(int $page = 1, int $pageSize = 100, ?int $warehouseId = null): array
    {
        $offset = ($page - 1) * $pageSize;

        $sql = "
            SELECT
                t.tw_Id as product_id,
                t.tw_Symbol as sku,
                t.tw_Nazwa as product_name,
                s.st_MagId as warehouse_id,
                m.mag_Symbol as warehouse_code,
                m.mag_Nazwa as warehouse_name,
                s.st_Stan as quantity,
                ISNULL(s.st_Rezerwacja, 0) as reserved,
                (s.st_Stan - ISNULL(s.st_Rezerwacja, 0)) as available
            FROM tw_Stan s
            JOIN tw__Towar t ON s.st_TowId = t.tw_Id
            JOIN sl_Magazyn m ON s.st_MagId = m.mag_Id
            WHERE t.tw_Aktywny = 1 AND m.mag_Aktywny = 1
        ";

        $params = [];

        if ($warehouseId !== null) {
            $sql .= " AND s.st_MagId = :warehouse_id";
            $params['warehouse_id'] = $warehouseId;
        }

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM tw_Stan s JOIN tw__Towar t ON s.st_TowId = t.tw_Id JOIN sl_Magazyn m ON s.st_MagId = m.mag_Id WHERE t.tw_Aktywny = 1 AND m.mag_Aktywny = 1";
        if ($warehouseId !== null) {
            $countSql .= " AND s.st_MagId = :warehouse_id";
        }

        $countStmt = $this->getConnection()->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = (int) $countStmt->fetch()->total;

        // Add pagination
        $sql .= " ORDER BY t.tw_Symbol OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue('limit', $pageSize, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'pagination' => [
                'current_page' => $page,
                'page_size' => $pageSize,
                'total_items' => $totalCount,
                'total_pages' => (int) ceil($totalCount / $pageSize),
            ],
        ];
    }

    /**
     * Get stock for single product across all warehouses
     *
     * @param int $productId Product ID
     * @return array Stock per warehouse
     */
    public function getProductStock(int $productId): array
    {
        $sql = "
            SELECT
                s.st_TowId as product_id,
                s.st_MagId as warehouse_id,
                m.mag_Symbol as warehouse_code,
                m.mag_Nazwa as warehouse_name,
                s.st_Stan as quantity,
                ISNULL(s.st_Rezerwacja, 0) as reserved,
                (s.st_Stan - ISNULL(s.st_Rezerwacja, 0)) as available
            FROM tw_Stan s
            JOIN sl_Magazyn m ON s.st_MagId = m.mag_Id
            WHERE s.st_TowId = :product_id AND m.mag_Aktywny = 1
        ";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    }

    /**
     * Get stock for product by SKU
     *
     * @param string $sku Product SKU
     * @return array Stock per warehouse
     */
    public function getProductStockBySku(string $sku): array
    {
        $sql = "
            SELECT
                t.tw_Id as product_id,
                t.tw_Symbol as sku,
                s.st_MagId as warehouse_id,
                m.mag_Symbol as warehouse_code,
                m.mag_Nazwa as warehouse_name,
                s.st_Stan as quantity,
                ISNULL(s.st_Rezerwacja, 0) as reserved,
                (s.st_Stan - ISNULL(s.st_Rezerwacja, 0)) as available
            FROM tw__Towar t
            JOIN tw_Stan s ON t.tw_Id = s.st_TowId
            JOIN sl_Magazyn m ON s.st_MagId = m.mag_Id
            WHERE t.tw_Symbol = :sku AND m.mag_Aktywny = 1
        ";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['sku' => $sku]);

        return $stmt->fetchAll();
    }

    // ==========================================
    // PRICE QUERIES
    // ==========================================

    /**
     * Get all prices for single product
     *
     * @param int $productId Product ID
     * @return array Prices for all price types
     */
    public function getProductPrices(int $productId): array
    {
        $sql = "
            SELECT
                c.tc_TowId as product_id,
                c.tc_RodzCenyId as price_type_id,
                r.rc_Symbol as price_type_code,
                r.rc_Nazwa as price_type_name,
                c.tc_CenaNetto as price_net,
                c.tc_CenaBrutto as price_gross
            FROM tw_Cena c
            JOIN sl_RodzajCeny r ON c.tc_RodzCenyId = r.rc_Id
            WHERE c.tc_TowId = :product_id AND r.rc_Aktywny = 1
            ORDER BY r.rc_Id
        ";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    }

    /**
     * Get prices for product by SKU
     *
     * @param string $sku Product SKU
     * @return array Prices for all price types
     */
    public function getProductPricesBySku(string $sku): array
    {
        $sql = "
            SELECT
                t.tw_Id as product_id,
                t.tw_Symbol as sku,
                c.tc_RodzCenyId as price_type_id,
                r.rc_Symbol as price_type_code,
                r.rc_Nazwa as price_type_name,
                c.tc_CenaNetto as price_net,
                c.tc_CenaBrutto as price_gross
            FROM tw__Towar t
            JOIN tw_Cena c ON t.tw_Id = c.tc_TowId
            JOIN sl_RodzajCeny r ON c.tc_RodzCenyId = r.rc_Id
            WHERE t.tw_Symbol = :sku AND r.rc_Aktywny = 1
            ORDER BY r.rc_Id
        ";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(['sku' => $sku]);

        return $stmt->fetchAll();
    }

    // ==========================================
    // WAREHOUSE QUERIES
    // ==========================================

    /**
     * Get all active warehouses
     *
     * @return array Warehouses list
     */
    public function getWarehouses(): array
    {
        $sql = "
            SELECT
                mag_Id as id,
                mag_Symbol as code,
                mag_Nazwa as name,
                mag_Aktywny as is_active
            FROM sl_Magazyn
            WHERE mag_Aktywny = 1
            ORDER BY mag_Id
        ";

        $stmt = $this->getConnection()->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Count active warehouses
     *
     * @return int Count
     */
    public function countWarehouses(): int
    {
        $stmt = $this->getConnection()->query("SELECT COUNT(*) as total FROM sl_Magazyn WHERE mag_Aktywny = 1");
        return (int) $stmt->fetch()->total;
    }

    // ==========================================
    // PRICE TYPE QUERIES
    // ==========================================

    /**
     * Get all active price types
     *
     * @return array Price types list
     */
    public function getPriceTypes(): array
    {
        $sql = "
            SELECT
                rc_Id as id,
                rc_Symbol as code,
                rc_Nazwa as name,
                rc_Aktywny as is_active
            FROM sl_RodzajCeny
            WHERE rc_Aktywny = 1
            ORDER BY rc_Id
        ";

        $stmt = $this->getConnection()->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Count active price types
     *
     * @return int Count
     */
    public function countPriceTypes(): int
    {
        $stmt = $this->getConnection()->query("SELECT COUNT(*) as total FROM sl_RodzajCeny WHERE rc_Aktywny = 1");
        return (int) $stmt->fetch()->total;
    }

    // ==========================================
    // ADDITIONAL REFERENCE DATA
    // ==========================================

    /**
     * Get VAT rates
     *
     * @return array VAT rates list
     */
    public function getVatRates(): array
    {
        $sql = "
            SELECT
                sv_Id as id,
                sv_Symbol as code,
                sv_Nazwa as name,
                sv_Stawka as rate,
                sv_Aktywny as is_active
            FROM sl_StawkaVat
            WHERE sv_Aktywny = 1
            ORDER BY sv_Id
        ";

        $stmt = $this->getConnection()->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get manufacturers/producers
     *
     * @return array Manufacturers list
     */
    public function getManufacturers(): array
    {
        $sql = "
            SELECT
                pr_Id as id,
                pr_Nazwa as name,
                pr_Aktywny as is_active
            FROM sl_Producent
            WHERE pr_Aktywny = 1
            ORDER BY pr_Nazwa
        ";

        $stmt = $this->getConnection()->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get product groups/categories
     *
     * @return array Product groups list
     */
    public function getProductGroups(): array
    {
        $sql = "
            SELECT
                gt_Id as id,
                gt_Symbol as code,
                gt_Nazwa as name,
                gt_NadrzednaId as parent_id,
                gt_Aktywny as is_active
            FROM sl_GrupaTow
            WHERE gt_Aktywny = 1
            ORDER BY gt_Id
        ";

        $stmt = $this->getConnection()->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get measurement units
     *
     * @return array Units list
     */
    public function getUnits(): array
    {
        $sql = "
            SELECT
                jm_Id as id,
                jm_Symbol as code,
                jm_Nazwa as name
            FROM sl_JednMiary
            ORDER BY jm_Id
        ";

        $stmt = $this->getConnection()->query($sql);
        return $stmt->fetchAll();
    }
}
