<?php

namespace App\Services\ERP\SubiektGT;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * SubiektQueryBuilder
 *
 * ETAP: Subiekt GT ERP Integration
 *
 * Helper class for building SQL queries to Subiekt GT database.
 * Handles product retrieval, stock levels, prices, warehouses and price types.
 *
 * IMPORTANT: This class is READ-ONLY by design.
 * Writing to Subiekt GT requires Sfera API or REST wrapper.
 *
 * Table Reference (Subiekt GT):
 * - tw__Towar: Products table (tw_Id, tw_Symbol, tw_Nazwa, tw_DataMod)
 * - tw_Cena: Product prices (tc_TowId, tc_CenaNetto, tc_CenaBrutto, tc_RodzCenyId)
 * - tw_Stan: Stock levels (st_TowId, st_Stan, st_MagId)
 * - sl_Magazyn: Warehouses (mag_Id, mag_Symbol, mag_Nazwa)
 * - sl_RodzajCeny: Price types (rc_Id, rc_Symbol, rc_Nazwa)
 *
 * @package App\Services\ERP\SubiektGT
 * @version 1.0
 */
class SubiektQueryBuilder
{
    protected string $connection;

    /**
     * Constructor
     *
     * @param string $connection Database connection name (from config/database.php)
     */
    public function __construct(string $connection = 'subiekt')
    {
        $this->connection = $connection;
    }

    /**
     * Get database connection instance
     */
    protected function db()
    {
        return DB::connection($this->connection);
    }

    // ==========================================
    // PRODUCT QUERIES
    // ==========================================

    /**
     * Get single product by ID
     *
     * @param int $productId Subiekt GT product ID (tw_Id)
     * @param int|null $priceTypeId Price type ID (rc_Id), defaults to 1
     * @param int|null $warehouseId Warehouse ID (mag_Id), defaults to 1
     * @return object|null Product data or null if not found
     */
    public function getProductById(int $productId, ?int $priceTypeId = 1, ?int $warehouseId = 1): ?object
    {
        return $this->db()
            ->table('tw__Towar as t')
            ->leftJoin('tw_Cena as c', function ($join) use ($priceTypeId) {
                $join->on('t.tw_Id', '=', 'c.tc_TowId')
                    ->where('c.tc_RodzCenyId', '=', $priceTypeId);
            })
            ->leftJoin('tw_Stan as s', function ($join) use ($warehouseId) {
                $join->on('t.tw_Id', '=', 's.st_TowId')
                    ->where('s.st_MagId', '=', $warehouseId);
            })
            ->select([
                't.tw_Id as id',
                't.tw_Symbol as sku',
                't.tw_Nazwa as name',
                't.tw_NazwaFiskalna as fiscal_name',
                't.tw_Opis as description',
                't.tw_OpisEtykiety as label_description',
                't.tw_KodKreskowy as ean',
                't.tw_WagaNetto as weight_net',
                't.tw_WagaBrutto as weight_gross',
                't.tw_JednMiaryId as unit_id',
                't.tw_Aktywny as is_active',
                't.tw_DataMod as updated_at',
                't.tw_DataDod as created_at',
                't.tw_ProducentId as manufacturer_id',
                't.tw_GrupaId as category_id',
                't.tw_StawkaVatSprzId as vat_rate_id',
                'c.tc_CenaNetto as price_net',
                'c.tc_CenaBrutto as price_gross',
                DB::raw('ISNULL(s.st_Stan, 0) as stock_quantity'),
            ])
            ->where('t.tw_Id', $productId)
            ->first();
    }

    /**
     * Get single product by SKU (Symbol)
     *
     * @param string $sku Product SKU (tw_Symbol)
     * @param int|null $priceTypeId Price type ID
     * @param int|null $warehouseId Warehouse ID
     * @return object|null Product data or null if not found
     */
    public function getProductBySKU(string $sku, ?int $priceTypeId = 1, ?int $warehouseId = 1): ?object
    {
        return $this->db()
            ->table('tw__Towar as t')
            ->leftJoin('tw_Cena as c', function ($join) use ($priceTypeId) {
                $join->on('t.tw_Id', '=', 'c.tc_TowId')
                    ->where('c.tc_RodzCenyId', '=', $priceTypeId);
            })
            ->leftJoin('tw_Stan as s', function ($join) use ($warehouseId) {
                $join->on('t.tw_Id', '=', 's.st_TowId')
                    ->where('s.st_MagId', '=', $warehouseId);
            })
            ->select([
                't.tw_Id as id',
                't.tw_Symbol as sku',
                't.tw_Nazwa as name',
                't.tw_NazwaFiskalna as fiscal_name',
                't.tw_Opis as description',
                't.tw_OpisEtykiety as label_description',
                't.tw_KodKreskowy as ean',
                't.tw_WagaNetto as weight_net',
                't.tw_WagaBrutto as weight_gross',
                't.tw_JednMiaryId as unit_id',
                't.tw_Aktywny as is_active',
                't.tw_DataMod as updated_at',
                't.tw_DataDod as created_at',
                't.tw_ProducentId as manufacturer_id',
                't.tw_GrupaId as category_id',
                't.tw_StawkaVatSprzId as vat_rate_id',
                'c.tc_CenaNetto as price_net',
                'c.tc_CenaBrutto as price_gross',
                DB::raw('ISNULL(s.st_Stan, 0) as stock_quantity'),
            ])
            ->where('t.tw_Symbol', $sku)
            ->where('t.tw_Aktywny', 1)
            ->first();
    }

    /**
     * Get all active products with pagination
     *
     * @param int|null $priceTypeId Price type ID for pricing
     * @param int|null $warehouseId Warehouse ID for stock
     * @param int $limit Products per page
     * @param int $offset Starting offset
     * @return Collection Collection of products
     */
    public function getAllProducts(
        ?int $priceTypeId = 1,
        ?int $warehouseId = 1,
        int $limit = 100,
        int $offset = 0
    ): Collection {
        return $this->db()
            ->table('tw__Towar as t')
            ->leftJoin('tw_Cena as c', function ($join) use ($priceTypeId) {
                $join->on('t.tw_Id', '=', 'c.tc_TowId')
                    ->where('c.tc_RodzCenyId', '=', $priceTypeId);
            })
            ->leftJoin('tw_Stan as s', function ($join) use ($warehouseId) {
                $join->on('t.tw_Id', '=', 's.st_TowId')
                    ->where('s.st_MagId', '=', $warehouseId);
            })
            ->select([
                't.tw_Id as id',
                't.tw_Symbol as sku',
                't.tw_Nazwa as name',
                't.tw_Opis as description',
                't.tw_KodKreskowy as ean',
                't.tw_WagaBrutto as weight',
                't.tw_Aktywny as is_active',
                't.tw_DataMod as updated_at',
                't.tw_ProducentId as manufacturer_id',
                't.tw_GrupaId as category_id',
                't.tw_StawkaVatSprzId as vat_rate_id',
                'c.tc_CenaNetto as price_net',
                'c.tc_CenaBrutto as price_gross',
                DB::raw('ISNULL(s.st_Stan, 0) as stock_quantity'),
            ])
            ->where('t.tw_Aktywny', 1)
            ->orderBy('t.tw_Id')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    /**
     * Get products modified since timestamp (for incremental sync)
     *
     * @param string $since Timestamp in Y-m-d H:i:s format
     * @param int|null $priceTypeId Price type ID
     * @param int|null $warehouseId Warehouse ID
     * @param int $limit Max products to return
     * @return Collection Modified products
     */
    public function getModifiedProducts(
        string $since,
        ?int $priceTypeId = 1,
        ?int $warehouseId = 1,
        int $limit = 1000
    ): Collection {
        return $this->db()
            ->table('tw__Towar as t')
            ->leftJoin('tw_Cena as c', function ($join) use ($priceTypeId) {
                $join->on('t.tw_Id', '=', 'c.tc_TowId')
                    ->where('c.tc_RodzCenyId', '=', $priceTypeId);
            })
            ->leftJoin('tw_Stan as s', function ($join) use ($warehouseId) {
                $join->on('t.tw_Id', '=', 's.st_TowId')
                    ->where('s.st_MagId', '=', $warehouseId);
            })
            ->select([
                't.tw_Id as id',
                't.tw_Symbol as sku',
                't.tw_Nazwa as name',
                't.tw_Opis as description',
                't.tw_KodKreskowy as ean',
                't.tw_WagaBrutto as weight',
                't.tw_Aktywny as is_active',
                't.tw_DataMod as updated_at',
                't.tw_ProducentId as manufacturer_id',
                't.tw_GrupaId as category_id',
                't.tw_StawkaVatSprzId as vat_rate_id',
                'c.tc_CenaNetto as price_net',
                'c.tc_CenaBrutto as price_gross',
                DB::raw('ISNULL(s.st_Stan, 0) as stock_quantity'),
            ])
            ->where('t.tw_DataMod', '>', $since)
            ->where('t.tw_Aktywny', 1)
            ->orderBy('t.tw_DataMod')
            ->take($limit)
            ->get();
    }

    /**
     * Get count of modified products since timestamp
     *
     * @param string $since Timestamp in Y-m-d H:i:s format
     * @return int Number of modified products
     */
    public function getModifiedProductsCount(string $since): int
    {
        return $this->db()
            ->table('tw__Towar')
            ->where('tw_DataMod', '>', $since)
            ->where('tw_Aktywny', 1)
            ->count();
    }

    /**
     * Get total count of active products
     *
     * @return int Total active products
     */
    public function getTotalProductsCount(): int
    {
        return $this->db()
            ->table('tw__Towar')
            ->where('tw_Aktywny', 1)
            ->count();
    }

    // ==========================================
    // STOCK QUERIES
    // ==========================================

    /**
     * Get stock for single product across all warehouses
     *
     * @param int $productId Subiekt GT product ID
     * @return Collection Stock per warehouse
     */
    public function getProductStock(int $productId): Collection
    {
        return $this->db()
            ->table('tw_Stan as s')
            ->join('sl_Magazyn as m', 's.st_MagId', '=', 'm.mag_Id')
            ->select([
                's.st_TowId as product_id',
                's.st_MagId as warehouse_id',
                'm.mag_Symbol as warehouse_code',
                'm.mag_Nazwa as warehouse_name',
                's.st_Stan as quantity',
                's.st_Rezerwacja as reserved',
                DB::raw('(s.st_Stan - ISNULL(s.st_Rezerwacja, 0)) as available'),
            ])
            ->where('s.st_TowId', $productId)
            ->where('m.mag_Aktywny', 1)
            ->get();
    }

    /**
     * Get stock for single product in specific warehouse
     *
     * @param int $productId Subiekt GT product ID
     * @param int $warehouseId Warehouse ID
     * @return object|null Stock data or null
     */
    public function getProductStockInWarehouse(int $productId, int $warehouseId): ?object
    {
        return $this->db()
            ->table('tw_Stan as s')
            ->join('sl_Magazyn as m', 's.st_MagId', '=', 'm.mag_Id')
            ->select([
                's.st_TowId as product_id',
                's.st_MagId as warehouse_id',
                'm.mag_Symbol as warehouse_code',
                'm.mag_Nazwa as warehouse_name',
                's.st_Stan as quantity',
                's.st_Rezerwacja as reserved',
                DB::raw('(s.st_Stan - ISNULL(s.st_Rezerwacja, 0)) as available'),
            ])
            ->where('s.st_TowId', $productId)
            ->where('s.st_MagId', $warehouseId)
            ->first();
    }

    /**
     * Get stock levels for multiple products (batch query)
     *
     * @param array $productIds Array of Subiekt GT product IDs
     * @param int|null $warehouseId Filter by warehouse (null = all warehouses)
     * @return Collection Stock data grouped by product_id
     */
    public function getBatchProductStock(array $productIds, ?int $warehouseId = null): Collection
    {
        $query = $this->db()
            ->table('tw_Stan as s')
            ->join('sl_Magazyn as m', 's.st_MagId', '=', 'm.mag_Id')
            ->select([
                's.st_TowId as product_id',
                's.st_MagId as warehouse_id',
                'm.mag_Symbol as warehouse_code',
                's.st_Stan as quantity',
                's.st_Rezerwacja as reserved',
            ])
            ->whereIn('s.st_TowId', $productIds)
            ->where('m.mag_Aktywny', 1);

        if ($warehouseId !== null) {
            $query->where('s.st_MagId', $warehouseId);
        }

        return $query->get();
    }

    // ==========================================
    // PRICE QUERIES
    // ==========================================

    /**
     * Get all prices for single product
     *
     * @param int $productId Subiekt GT product ID
     * @return Collection Prices for all price types
     */
    public function getProductPrices(int $productId): Collection
    {
        return $this->db()
            ->table('tw_Cena as c')
            ->join('sl_RodzajCeny as r', 'c.tc_RodzCenyId', '=', 'r.rc_Id')
            ->select([
                'c.tc_TowId as product_id',
                'c.tc_RodzCenyId as price_type_id',
                'r.rc_Symbol as price_type_code',
                'r.rc_Nazwa as price_type_name',
                'c.tc_CenaNetto as price_net',
                'c.tc_CenaBrutto as price_gross',
            ])
            ->where('c.tc_TowId', $productId)
            ->where('r.rc_Aktywny', 1)
            ->get();
    }

    /**
     * Get specific price for product
     *
     * @param int $productId Subiekt GT product ID
     * @param int $priceTypeId Price type ID
     * @return object|null Price data or null
     */
    public function getProductPrice(int $productId, int $priceTypeId): ?object
    {
        return $this->db()
            ->table('tw_Cena as c')
            ->join('sl_RodzajCeny as r', 'c.tc_RodzCenyId', '=', 'r.rc_Id')
            ->select([
                'c.tc_TowId as product_id',
                'c.tc_RodzCenyId as price_type_id',
                'r.rc_Symbol as price_type_code',
                'r.rc_Nazwa as price_type_name',
                'c.tc_CenaNetto as price_net',
                'c.tc_CenaBrutto as price_gross',
            ])
            ->where('c.tc_TowId', $productId)
            ->where('c.tc_RodzCenyId', $priceTypeId)
            ->first();
    }

    /**
     * Get prices for multiple products (batch query)
     *
     * @param array $productIds Array of Subiekt GT product IDs
     * @param int|null $priceTypeId Filter by price type (null = all types)
     * @return Collection Prices grouped by product_id
     */
    public function getBatchProductPrices(array $productIds, ?int $priceTypeId = null): Collection
    {
        $query = $this->db()
            ->table('tw_Cena as c')
            ->join('sl_RodzajCeny as r', 'c.tc_RodzCenyId', '=', 'r.rc_Id')
            ->select([
                'c.tc_TowId as product_id',
                'c.tc_RodzCenyId as price_type_id',
                'r.rc_Symbol as price_type_code',
                'c.tc_CenaNetto as price_net',
                'c.tc_CenaBrutto as price_gross',
            ])
            ->whereIn('c.tc_TowId', $productIds)
            ->where('r.rc_Aktywny', 1);

        if ($priceTypeId !== null) {
            $query->where('c.tc_RodzCenyId', $priceTypeId);
        }

        return $query->get();
    }

    // ==========================================
    // WAREHOUSE & PRICE TYPE QUERIES
    // ==========================================

    /**
     * Get all active warehouses
     *
     * @return Collection Warehouses list
     */
    public function getWarehouses(): Collection
    {
        return $this->db()
            ->table('sl_Magazyn')
            ->select([
                'mag_Id as id',
                'mag_Symbol as code',
                'mag_Nazwa as name',
                'mag_Aktywny as is_active',
            ])
            ->where('mag_Aktywny', 1)
            ->orderBy('mag_Id')
            ->get();
    }

    /**
     * Get all active price types
     *
     * @return Collection Price types list
     */
    public function getPriceTypes(): Collection
    {
        return $this->db()
            ->table('sl_RodzajCeny')
            ->select([
                'rc_Id as id',
                'rc_Symbol as code',
                'rc_Nazwa as name',
                'rc_Aktywny as is_active',
            ])
            ->where('rc_Aktywny', 1)
            ->orderBy('rc_Id')
            ->get();
    }

    /**
     * Get all VAT rates
     *
     * @return Collection VAT rates list
     */
    public function getVatRates(): Collection
    {
        return $this->db()
            ->table('sl_StawkaVat')
            ->select([
                'sv_Id as id',
                'sv_Symbol as code',
                'sv_Nazwa as name',
                'sv_Stawka as rate',
                'sv_Aktywny as is_active',
            ])
            ->where('sv_Aktywny', 1)
            ->orderBy('sv_Id')
            ->get();
    }

    /**
     * Get all manufacturers/producers
     *
     * @return Collection Manufacturers list
     */
    public function getManufacturers(): Collection
    {
        return $this->db()
            ->table('sl_Producent')
            ->select([
                'pr_Id as id',
                'pr_Nazwa as name',
                'pr_Aktywny as is_active',
            ])
            ->where('pr_Aktywny', 1)
            ->orderBy('pr_Nazwa')
            ->get();
    }

    /**
     * Get product groups/categories
     *
     * @return Collection Product groups list
     */
    public function getProductGroups(): Collection
    {
        return $this->db()
            ->table('sl_GrupaTow')
            ->select([
                'gt_Id as id',
                'gt_Symbol as code',
                'gt_Nazwa as name',
                'gt_NadrzednaId as parent_id',
                'gt_Aktywny as is_active',
            ])
            ->where('gt_Aktywny', 1)
            ->orderBy('gt_Id')
            ->get();
    }

    // ==========================================
    // CONNECTION HELPERS
    // ==========================================

    /**
     * Test database connection
     *
     * @return array Connection test result
     */
    public function testConnection(): array
    {
        $startTime = microtime(true);

        try {
            $result = $this->db()->select('SELECT @@VERSION as version');
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $version = $result[0]->version ?? 'Unknown';

            return [
                'success' => true,
                'message' => 'Polaczenie z baza danych Subiekt GT pomyslne',
                'response_time' => $responseTime,
                'details' => [
                    'sql_server_version' => $version,
                    'connection_name' => $this->connection,
                ],
            ];
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('SubiektQueryBuilder: Connection test failed', [
                'connection' => $this->connection,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Blad polaczenia: ' . $e->getMessage(),
                'response_time' => $responseTime,
                'details' => [
                    'exception_type' => get_class($e),
                    'connection_name' => $this->connection,
                ],
            ];
        }
    }

    /**
     * Test access to required tables
     *
     * @return array Test result with accessible tables
     */
    public function testTableAccess(): array
    {
        $requiredTables = [
            'tw__Towar' => 'Produkty',
            'tw_Cena' => 'Ceny produktow',
            'tw_Stan' => 'Stany magazynowe',
            'sl_Magazyn' => 'Magazyny',
            'sl_RodzajCeny' => 'Rodzaje cen',
        ];

        $results = [];
        $allSuccess = true;

        foreach ($requiredTables as $table => $description) {
            try {
                $count = $this->db()->table($table)->count();
                $results[$table] = [
                    'accessible' => true,
                    'description' => $description,
                    'record_count' => $count,
                ];
            } catch (\Exception $e) {
                $results[$table] = [
                    'accessible' => false,
                    'description' => $description,
                    'error' => $e->getMessage(),
                ];
                $allSuccess = false;
            }
        }

        return [
            'success' => $allSuccess,
            'tables' => $results,
        ];
    }

    /**
     * Get database statistics for health check
     *
     * @return array Database statistics
     */
    public function getDatabaseStats(): array
    {
        try {
            return [
                'total_products' => $this->getTotalProductsCount(),
                'active_warehouses' => $this->getWarehouses()->count(),
                'price_types' => $this->getPriceTypes()->count(),
                'vat_rates' => $this->getVatRates()->count(),
                'manufacturers' => $this->getManufacturers()->count(),
                'product_groups' => $this->getProductGroups()->count(),
            ];
        } catch (\Exception $e) {
            Log::error('SubiektQueryBuilder: Failed to get database stats', [
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
