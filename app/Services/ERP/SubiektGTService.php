<?php

namespace App\Services\ERP;

use App\Models\ERPConnection;
use App\Models\Product;
use App\Models\ProductErpData;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use App\Models\IntegrationLog;
use App\Models\IntegrationMapping;
use App\Services\ERP\Contracts\ERPSyncServiceInterface;
use App\Services\ERP\SubiektGT\SubiektQueryBuilder;
use App\Services\ERP\SubiektGT\SubiektDataTransformer;
use App\Services\ERP\SubiektGT\SubiektRestApiClient;
use App\Services\ERP\SubiektGT\SubiektVariantResolver;
use App\Models\ProductVariant;
use App\Models\VariantPrice;
use App\Models\VariantStock;
use App\Exceptions\SubiektApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

/**
 * SubiektGTService
 *
 * ETAP: Subiekt GT ERP Integration
 *
 * Pelna implementacja integracji z Subiekt GT (InsERT).
 * Komunikacja przez REST API Wrapper na serwerze Windows (sapi.mpptrade.pl).
 *
 * Supported Features:
 * - Product synchronization (PULL/PUSH via REST API)
 * - Stock levels per warehouse
 * - Prices for all price types
 * - Warehouses and price types reference data
 *
 * NOT Supported (Subiekt GT limitations):
 * - Images (Subiekt GT does not store product images)
 * - Webhooks (polling required)
 * - Native variants (each variant = separate product)
 *
 * Connection Mode:
 * - rest_api: REST API wrapper on Windows server (ONLY supported mode)
 *   URL: https://sapi.mpptrade.pl
 *   Auth: X-API-Key header
 *
 * @package App\Services\ERP
 * @version 2.0
 */
class SubiektGTService implements ERPSyncServiceInterface
{
    protected ?SubiektQueryBuilder $queryBuilder = null;
    protected ?SubiektDataTransformer $transformer = null;
    protected ?SubiektRestApiClient $restApiClient = null;
    protected ?SubiektVariantResolver $variantResolver = null;
    protected string $connectionName = 'subiekt';

    /**
     * Get or create SubiektVariantResolver instance.
     *
     * @return SubiektVariantResolver
     */
    protected function getVariantResolver(): SubiektVariantResolver
    {
        if ($this->variantResolver === null) {
            $this->variantResolver = new SubiektVariantResolver();
        }
        return $this->variantResolver;
    }

    /**
     * Test connection to Subiekt GT database.
     *
     * @param array $config Connection configuration
     * @return array Test result
     */
    public function testConnection(array $config): array
    {
        $connectionMode = $config['connection_mode'] ?? 'rest_api';

        // REST API is the default and recommended mode
        if ($connectionMode === 'rest_api') {
            return $this->testConnectionViaRestApi($config);
        }

        // Legacy: SQL Direct connection test (not recommended)
        return $this->testConnectionViaSqlDirect($config);
    }

    /**
     * Test connection via SQL Direct mode.
     *
     * @param array $config Connection configuration
     * @return array Test result
     */
    protected function testConnectionViaSqlDirect(array $config): array
    {
        $startTime = microtime(true);

        try {
            // Configure dynamic database connection
            $this->configureConnection($config);

            // Create query builder with configured connection
            $queryBuilder = new SubiektQueryBuilder($this->connectionName);

            // Test basic connectivity
            $connectionResult = $queryBuilder->testConnection();

            if (!$connectionResult['success']) {
                return $connectionResult;
            }

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Get database statistics
            $stats = $queryBuilder->getDatabaseStats();

            // Log successful test
            IntegrationLog::info(
                'connection_test',
                'Subiekt GT connection test successful (SQL Direct)',
                [
                    'response_time' => $responseTime,
                    'stats' => $stats,
                    'config_mode' => 'sql_direct',
                ],
                IntegrationLog::INTEGRATION_SUBIEKT_GT,
                $config['db_host'] ?? 'unknown'
            );

            return [
                'success' => true,
                'message' => 'Polaczenie z Subiekt GT pomyslne (SQL Direct)',
                'response_time' => $responseTime,
                'details' => array_merge($connectionResult['details'], [
                    'total_products' => $stats['total_products'] ?? 0,
                    'active_warehouses' => $stats['active_warehouses'] ?? 0,
                    'price_types' => $stats['price_types'] ?? 0,
                    'connection_mode' => 'sql_direct',
                ]),
            ];

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            IntegrationLog::error(
                'connection_test',
                'Subiekt GT connection test failed (SQL Direct)',
                [
                    'response_time' => $responseTime,
                    'error' => $e->getMessage(),
                ],
                IntegrationLog::INTEGRATION_SUBIEKT_GT,
                $config['db_host'] ?? 'unknown',
                $e
            );

            return [
                'success' => false,
                'message' => 'Blad polaczenia: ' . $e->getMessage(),
                'response_time' => $responseTime,
                'details' => [
                    'exception_type' => get_class($e),
                    'connection_mode' => 'sql_direct',
                ],
            ];
        }
    }

    /**
     * Test connection via REST API mode.
     *
     * @param array $config Connection configuration
     * @return array Test result
     */
    protected function testConnectionViaRestApi(array $config): array
    {
        $startTime = microtime(true);

        try {
            $client = $this->createRestApiClient($config);
            $result = $client->testConnection();

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($result['success']) {
                // Get additional stats
                try {
                    $statsResponse = $client->getStats();
                    $stats = $statsResponse;
                } catch (\Exception $e) {
                    $stats = [];
                }

                IntegrationLog::info(
                    'connection_test',
                    'Subiekt GT REST API connection test successful',
                    [
                        'response_time' => $responseTime,
                        'api_url' => $config['rest_api_url'] ?? 'unknown',
                    ],
                    IntegrationLog::INTEGRATION_SUBIEKT_GT,
                    $config['rest_api_url'] ?? 'unknown'
                );

                return [
                    'success' => true,
                    'message' => 'Polaczenie z Subiekt GT REST API pomyslne',
                    'response_time' => $responseTime,
                    'details' => array_merge($result['details'] ?? [], [
                        'total_products' => $stats['total_products'] ?? $stats['active_products'] ?? 0,
                        'active_warehouses' => $stats['warehouses'] ?? 0,
                        'price_types' => $stats['price_types'] ?? 0,
                        'connection_mode' => 'rest_api',
                        'api_url' => $config['rest_api_url'] ?? '',
                    ]),
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Blad polaczenia z REST API',
                'response_time' => $responseTime,
                'details' => [
                    'connection_mode' => 'rest_api',
                ],
            ];

        } catch (SubiektApiException $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            IntegrationLog::error(
                'connection_test',
                'Subiekt GT REST API connection test failed',
                [
                    'response_time' => $responseTime,
                    'error' => $e->getMessage(),
                    'http_status' => $e->getHttpStatusCode(),
                ],
                IntegrationLog::INTEGRATION_SUBIEKT_GT,
                $config['rest_api_url'] ?? 'unknown',
                $e
            );

            return [
                'success' => false,
                'message' => 'Blad polaczenia REST API: ' . $e->getMessage(),
                'response_time' => $responseTime,
                'details' => [
                    'exception_type' => get_class($e),
                    'http_status' => $e->getHttpStatusCode(),
                    'connection_mode' => 'rest_api',
                ],
            ];

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => false,
                'message' => 'Blad polaczenia: ' . $e->getMessage(),
                'response_time' => $responseTime,
                'details' => [
                    'exception_type' => get_class($e),
                    'connection_mode' => 'rest_api',
                ],
            ];
        }
    }

    /**
     * Test authentication with Subiekt GT.
     *
     * @param array $config Connection configuration
     * @return array Test result with supported features
     */
    public function testAuthentication(array $config): array
    {
        $connectionMode = $config['connection_mode'] ?? 'rest_api';

        $connectionResult = $this->testConnection($config);

        if (!$connectionResult['success']) {
            return $connectionResult;
        }

        try {
            if ($connectionMode === 'rest_api') {
                return $this->testAuthenticationViaRestApi($config, $connectionResult);
            }

            // SQL Direct mode
            // Configure connection
            $this->configureConnection($config);
            $queryBuilder = new SubiektQueryBuilder($this->connectionName);

            // Test table access
            $tableAccess = $queryBuilder->testTableAccess();

            // Get warehouses and price types for mapping UI
            $warehouses = $queryBuilder->getWarehouses()->toArray();
            $priceTypes = $queryBuilder->getPriceTypes()->toArray();

            // Get database statistics
            $dbStats = $queryBuilder->getDatabaseStats();

            // Format database_stats for UI compatibility
            $databaseStats = [
                'product_count' => $dbStats['total_products'] ?? 0,
                'active_products' => $dbStats['total_products'] ?? 0, // Subiekt GT counts active only
                'contractor_count' => null, // Not yet implemented
                'warehouse_count' => $dbStats['active_warehouses'] ?? 0,
                'price_type_count' => $dbStats['price_types'] ?? 0,
            ];

            return [
                'success' => true,
                'message' => 'Uwierzytelnienie pomyslne',
                'response_time' => $connectionResult['response_time'],
                'details' => array_merge($connectionResult['details'], [
                    'table_access' => $tableAccess,
                    'warehouses' => $warehouses,
                    'price_types' => $priceTypes,
                    'database_stats' => $databaseStats,
                ]),
                'supported_features' => $this->getSupportedFeatures(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Blad uwierzytelnienia: ' . $e->getMessage(),
                'response_time' => $connectionResult['response_time'],
                'details' => [],
                'supported_features' => $this->getSupportedFeatures(),
            ];
        }
    }

    /**
     * Test authentication via REST API.
     *
     * @param array $config Connection configuration
     * @param array $connectionResult Connection test result
     * @return array Authentication test result
     */
    protected function testAuthenticationViaRestApi(array $config, array $connectionResult): array
    {
        try {
            $client = $this->createRestApiClient($config);

            // Get reference data for mapping UI
            $warehousesResponse = $client->getWarehouses(false);
            $priceTypesResponse = $client->getPriceTypes(false);
            $statsResponse = $client->getStats();

            $warehouses = $warehousesResponse['data'] ?? [];
            $priceTypes = $priceTypesResponse['data'] ?? [];

            // Format database_stats for UI compatibility
            $databaseStats = [
                'product_count' => $statsResponse['total_products'] ?? 0,
                'active_products' => $statsResponse['active_products'] ?? $statsResponse['total_products'] ?? 0,
                'contractor_count' => null,
                'warehouse_count' => $statsResponse['warehouses'] ?? count($warehouses),
                'price_type_count' => $statsResponse['price_types'] ?? count($priceTypes),
            ];

            return [
                'success' => true,
                'message' => 'Uwierzytelnienie REST API pomyslne',
                'response_time' => $connectionResult['response_time'],
                'details' => array_merge($connectionResult['details'], [
                    'warehouses' => $warehouses,
                    'price_types' => $priceTypes,
                    'database_stats' => $databaseStats,
                ]),
                'supported_features' => $this->getSupportedFeatures(),
            ];

        } catch (SubiektApiException $e) {
            return [
                'success' => false,
                'message' => 'Blad uwierzytelnienia REST API: ' . $e->getMessage(),
                'response_time' => $connectionResult['response_time'],
                'details' => [
                    'http_status' => $e->getHttpStatusCode(),
                ],
                'supported_features' => $this->getSupportedFeatures(),
            ];
        }
    }

    /**
     * Sync single product TO Subiekt GT.
     *
     * NOTE: In sql_direct mode, this validates and maps the product
     * but does NOT write to Subiekt GT (requires Sfera API).
     *
     * @param ERPConnection $connection Active ERP connection
     * @param Product $product Product to sync
     * @param array $syncOptions Optional sync options for selective field synchronization
     * @return array Sync result
     */
    public function syncProductToERP(ERPConnection $connection, Product $product, array $syncOptions = []): array
    {
        $config = $connection->connection_config;
        $connectionMode = $config['connection_mode'] ?? 'rest_api';

        // SQL Direct mode = read-only
        if ($connectionMode === 'sql_direct') {
            return $this->validateProductForSubiekt($connection, $product);
        }

        // REST API mode - would require external endpoint
        if ($connectionMode === 'rest_api') {
            return $this->syncProductViaRestApi($connection, $product, $syncOptions);
        }

        // Sfera API mode - would require COM bridge
        if ($connectionMode === 'sfera_api') {
            return $this->syncProductViaSferaApi($connection, $product, $syncOptions);
        }

        return [
            'success' => false,
            'message' => 'Nieobslugiwany tryb polaczenia: ' . $connectionMode,
            'external_id' => null,
        ];
    }

    /**
     * Sync single product FROM Subiekt GT (PULL).
     *
     * @param ERPConnection $connection Active ERP connection
     * @param string $erpProductId Subiekt GT product ID or SKU
     * @return array Pull result
     */
    public function syncProductFromERP(ERPConnection $connection, string $erpProductId): array
    {
        try {
            $this->initializeForConnection($connection);

            $config = $connection->connection_config;
            $connectionMode = $config['connection_mode'] ?? 'rest_api';

            // REST API mode - use REST client
            if ($connectionMode === 'rest_api' && $this->restApiClient) {
                $productResult = is_numeric($erpProductId)
                    ? $this->restApiClient->getProductById((int) $erpProductId)
                    : $this->restApiClient->getProductBySku($erpProductId);

                if (!$productResult || !($productResult['success'] ?? false) || empty($productResult['data'])) {
                    return [
                        'success' => false,
                        'message' => 'Produkt nie znaleziony w Subiekt GT: ' . $erpProductId,
                        'product' => null,
                    ];
                }

                $subiektProductData = $productResult['data'];
                $productId = $subiektProductData['id'] ?? $subiektProductData['Id'] ?? $subiektProductData['tw_Id'] ?? null;

                // Get prices and stock via REST API
                $pricesResult = $productId ? $this->restApiClient->getProductPrices((int) $productId) : null;
                $stockResult = $productId ? $this->restApiClient->getProductStock((int) $productId) : null;

                $prices = ($pricesResult && ($pricesResult['success'] ?? false)) ? ($pricesResult['data'] ?? []) : [];
                $stock = ($stockResult && ($stockResult['success'] ?? false)) ? ($stockResult['data'] ?? []) : [];

                // Create object for transformer with ALL fields from API
                // ETAP_08 FAZA 7 FIX: Include extended fields (Pole1-5, ShopInternet, SplitPayment, etc.)
                $subiektProduct = (object) [
                    'id' => $productId,
                    'sku' => $subiektProductData['sku'] ?? $subiektProductData['Sku'] ?? $subiektProductData['tw_Symbol'] ?? '',
                    'name' => $subiektProductData['name'] ?? $subiektProductData['Name'] ?? $subiektProductData['tw_Nazwa'] ?? '',
                    'ean' => $subiektProductData['ean'] ?? $subiektProductData['Ean'] ?? $subiektProductData['tw_SWW'] ?? '',
                    // Basic fields
                    'priceNet' => $subiektProductData['priceNet'] ?? $subiektProductData['PriceNet'] ?? null,
                    'priceGross' => $subiektProductData['priceGross'] ?? $subiektProductData['PriceGross'] ?? null,
                    'stock' => $subiektProductData['stock'] ?? $subiektProductData['Stock'] ?? 0,
                    'stockReserved' => $subiektProductData['stockReserved'] ?? $subiektProductData['StockReserved'] ?? 0,
                    'isActive' => $subiektProductData['isActive'] ?? $subiektProductData['IsActive'] ?? true,
                    'vatRate' => $subiektProductData['vatRate'] ?? $subiektProductData['VatRate'] ?? null,
                    'groupName' => $subiektProductData['groupName'] ?? $subiektProductData['GroupName'] ?? null,
                    'unit' => $subiektProductData['unit'] ?? $subiektProductData['Unit'] ?? null,
                    'weight' => $subiektProductData['weight'] ?? $subiektProductData['Weight'] ?? null,
                    // Extended fields (tw_Pole1-5, tw_Uwagi)
                    'Pole1' => $subiektProductData['pole1'] ?? $subiektProductData['Pole1'] ?? null,
                    'Pole2' => $subiektProductData['pole2'] ?? $subiektProductData['Pole2'] ?? null,
                    'Pole3' => $subiektProductData['pole3'] ?? $subiektProductData['Pole3'] ?? null,
                    'Pole4' => $subiektProductData['pole4'] ?? $subiektProductData['Pole4'] ?? null,
                    'Pole5' => $subiektProductData['pole5'] ?? $subiektProductData['Pole5'] ?? null,
                    'Notes' => $subiektProductData['notes'] ?? $subiektProductData['Notes'] ?? null,
                    // Boolean flags
                    'ShopInternet' => $subiektProductData['shopInternet'] ?? $subiektProductData['ShopInternet'] ?? null,
                    'SplitPayment' => $subiektProductData['splitPayment'] ?? $subiektProductData['SplitPayment'] ?? null,
                    // Manufacturer and supplier
                    'ManufacturerName' => $subiektProductData['manufacturerName'] ?? $subiektProductData['ManufacturerName'] ?? null,
                    'SupplierCode' => $subiektProductData['supplierCode'] ?? $subiektProductData['SupplierCode'] ?? null,
                ];
            } else {
                // SQL Direct mode - use query builder
                $subiektProduct = is_numeric($erpProductId)
                    ? $this->queryBuilder->getProductById((int) $erpProductId)
                    : $this->queryBuilder->getProductBySKU($erpProductId);

                if (!$subiektProduct) {
                    return [
                        'success' => false,
                        'message' => 'Produkt nie znaleziony w Subiekt GT: ' . $erpProductId,
                        'product' => null,
                    ];
                }

                // Get full product data (prices and stock)
                $prices = $this->queryBuilder->getProductPrices($subiektProduct->id)->toArray();
                $stock = $this->queryBuilder->getProductStock($subiektProduct->id)->toArray();
            }

            // Transform to PPM format
            $ppmData = $this->transformer->subiektToPPM($subiektProduct, $prices, $stock);

            // ETAP_08 FAZA 8: Enrich stock data with locations from Pole2
            $pole2Value = $ppmData['Pole2'] ?? $subiektProduct->Pole2 ?? $subiektProduct->pole2 ?? null;
            if (!empty($pole2Value) && !empty($ppmData['stock'])) {
                $config = $connection->connection_config ?? [];
                $warehouseMappings = $config['warehouse_mappings'] ?? [];
                $defaultWarehouseId = $config['default_warehouse_id'] ?? null;
                $copyLocationToAll = $config['copy_location_to_all'] ?? false;

                // Parse locations - simple format goes to Default Warehouse
                $locations = $this->parseStockLocationsFromErp(
                    $pole2Value,
                    $warehouseMappings,
                    $defaultWarehouseId,
                    $copyLocationToAll,
                    $ppmData['stock']
                );

                // Handle '_default' location fallback - assign to default warehouse
                if (isset($locations['_default'])) {
                    $defaultLocation = $locations['_default'];
                    unset($locations['_default']);
                    if ($defaultWarehouseId !== null) {
                        $locations[$defaultWarehouseId] = $defaultLocation;
                        // Copy to all if enabled
                        if ($copyLocationToAll) {
                            foreach (array_keys($ppmData['stock']) as $warehouseId) {
                                if ($warehouseId !== $defaultWarehouseId) {
                                    $locations[$warehouseId] = $defaultLocation;
                                }
                            }
                        }
                    }
                }

                // Add location to each stock entry
                foreach ($locations as $ppmWarehouseId => $location) {
                    if (isset($ppmData['stock'][$ppmWarehouseId])) {
                        $ppmData['stock'][$ppmWarehouseId]['location'] = $location;
                    }
                }

                Log::debug('[Subiekt] Enriched stock with locations from Pole2', [
                    'pole2' => $pole2Value,
                    'default_warehouse_id' => $defaultWarehouseId,
                    'copy_to_all' => $copyLocationToAll,
                    'locations' => $locations,
                    'stock_keys' => array_keys($ppmData['stock'] ?? []),
                ]);
            }

            // Find or create PPM product by SKU
            $product = $this->findOrCreateProduct($ppmData, $connection);

            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Nie udalo sie utworzyc/zaktualizowac produktu w PPM',
                    'product' => null,
                ];
            }

            // Update/create ProductErpData
            $this->updateProductErpData($product, $connection, $subiektProduct);

            // Update/create IntegrationMapping
            $this->updateIntegrationMapping($product, $connection, (string) $subiektProduct->id);

            IntegrationLog::info(
                'product_pull',
                'Product pulled from Subiekt GT',
                [
                    'subiekt_id' => $subiektProduct->id,
                    'subiekt_sku' => $subiektProduct->sku,
                    'ppm_product_id' => $product->id,
                ],
                IntegrationLog::INTEGRATION_SUBIEKT_GT,
                (string) $connection->id
            );

            // === PULL VARIANTS IF PRODUCT IS VARIANT MASTER ===
            $variantsUpdated = 0;
            $variantsFailed = 0;
            if ($product->is_variant_master && $product->variants()->count() > 0) {
                Log::info('syncProductFromERP: Product is variant master, pulling variants from Subiekt', [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'variants_count' => $product->variants()->count(),
                ]);

                try {
                    $variantPullResult = $this->pullProductVariantsFromSubiekt($connection, $product);
                    $variantsUpdated = $variantPullResult['updated'] ?? 0;
                    $variantsFailed = $variantPullResult['failed'] ?? 0;

                    Log::info('syncProductFromERP: Variants pull completed', [
                        'product_id' => $product->id,
                        'variants_updated' => $variantsUpdated,
                        'variants_failed' => $variantsFailed,
                    ]);
                } catch (\Exception $e) {
                    Log::error('syncProductFromERP: Variants pull failed', [
                        'product_id' => $product->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return [
                'success' => true,
                'message' => 'Produkt pobrany pomyslnie' . ($variantsUpdated > 0 ? " (+ {$variantsUpdated} wariantów)" : ''),
                'product' => $product,
                'erp_data' => $ppmData,
                'variants_updated' => $variantsUpdated,
                'variants_failed' => $variantsFailed,
            ];

        } catch (\Exception $e) {
            IntegrationLog::error(
                'product_pull',
                'Failed to pull product from Subiekt GT',
                [
                    'erp_product_id' => $erpProductId,
                    'error' => $e->getMessage(),
                ],
                IntegrationLog::INTEGRATION_SUBIEKT_GT,
                (string) $connection->id,
                $e
            );

            return [
                'success' => false,
                'message' => 'Blad pobierania: ' . $e->getMessage(),
                'product' => null,
            ];
        }
    }

    /**
     * Sync all products TO Subiekt GT (batch PUSH).
     *
     * NOTE: In sql_direct mode, this validates all products but does NOT write.
     *
     * @param ERPConnection $connection Active ERP connection
     * @param array $filters Optional filters
     * @return array Batch sync result
     */
    public function syncAllProducts(ERPConnection $connection, array $filters = []): array
    {
        $config = $connection->connection_config;
        $connectionMode = $config['connection_mode'] ?? 'rest_api';

        // SQL Direct mode = validation only
        if ($connectionMode === 'sql_direct') {
            return $this->validateAllProductsForSubiekt($connection, $filters);
        }

        // Other modes would require external API
        return [
            'success' => false,
            'total' => 0,
            'synced' => 0,
            'failed' => 0,
            'errors' => ['Tryb ' . $connectionMode . ' nie jest jeszcze zaimplementowany dla batch push'],
        ];
    }

    /**
     * Pull all products FROM Subiekt GT (batch PULL).
     *
     * @param ERPConnection $connection Active ERP connection
     * @param array $filters Optional filters (mode, since, limit)
     * @return array Batch pull result
     */
    public function pullAllProducts(ERPConnection $connection, array $filters = []): array
    {
        $startTime = Carbon::now();

        try {
            $this->initializeForConnection($connection);

            $mode = $filters['mode'] ?? 'full';
            $limit = $filters['limit'] ?? 1000;
            $since = $filters['since'] ?? null;

            $config = $connection->connection_config;
            $defaultPriceTypeId = $config['default_price_type_id'] ?? 1;
            $defaultWarehouseId = $config['default_warehouse_id'] ?? 1;

            $results = [
                'success' => true,
                'total' => 0,
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [],
                'mode' => $mode,
                'updated_products' => [],  // ETAP_08: Track which products were updated
                'imported_products' => [], // ETAP_08: Track which products were imported
            ];

            $connectionMode = $config['connection_mode'] ?? 'rest_api';

            // === REST API MODE ===
            if ($connectionMode === 'rest_api' && $this->restApiClient !== null) {
                $results = $this->pullAllProductsViaRestApi(
                    $connection,
                    $results,
                    $mode,
                    $since,
                    $limit,
                    $defaultPriceTypeId,
                    $defaultWarehouseId
                );
            }
            // === SQL DIRECT MODE ===
            elseif ($this->queryBuilder !== null) {
                $results = $this->pullAllProductsViaSqlDirect(
                    $connection,
                    $results,
                    $mode,
                    $since,
                    $limit,
                    $defaultPriceTypeId,
                    $defaultWarehouseId
                );
            } else {
                throw new \RuntimeException('No valid connection method available (REST API client and Query Builder are both null)');
            }

            $duration = $startTime->diffInSeconds(Carbon::now());

            // Update connection stats
            $connection->updateSyncStats(
                true,
                $results['imported'] + $results['updated'],
                $duration
            );

            IntegrationLog::info(
                'products_pull',
                'Batch pull from Subiekt GT completed',
                [
                    'results' => $results,
                    'duration_seconds' => $duration,
                ],
                IntegrationLog::INTEGRATION_SUBIEKT_GT,
                (string) $connection->id
            );

            return $results;

        } catch (\Exception $e) {
            IntegrationLog::error(
                'products_pull',
                'Batch pull from Subiekt GT failed',
                [
                    'error' => $e->getMessage(),
                    'filters' => $filters,
                ],
                IntegrationLog::INTEGRATION_SUBIEKT_GT,
                (string) $connection->id,
                $e
            );

            return [
                'success' => false,
                'total' => 0,
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['Exception: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Pull products via REST API mode.
     *
     * @param ERPConnection $connection
     * @param array $results Initial results array
     * @param string $mode Sync mode (full, incremental, prices, stock, basic_data)
     * @param string|null $since Timestamp for incremental
     * @param int $limit Max products to fetch
     * @param int $defaultPriceTypeId Default price level
     * @param int $defaultWarehouseId Default warehouse
     * @return array Updated results
     */
    protected function pullAllProductsViaRestApi(
        ERPConnection $connection,
        array $results,
        string $mode,
        ?string $since,
        int $limit,
        int $defaultPriceTypeId,
        int $defaultWarehouseId
    ): array {
        $processed = 0;

        // Build filters for REST API
        $apiFilters = [
            'priceLevel' => $defaultPriceTypeId,
            'warehouseId' => $defaultWarehouseId,
        ];

        if ($mode === 'incremental' && $since) {
            $apiFilters['modified_since'] = $since;
        }

        Log::info('pullAllProductsViaRestApi: Starting REST API pull', [
            'mode' => $mode,
            'filters' => $apiFilters,
            'limit' => $limit,
        ]);

        // Use generator to iterate over all products
        foreach ($this->restApiClient->getAllProducts($apiFilters, 100) as $productData) {
            if ($processed >= $limit) {
                break;
            }

            try {
                // REST API returns array, convert to object for transformer
                $subiektProduct = (object) $productData;

                // For REST API, prices and stock are included in product response
                $prices = $productData['prices'] ?? [];
                $stock = $productData['stock'] ?? [];

                // Transform to PPM format
                $ppmData = $this->transformer->subiektToPPM(
                    $subiektProduct,
                    $prices,
                    is_array($stock) ? $stock : [$stock]
                );

                // Find or create PPM product
                $result = $this->importProduct($ppmData, $connection, $subiektProduct);

                if ($result['created']) {
                    $results['imported']++;
                    // ETAP_08: Track imported products (limit to 50 for performance)
                    if (count($results['imported_products']) < 50) {
                        $results['imported_products'][] = [
                            'sku' => $result['sku'] ?? $ppmData['sku'] ?? 'unknown',
                            'name' => $result['name'] ?? $ppmData['name'] ?? '',
                            'product_id' => $result['product_id'] ?? null,
                        ];
                    }
                } elseif ($result['updated']) {
                    $results['updated']++;
                    // ETAP_08: Track updated products (limit to 50 for performance)
                    if (count($results['updated_products']) < 50) {
                        $results['updated_products'][] = [
                            'sku' => $result['sku'] ?? $ppmData['sku'] ?? 'unknown',
                            'name' => $result['name'] ?? $ppmData['name'] ?? '',
                            'product_id' => $result['product_id'] ?? null,
                        ];
                    }
                } else {
                    $results['skipped']++;
                }

                $processed++;
                $results['total']++;

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'subiekt_id' => $productData['id'] ?? 'unknown',
                    'sku' => $productData['sku'] ?? $productData['symbol'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
                $processed++;
                $results['total']++;
            }
        }

        Log::info('pullAllProductsViaRestApi: Completed', [
            'total' => $results['total'],
            'imported' => $results['imported'],
            'updated' => $results['updated'],
            'skipped' => $results['skipped'],
            'errors_count' => count($results['errors']),
        ]);

        return $results;
    }

    /**
     * Pull products via SQL Direct mode.
     *
     * @param ERPConnection $connection
     * @param array $results Initial results array
     * @param string $mode Sync mode
     * @param string|null $since Timestamp for incremental
     * @param int $limit Max products to fetch
     * @param int $defaultPriceTypeId Default price level
     * @param int $defaultWarehouseId Default warehouse
     * @return array Updated results
     */
    protected function pullAllProductsViaSqlDirect(
        ERPConnection $connection,
        array $results,
        string $mode,
        ?string $since,
        int $limit,
        int $defaultPriceTypeId,
        int $defaultWarehouseId
    ): array {
        // Get products based on mode
        if ($mode === 'incremental' && $since) {
            $subiektProducts = $this->queryBuilder->getModifiedProducts(
                $since,
                $defaultPriceTypeId,
                $defaultWarehouseId,
                $limit
            );
        } else {
            $subiektProducts = $this->queryBuilder->getAllProducts(
                $defaultPriceTypeId,
                $defaultWarehouseId,
                $limit,
                0
            );
        }

        $results['total'] = $subiektProducts->count();

        // Batch fetch prices and stock for efficiency
        $productIds = $subiektProducts->pluck('id')->toArray();
        $allPrices = $this->queryBuilder->getBatchProductPrices($productIds)->groupBy('product_id')->toArray();
        $allStock = $this->queryBuilder->getBatchProductStock($productIds)->groupBy('product_id')->toArray();

        foreach ($subiektProducts as $subiektProduct) {
            try {
                $prices = $allPrices[$subiektProduct->id] ?? [];
                $stock = $allStock[$subiektProduct->id] ?? [];

                // Transform to PPM format
                $ppmData = $this->transformer->subiektToPPM(
                    $subiektProduct,
                    is_array($prices) ? $prices : $prices->toArray(),
                    is_array($stock) ? $stock : $stock->toArray()
                );

                // Find or create PPM product
                $result = $this->importProduct($ppmData, $connection, $subiektProduct);

                if ($result['created']) {
                    $results['imported']++;
                    // ETAP_08: Track imported products (limit to 50 for performance)
                    if (count($results['imported_products']) < 50) {
                        $results['imported_products'][] = [
                            'sku' => $result['sku'] ?? $ppmData['sku'] ?? 'unknown',
                            'name' => $result['name'] ?? $ppmData['name'] ?? '',
                            'product_id' => $result['product_id'] ?? null,
                        ];
                    }
                } elseif ($result['updated']) {
                    $results['updated']++;
                    // ETAP_08: Track updated products (limit to 50 for performance)
                    if (count($results['updated_products']) < 50) {
                        $results['updated_products'][] = [
                            'sku' => $result['sku'] ?? $ppmData['sku'] ?? 'unknown',
                            'name' => $result['name'] ?? $ppmData['name'] ?? '',
                            'product_id' => $result['product_id'] ?? null,
                        ];
                    }
                } else {
                    $results['skipped']++;
                }

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'subiekt_id' => $subiektProduct->id,
                    'sku' => $subiektProduct->sku ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Pull only products linked to this ERP connection (OPTIMIZED).
     *
     * PERFORMANCE OPTIMIZATION (2026-01-26):
     * Instead of fetching ALL products from Subiekt GT (12717+), this method:
     * 1. Gets only products with ProductErpData linked to this connection
     * 2. Fetches their data from Subiekt by SKU (batch)
     * 3. Compares tw_DataMod with last_pull_at to skip unchanged products
     * 4. Updates only products that have changed in Subiekt
     *
     * Expected result:
     * - Total: 13 (linked products in PPM)
     * - Skipped: 11 (no changes since last pull)
     * - Updated: 2 (actually changed in Subiekt)
     * - Duration: <5s (instead of minutes for full sync)
     *
     * @param ERPConnection $connection Active ERP connection
     * @param array $filters Optional filters
     * @return array Results with counts
     */
    public function pullLinkedProducts(ERPConnection $connection, array $filters = []): array
    {
        $startTime = Carbon::now();

        try {
            $this->initializeForConnection($connection);

            $config = $connection->connection_config;
            $defaultPriceTypeId = $config['default_price_type_id'] ?? 1;
            $defaultWarehouseId = $config['default_warehouse_id'] ?? 1;
            $connectionMode = $config['connection_mode'] ?? 'rest_api';

            // ETAP_08 FAZA 7: Support sync_type filter for selective updates
            // - 'prices': Only update ProductGroupPrice records
            // - 'stock': Only update ProductStock records
            // - 'basic_data': Only update ProductErpData basic fields
            // - 'linked_only'/default: Update everything
            $syncType = $filters['sync_type'] ?? 'linked_only';

            $results = [
                'success' => true,
                'total' => 0,
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'not_found' => 0,
                'errors' => [],
                'mode' => $syncType,
                'sync_type' => $syncType,
                'updated_products' => [],
                'imported_products' => [],
            ];

            // STEP 1: Get all products linked to this ERP connection
            $linkedProducts = Product::whereHas('erpData', function($query) use ($connection) {
                $query->where('erp_connection_id', $connection->id);
            })
            ->with(['erpData' => function($query) use ($connection) {
                $query->where('erp_connection_id', $connection->id);
            }])
            ->get();

            $results['total'] = $linkedProducts->count();

            Log::info('pullLinkedProducts: Starting optimized pull', [
                'connection_id' => $connection->id,
                'linked_products_count' => $results['total'],
                'connection_mode' => $connectionMode,
            ]);

            if ($results['total'] === 0) {
                Log::info('pullLinkedProducts: No linked products found');
                return $results;
            }

            // STEP 2: Collect SKUs for batch fetch
            $skuToProduct = [];
            $skuToErpData = [];

            foreach ($linkedProducts as $product) {
                $sku = $product->sku;
                if (empty($sku)) {
                    Log::warning('pullLinkedProducts: Product without SKU', [
                        'product_id' => $product->id,
                    ]);
                    $results['errors'][] = [
                        'product_id' => $product->id,
                        'error' => 'Product has no SKU',
                    ];
                    continue;
                }

                $skuToProduct[$sku] = $product;
                $skuToErpData[$sku] = $product->erpData->first();
            }

            $skus = array_keys($skuToProduct);

            Log::debug('pullLinkedProducts: SKUs to fetch', [
                'count' => count($skus),
                'sample' => array_slice($skus, 0, 5),
            ]);

            // STEP 3: Fetch products from Subiekt by SKUs
            if ($connectionMode === 'rest_api' && $this->restApiClient !== null) {
                $subiektProducts = $this->restApiClient->getProductsBySkus(
                    $skus,
                    $defaultPriceTypeId,
                    $defaultWarehouseId
                );
            } else {
                // SQL Direct mode - fetch one by one (less efficient but works)
                $subiektProducts = [];
                foreach ($skus as $sku) {
                    try {
                        $product = $this->queryBuilder->getProductBySku(
                            $sku,
                            $defaultPriceTypeId,
                            $defaultWarehouseId
                        );
                        if ($product) {
                            $subiektProducts[$sku] = (array) $product;
                        }
                    } catch (\Exception $e) {
                        Log::debug('pullLinkedProducts: Product not found in Subiekt', [
                            'sku' => $sku,
                        ]);
                    }
                }
            }

            Log::info('pullLinkedProducts: Fetched from Subiekt', [
                'requested' => count($skus),
                'found' => count($subiektProducts),
            ]);

            // STEP 3.5: ETAP_08 FAZA 7.4 - Batch pre-fetch prices/stock for ALL SKUs
            // This replaces individual API calls in the loop (92s -> ~7s for 13 products)
            $batchPrices = [];
            $batchStocks = [];

            if ($syncType === 'prices' && $this->restApiClient) {
                $batchPrices = $this->restApiClient->batchFetchPricesBySku($skus);
                Log::info('pullLinkedProducts: Batch fetched prices', [
                    'requested' => count($skus),
                    'fetched' => count($batchPrices),
                ]);
            }

            if ($syncType === 'stock' && $this->restApiClient) {
                $batchStocks = $this->restApiClient->batchFetchStockBySku($skus);
                Log::info('pullLinkedProducts: Batch fetched stock', [
                    'requested' => count($skus),
                    'fetched' => count($batchStocks),
                ]);
            }

            // STEP 4: Process each product - compare timestamps, update if changed
            foreach ($skuToProduct as $sku => $ppmProduct) {
                $erpData = $skuToErpData[$sku] ?? null;

                // Check if product exists in Subiekt
                if (!isset($subiektProducts[$sku])) {
                    $results['not_found']++;
                    Log::debug('pullLinkedProducts: SKU not found in Subiekt', [
                        'sku' => $sku,
                        'product_id' => $ppmProduct->id,
                    ]);
                    continue;
                }

                $subiektData = $subiektProducts[$sku];

                // Check if product has changed (compare tw_DataMod with last_pull_at)
                // ETAP_08 FAZA 7: For selective sync_types (prices, stock, basic_data),
                // skip timestamp check because tw_DataMod is global for ALL changes.
                // E.g., name change updates tw_DataMod but doesn't mean prices changed.
                $subiektModified = $subiektData['date_modified'] ?? $subiektData['tw_DataMod'] ?? null;
                $isSelectiveSyncType = in_array($syncType, ['prices', 'stock', 'basic_data']);

                if (!$isSelectiveSyncType && $erpData && $erpData->last_pull_at && $subiektModified) {
                    $subiektTimestamp = is_numeric($subiektModified)
                        ? $subiektModified
                        : strtotime($subiektModified);
                    $lastPullTimestamp = $erpData->last_pull_at->timestamp;

                    // Skip if Subiekt hasn't changed since last pull (only for full sync)
                    if ($subiektTimestamp <= $lastPullTimestamp) {
                        $results['skipped']++;
                        Log::debug('pullLinkedProducts: No changes, skipping', [
                            'sku' => $sku,
                            'subiekt_modified' => $subiektModified,
                            'last_pull_at' => $erpData->last_pull_at->toDateTimeString(),
                        ]);
                        continue;
                    }
                }

                // STEP 5: Product has changed - selective update based on syncType
                // ETAP_08 FAZA 7: Only update specific data based on sync_type
                try {
                    $subiektProduct = (object) $subiektData;
                    $prices = $subiektData['prices'] ?? [];
                    $stockData = $subiektData['stock'] ?? [];

                    $wasUpdated = false;
                    $updateDetails = [];

                    switch ($syncType) {
                        case 'prices':
                            // ETAP_08 FAZA 7.4: Use batch pre-fetched prices (parallel requests)
                            // Fallback to subiektData if batch not available
                            if (empty($prices)) {
                                $prices = $batchPrices[$sku] ?? [];
                            }
                            // ETAP_08 FAZA 7.3: Smart comparison - update only when PPM != Subiekt
                            $priceResult = $this->updateProductPricesFromErp($ppmProduct, $prices, $connection);
                            $wasUpdated = $priceResult['updated'] ?? false;
                            $updateDetails = [
                                'type' => 'prices',
                                'prices_updated' => $priceResult['count'] ?? 0,
                                'prices_skipped' => $priceResult['skipped'] ?? 0,
                                'changes' => $priceResult['changes'] ?? [],
                            ];
                            break;

                        case 'stock':
                            // ETAP_08 FAZA 7.4: Use batch pre-fetched stock (parallel requests)
                            // Fallback to subiektData if batch not available
                            if (empty($stockData)) {
                                $stockData = $batchStocks[$sku] ?? [];
                            }
                            // ETAP_08 FAZA 7.3: Smart comparison - update only when PPM != Subiekt
                            $stockResult = $this->updateProductStockFromErp($ppmProduct, $stockData, $connection);
                            $wasUpdated = $stockResult['updated'] ?? false;
                            $updateDetails = [
                                'type' => 'stock',
                                'stock_updated' => $stockResult['count'] ?? 0,
                                'stock_skipped' => $stockResult['skipped'] ?? 0,
                                'changes' => $stockResult['changes'] ?? [],
                            ];
                            break;

                        case 'basic_data':
                            // Only update basic data (name, description) in ProductErpData
                            $basicResult = $this->updateProductBasicDataFromErp($ppmProduct, $subiektProduct, $connection);
                            $wasUpdated = $basicResult['updated'] ?? false;
                            $updateDetails = [
                                'type' => 'basic_data',
                                'fields_updated' => $basicResult['fields'] ?? [],
                            ];
                            break;

                        default: // 'linked_only' or any other - full update
                            $ppmData = $this->transformer->subiektToPPM(
                                $subiektProduct,
                                $prices,
                                is_array($stockData) ? $stockData : [$stockData]
                            );
                            $result = $this->importProduct($ppmData, $connection, $subiektProduct);
                            $wasUpdated = $result['updated'] ?? $result['created'] ?? false;
                            $updateDetails = ['type' => 'full'];
                    }

                    // === PULL VARIANTS IF PRODUCT IS VARIANT MASTER ===
                    $variantsUpdated = 0;
                    $variantsFailed = 0;
                    if ($ppmProduct->is_variant_master && $ppmProduct->variants()->count() > 0) {
                        Log::info('pullLinkedProducts: Product is variant master, pulling variants from Subiekt', [
                            'product_id' => $ppmProduct->id,
                            'sku' => $sku,
                            'variants_count' => $ppmProduct->variants()->count(),
                        ]);

                        try {
                            $variantPullResult = $this->pullProductVariantsFromSubiekt($connection, $ppmProduct);
                            $variantsUpdated = $variantPullResult['updated'] ?? 0;
                            $variantsFailed = $variantPullResult['failed'] ?? 0;

                            if ($variantsUpdated > 0) {
                                $wasUpdated = true;
                                $updateDetails['variants_updated'] = $variantsUpdated;
                            }
                            if ($variantsFailed > 0) {
                                $updateDetails['variants_failed'] = $variantsFailed;
                            }

                            Log::info('pullLinkedProducts: Variants pull completed', [
                                'product_id' => $ppmProduct->id,
                                'variants_updated' => $variantsUpdated,
                                'variants_failed' => $variantsFailed,
                            ]);
                        } catch (\Exception $e) {
                            Log::error('pullLinkedProducts: Variants pull failed', [
                                'product_id' => $ppmProduct->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    if ($wasUpdated) {
                        $results['updated']++;
                        if (count($results['updated_products']) < 50) {
                            $results['updated_products'][] = array_merge([
                                'sku' => $sku,
                                'name' => $subiektProduct->name ?? $ppmProduct->name,
                                'product_id' => $ppmProduct->id,
                            ], $updateDetails);
                        }
                    } else {
                        $results['skipped']++;
                    }

                    // Update last_pull_at timestamp
                    if ($erpData) {
                        $erpData->markPulled();
                        if ($subiektModified) {
                            $erpData->update([
                                'erp_updated_at' => Carbon::parse($subiektModified),
                            ]);
                        }
                    }

                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'sku' => $sku,
                        'product_id' => $ppmProduct->id,
                        'error' => $e->getMessage(),
                    ];
                    Log::warning('pullLinkedProducts: Error processing product', [
                        'sku' => $sku,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $duration = $startTime->diffInSeconds(Carbon::now());

            Log::info('pullLinkedProducts: Completed', [
                'duration_seconds' => $duration,
                'total' => $results['total'],
                'updated' => $results['updated'],
                'imported' => $results['imported'],
                'skipped' => $results['skipped'],
                'not_found' => $results['not_found'],
                'errors_count' => count($results['errors']),
            ]);

            // Update connection stats
            $connection->updateSyncStats(
                true,
                $results['imported'] + $results['updated'],
                $duration
            );

            IntegrationLog::info(
                'products_pull_linked',
                'Optimized linked products pull from Subiekt GT completed',
                [
                    'results' => $results,
                    'duration_seconds' => $duration,
                ],
                IntegrationLog::INTEGRATION_SUBIEKT_GT,
                (string) $connection->id
            );

            return $results;

        } catch (\Exception $e) {
            IntegrationLog::error(
                'products_pull_linked',
                'Optimized linked products pull failed',
                [
                    'error' => $e->getMessage(),
                ],
                IntegrationLog::INTEGRATION_SUBIEKT_GT,
                (string) $connection->id,
                $e
            );

            return [
                'success' => false,
                'total' => 0,
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'not_found' => 0,
                'errors' => ['Exception: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Sync product stock to Subiekt GT.
     *
     * NOTE: In sql_direct mode, this is read-only - fetches stock FROM Subiekt.
     *
     * @param ERPConnection $connection Active ERP connection
     * @param Product $product Product to sync stock for
     * @return array Sync result
     */
    public function syncStock(ERPConnection $connection, Product $product): array
    {
        try {
            $this->initializeForConnection($connection);

            // Get Subiekt product ID from mapping
            $mapping = $this->getProductMapping($product, $connection);

            if (!$mapping || !$mapping->external_id) {
                return [
                    'success' => false,
                    'message' => 'Produkt nie jest zmapowany do Subiekt GT',
                ];
            }

            $subiektId = (int) $mapping->external_id;
            $config = $connection->connection_config;
            $connectionMode = $config['connection_mode'] ?? 'rest_api';

            // Fetch stock from Subiekt GT based on connection mode
            if ($connectionMode === 'rest_api' && $this->restApiClient !== null) {
                $response = $this->restApiClient->getProductStock($subiektId);
                $stockData = collect($response['data'] ?? $response);
            } elseif ($this->queryBuilder !== null) {
                $stockData = $this->queryBuilder->getProductStock($subiektId);
            } else {
                throw new \RuntimeException('No valid connection method available');
            }

            // Transform and return
            $transformedStock = $this->transformer->transformStock($stockData->toArray());

            // Update ProductErpData with stock info
            $erpData = ProductErpData::where('product_id', $product->id)
                ->where('erp_connection_id', $connection->id)
                ->first();

            if ($erpData) {
                $externalData = $erpData->external_data ?? [];
                $externalData['stock'] = $transformedStock;
                $externalData['stock_updated_at'] = now()->toIso8601String();
                $erpData->update(['external_data' => $externalData]);
            }

            return [
                'success' => true,
                'message' => 'Stan magazynowy pobrany pomyslnie',
                'stock' => $transformedStock,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Blad pobierania stanow: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync product prices to Subiekt GT.
     *
     * NOTE: In sql_direct mode, this fetches prices FROM Subiekt.
     *
     * @param ERPConnection $connection Active ERP connection
     * @param Product $product Product to sync prices for
     * @return array Sync result
     */
    public function syncPrices(ERPConnection $connection, Product $product): array
    {
        try {
            $this->initializeForConnection($connection);

            // Get Subiekt product ID from mapping
            $mapping = $this->getProductMapping($product, $connection);

            if (!$mapping || !$mapping->external_id) {
                return [
                    'success' => false,
                    'message' => 'Produkt nie jest zmapowany do Subiekt GT',
                ];
            }

            $subiektId = (int) $mapping->external_id;
            $config = $connection->connection_config;
            $connectionMode = $config['connection_mode'] ?? 'rest_api';

            // Fetch prices from Subiekt GT based on connection mode
            if ($connectionMode === 'rest_api' && $this->restApiClient !== null) {
                $response = $this->restApiClient->getProductPrices($subiektId);
                $priceData = collect($response['data'] ?? $response);
            } elseif ($this->queryBuilder !== null) {
                $priceData = $this->queryBuilder->getProductPrices($subiektId);
            } else {
                throw new \RuntimeException('No valid connection method available');
            }

            // Transform and return
            $transformedPrices = $this->transformer->transformPrices($priceData->toArray());

            // Update ProductErpData with price info
            $erpData = ProductErpData::where('product_id', $product->id)
                ->where('erp_connection_id', $connection->id)
                ->first();

            if ($erpData) {
                $externalData = $erpData->external_data ?? [];
                $externalData['prices'] = $transformedPrices;
                $externalData['prices_updated_at'] = now()->toIso8601String();
                $erpData->update(['external_data' => $externalData]);
            }

            return [
                'success' => true,
                'message' => 'Ceny pobrane pomyslnie',
                'prices' => $transformedPrices,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Blad pobierania cen: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get ERP type identifier.
     *
     * @return string
     */
    public function getERPType(): string
    {
        return ERPConnection::ERP_SUBIEKT_GT;
    }

    /**
     * Get supported features for Subiekt GT.
     *
     * @return array
     */
    public function getSupportedFeatures(): array
    {
        return [
            'products' => true,
            'stock' => true,
            'prices' => true,
            'categories' => true,
            'manufacturers' => true,
            'orders' => false,           // Not implemented yet
            'invoices' => false,         // Requires Sfera API
            'images' => false,           // Subiekt GT does not store images
            'variants' => false,         // Variants are separate products
            'webhooks' => false,         // Polling required
            'bidirectional_sync' => false, // sql_direct/rest_api = read-only
            'connection_modes' => [
                'sql_direct' => [
                    'name' => 'SQL Direct (Read-Only)',
                    'description' => 'Bezposrednie polaczenie do bazy SQL Server. Tylko odczyt danych. Wymaga dostępu do SQL Server z serwera hostingowego.',
                    'available' => true,
                    'config_fields' => ['db_host', 'db_port', 'db_database', 'db_username', 'db_password'],
                ],
                'rest_api' => [
                    'name' => 'REST API Wrapper',
                    'description' => 'Polaczenie przez REST API uruchomione na serwerze Windows z dostepem do Subiekt GT. Zalecane dla hostingu Linux.',
                    'available' => true,
                    'config_fields' => ['rest_api_url', 'rest_api_key'],
                ],
                'sfera_api' => [
                    'name' => 'Sfera API (COM)',
                    'description' => 'Natywne API Subiekt GT z obsluga zapisu (wymaga Windows Server + licencja Sfera).',
                    'available' => false,
                    'config_fields' => [],
                ],
            ],
        ];
    }

    /**
     * Find product in ERP by SKU.
     *
     * @param ERPConnection $connection Active ERP connection
     * @param string $sku Product SKU to search for
     * @return array{success: bool, found: bool, external_id: ?string, data: ?array, message: string}
     */
    public function findProductBySku(ERPConnection $connection, string $sku): array
    {
        try {
            $config = $connection->connection_config;
            $mode = $config['connection_mode'] ?? 'rest_api';

            if ($mode === 'rest_api') {
                $client = $this->createRestApiClient($config);
                $result = $client->getProductBySku($sku);

                if ($result && isset($result['success']) && $result['success']) {
                    $productData = $result['data'] ?? null;
                    if ($productData) {
                        return [
                            'success' => true,
                            'found' => true,
                            'external_id' => (string) ($productData['id'] ?? $productData['Id'] ?? $productData['tw_Id'] ?? null),
                            'data' => $productData,
                            'message' => "Znaleziono produkt w Subiekt GT: {$sku}",
                        ];
                    }
                }

                return [
                    'success' => true,
                    'found' => false,
                    'external_id' => null,
                    'data' => null,
                    'message' => "Nie znaleziono produktu o SKU: {$sku}",
                ];
            }

            // SQL Direct mode
            $this->configureDynamicConnection($config);
            $product = DB::connection('subiekt_dynamic')
                ->table('tw__Towar')
                ->where('tw_Symbol', $sku)
                ->where('tw_Aktywny', 1)
                ->first();

            if ($product) {
                return [
                    'success' => true,
                    'found' => true,
                    'external_id' => (string) $product->tw_Id,
                    'data' => (array) $product,
                    'message' => "Znaleziono produkt w Subiekt GT: {$sku}",
                ];
            }

            return [
                'success' => true,
                'found' => false,
                'external_id' => null,
                'data' => null,
                'message' => "Nie znaleziono produktu o SKU: {$sku}",
            ];
        } catch (\Exception $e) {
            Log::error('SubiektGTService::findProductBySku error', [
                'sku' => $sku,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'found' => false,
                'external_id' => null,
                'data' => null,
                'message' => 'Błąd wyszukiwania: ' . $e->getMessage(),
            ];
        }
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Configure dynamic database connection for Subiekt GT.
     *
     * @param array $config Connection configuration
     * @return void
     */
    protected function configureConnection(array $config): void
    {
        $connectionConfig = [
            'driver' => 'sqlsrv',
            'host' => $config['db_host'] ?? '(local)\INSERTGT',
            'port' => $config['db_port'] ?? '1433',
            'database' => $config['db_database'] ?? '',
            'username' => $config['db_username'] ?? 'sa',
            'password' => $config['db_password'] ?? '',
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'encrypt' => 'no',
            'trust_server_certificate' => $config['db_trust_certificate'] ?? true,
        ];

        Config::set('database.connections.' . $this->connectionName, $connectionConfig);

        // Purge and reconnect
        DB::purge($this->connectionName);
    }

    /**
     * Initialize query builder and transformer for connection.
     *
     * @param ERPConnection $connection
     * @return void
     */
    protected function initializeForConnection(ERPConnection $connection): void
    {
        $config = $connection->connection_config;
        $connectionMode = $config['connection_mode'] ?? 'rest_api';

        // For REST API mode, initialize REST client instead of direct SQL
        if ($connectionMode === 'rest_api') {
            $this->restApiClient = $this->createRestApiClient($config);
            $this->transformer = new SubiektDataTransformer([
                'warehouse_mappings' => $config['warehouse_mappings'] ?? [],
                'price_group_mappings' => $config['price_group_mappings'] ?? [],
            ]);
            return;
        }

        // SQL Direct mode
        $this->configureConnection($config);

        $this->queryBuilder = new SubiektQueryBuilder($this->connectionName);

        $this->transformer = new SubiektDataTransformer([
            'warehouse_mappings' => $config['warehouse_mappings'] ?? [],
            'price_group_mappings' => $config['price_group_mappings'] ?? [],
        ]);
    }

    /**
     * Create REST API client from configuration.
     *
     * @param array $config Connection configuration
     * @return SubiektRestApiClient
     */
    protected function createRestApiClient(array $config): SubiektRestApiClient
    {
        return new SubiektRestApiClient([
            'base_url' => $config['rest_api_url'] ?? '',
            'api_key' => $config['rest_api_key'] ?? '',
            'timeout' => $config['rest_api_timeout'] ?? 30,
            'connect_timeout' => $config['rest_api_connect_timeout'] ?? 10,
            'retry_times' => $config['rest_api_retry_times'] ?? 3,
            'retry_delay' => $config['rest_api_retry_delay'] ?? 100,
            'verify_ssl' => $config['rest_api_verify_ssl'] ?? true,
        ]);
    }

    /**
     * Find or create PPM product from Subiekt data.
     *
     * @param array $ppmData Transformed product data
     * @param ERPConnection $connection
     * @return Product|null
     */
    protected function findOrCreateProduct(array $ppmData, ERPConnection $connection): ?Product
    {
        $config = $connection->connection_config;
        $createMissing = $config['create_missing_products'] ?? false;

        // First try to find by SKU
        $product = Product::where('sku', $ppmData['sku'])->first();

        if ($product) {
            return $product;
        }

        // Try to find by EAN
        if (!empty($ppmData['ean'])) {
            $product = Product::where('ean', $ppmData['ean'])->first();
            if ($product) {
                return $product;
            }
        }

        // Create new product if allowed
        if ($createMissing && !empty($ppmData['sku'])) {
            return Product::create([
                'sku' => $ppmData['sku'],
                'ean' => $ppmData['ean'] ?? null,
                'name' => $ppmData['name'] ?? 'Imported from Subiekt GT',
                'description' => $ppmData['long_description'] ?? null,
                'weight' => $ppmData['weight'] ?? null,
                'is_active' => $ppmData['is_active'] ?? true,
            ]);
        }

        return null;
    }

    /**
     * Import single product from Subiekt GT data.
     *
     * @param array $ppmData Transformed product data
     * @param ERPConnection $connection
     * @param object $subiektProduct Original Subiekt product
     * @return array Import result
     */
    protected function importProduct(array $ppmData, ERPConnection $connection, object $subiektProduct): array
    {
        $product = $this->findOrCreateProduct($ppmData, $connection);

        if (!$product) {
            return ['created' => false, 'updated' => false, 'skipped' => true];
        }

        $wasCreated = $product->wasRecentlyCreated;

        // Update ProductErpData
        $this->updateProductErpData($product, $connection, $subiektProduct);

        // Update IntegrationMapping
        $this->updateIntegrationMapping($product, $connection, (string) $subiektProduct->id);

        return [
            'created' => $wasCreated,
            'updated' => !$wasCreated,
            'skipped' => false,
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
        ];
    }

    /**
     * Update or create ProductErpData for product.
     *
     * @param Product $product
     * @param ERPConnection $connection
     * @param object $subiektProduct
     * @return ProductErpData
     */
    protected function updateProductErpData(Product $product, ERPConnection $connection, object $subiektProduct): ProductErpData
    {
        $erpDataAttributes = $this->transformer->subiektToProductErpData($subiektProduct, $connection->id);

        return ProductErpData::updateOrCreate(
            [
                'product_id' => $product->id,
                'erp_connection_id' => $connection->id,
            ],
            $erpDataAttributes
        );
    }

    /**
     * Update or create IntegrationMapping for product.
     *
     * @param Product $product
     * @param ERPConnection $connection
     * @param string $externalId Subiekt GT product ID
     * @return IntegrationMapping
     */
    protected function updateIntegrationMapping(Product $product, ERPConnection $connection, string $externalId): IntegrationMapping
    {
        return IntegrationMapping::updateOrCreate(
            [
                'mappable_type' => Product::class,
                'mappable_id' => $product->id,
                'integration_type' => 'subiekt_gt',
                'integration_identifier' => $connection->instance_name,
            ],
            [
                'external_id' => $externalId,
                'external_reference' => $product->sku,
                'sync_status' => 'synced',
                'last_sync_at' => now(),
            ]
        );
    }

    // ========================================================================
    // ETAP_08 FAZA 7: Selective Update Methods for sync_type filtering
    // ========================================================================

    /**
     * Update only product prices from ERP data.
     * Used when sync_type = 'prices'
     *
     * ETAP_08 FAZA 7.3: Smart comparison - update only when PPM != Subiekt
     *
     * @param Product $product PPM product
     * @param array $prices Prices from Subiekt (level => [net, gross])
     * @param ERPConnection $connection
     * @return array ['updated' => bool, 'count' => int, 'skipped' => int, 'changes' => array]
     */
    protected function updateProductPricesFromErp(Product $product, array $prices, ERPConnection $connection): array
    {
        $updated = false;
        $count = 0;
        $skipped = 0;
        $changes = [];

        if (empty($prices)) {
            Log::debug('updateProductPricesFromErp: No prices data', ['sku' => $product->sku]);
            return ['updated' => false, 'count' => 0, 'skipped' => 0, 'changes' => []];
        }

        $config = $connection->connection_config;
        $priceGroupMappings = $config['price_group_mappings'] ?? [];

        if (empty($priceGroupMappings)) {
            Log::debug('updateProductPricesFromErp: No price_group_mappings configured', [
                'sku' => $product->sku,
                'connection_id' => $connection->id,
            ]);
            return ['updated' => false, 'count' => 0, 'skipped' => 0, 'changes' => []];
        }

        // Get current PPM prices for comparison
        $currentPrices = $product->prices()
            ->whereIn('price_group_id', array_values($priceGroupMappings))
            ->get()
            ->keyBy('price_group_id');

        // Update ProductPrice records based on price_group_mappings
        // Only update when values differ!
        foreach ($priceGroupMappings as $subiektLevel => $ppmPriceGroupId) {
            if (!isset($prices[$subiektLevel])) continue;

            $priceData = $prices[$subiektLevel];
            $subiektNetPrice = (float) ($priceData['net'] ?? $priceData['netto'] ?? 0);
            $subiektGrossPrice = (float) ($priceData['gross'] ?? $priceData['brutto'] ?? 0);

            // Calculate missing values
            if ($subiektNetPrice > 0 && $subiektGrossPrice <= 0) {
                $subiektGrossPrice = round($subiektNetPrice * 1.23, 2);
            } elseif ($subiektGrossPrice > 0 && $subiektNetPrice <= 0) {
                $subiektNetPrice = round($subiektGrossPrice / 1.23, 2);
            }

            if ($subiektNetPrice <= 0 && $subiektGrossPrice <= 0) {
                continue; // Skip zero prices
            }

            // Get current PPM price
            $currentPrice = $currentPrices->get($ppmPriceGroupId);
            $ppmNetPrice = $currentPrice ? (float) $currentPrice->price_net : 0;
            $ppmGrossPrice = $currentPrice ? (float) $currentPrice->price_gross : 0;

            // Compare with tolerance (0.01 PLN)
            $netDiff = abs($subiektNetPrice - $ppmNetPrice);
            $grossDiff = abs($subiektGrossPrice - $ppmGrossPrice);

            if ($netDiff < 0.01 && $grossDiff < 0.01) {
                // Prices are the same - skip update
                $skipped++;
                continue;
            }

            // Prices differ - update!
            ProductPrice::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'price_group_id' => $ppmPriceGroupId,
                    'product_variant_id' => null,
                ],
                [
                    'price_net' => $subiektNetPrice,
                    'price_gross' => $subiektGrossPrice,
                    'currency' => 'PLN',
                    'is_active' => true,
                ]
            );
            $count++;
            $updated = true;
            $changes[] = [
                'level' => $subiektLevel,
                'ppm_net' => $ppmNetPrice,
                'subiekt_net' => $subiektNetPrice,
                'diff' => round($netDiff, 2),
            ];
        }

        // Update ProductErpData.external_data.prices only if something changed
        if ($updated) {
            $erpData = $product->erpData()->where('erp_connection_id', $connection->id)->first();
            if ($erpData) {
                $externalData = $erpData->external_data ?? [];
                $externalData['prices'] = $prices;
                $erpData->update([
                    'external_data' => $externalData,
                    'last_pull_at' => now(),
                ]);
            }
        }

        Log::debug('updateProductPricesFromErp: Comparison result', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'updated_count' => $count,
            'skipped_same' => $skipped,
            'changes' => $changes,
        ]);

        return ['updated' => $updated, 'count' => $count, 'skipped' => $skipped, 'changes' => $changes];
    }

    /**
     * Update only product stock from ERP data.
     * Used when sync_type = 'stock'
     *
     * ETAP_08 FAZA 7.3: Smart comparison - update only when PPM != Subiekt
     *
     * @param Product $product PPM product
     * @param array $stockData Stock from Subiekt (warehouse_id => qty or single value)
     * @param ERPConnection $connection
     * @return array ['updated' => bool, 'count' => int, 'skipped' => int, 'changes' => array]
     */
    protected function updateProductStockFromErp(Product $product, $stockData, ERPConnection $connection): array
    {
        $updated = false;
        $count = 0;
        $skipped = 0;
        $changes = [];

        if (empty($stockData)) {
            Log::debug('updateProductStockFromErp: No stock data', ['sku' => $product->sku]);
            return ['updated' => false, 'count' => 0, 'skipped' => 0, 'changes' => []];
        }

        $config = $connection->connection_config;
        $warehouseMappings = $config['warehouse_mappings'] ?? [];
        $defaultWarehouseId = $config['default_warehouse_id'] ?? 1;

        // Get current PPM stock for comparison
        $currentStocks = $product->stocks()->get()->keyBy('warehouse_id');

        // Helper function to compare and update stock
        $compareAndUpdateStock = function ($ppmWarehouseId, $subiektQty) use (
            $product, &$count, &$skipped, &$updated, &$changes, $currentStocks
        ) {
            $subiektQty = (float) $subiektQty;
            $currentStock = $currentStocks->get($ppmWarehouseId);
            $ppmQty = $currentStock ? (float) $currentStock->quantity : 0;

            // Compare with tolerance (0.001 units)
            if (abs($subiektQty - $ppmQty) < 0.001) {
                // Stock is the same - skip update
                $skipped++;
                return;
            }

            // Stock differs - update!
            ProductStock::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $ppmWarehouseId,
                ],
                [
                    'quantity' => $subiektQty,
                    'reserved_quantity' => $currentStock ? $currentStock->reserved_quantity : 0,
                ]
            );
            $count++;
            $updated = true;
            $changes[] = [
                'warehouse_id' => $ppmWarehouseId,
                'ppm_qty' => $ppmQty,
                'subiekt_qty' => $subiektQty,
                'diff' => round($subiektQty - $ppmQty, 2),
            ];
        };

        // Handle simple stock value (single number or array with 'quantity')
        if (is_numeric($stockData)) {
            $ppmWarehouseId = $warehouseMappings[$defaultWarehouseId] ?? 1;
            $compareAndUpdateStock($ppmWarehouseId, $stockData);
        }
        // Handle array with warehouse mappings
        elseif (is_array($stockData)) {
            foreach ($stockData as $warehouseId => $qty) {
                $ppmWarehouseId = $warehouseMappings[$warehouseId] ?? $warehouseId;

                // Extract quantity if it's an array
                $quantity = is_array($qty) ? ($qty['quantity'] ?? $qty['qty'] ?? 0) : $qty;

                $compareAndUpdateStock($ppmWarehouseId, $quantity);
            }
        }

        // Update ProductErpData.external_data.stock only if something changed
        if ($updated) {
            $erpData = $product->erpData()->where('erp_connection_id', $connection->id)->first();
            if ($erpData) {
                $externalData = $erpData->external_data ?? [];
                $externalData['stock'] = $stockData;
                $erpData->update([
                    'external_data' => $externalData,
                    'last_pull_at' => now(),
                ]);
            }
        }

        Log::debug('updateProductStockFromErp: Comparison result', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'updated_count' => $count,
            'skipped_same' => $skipped,
            'changes' => $changes,
        ]);

        return ['updated' => $updated, 'count' => $count, 'skipped' => $skipped, 'changes' => $changes];
    }

    /**
     * Update only basic product data from ERP.
     * Used when sync_type = 'basic_data'
     *
     * @param Product $product PPM product
     * @param object $subiektProduct Subiekt product object
     * @param ERPConnection $connection
     * @return array ['updated' => bool, 'fields' => array]
     */
    protected function updateProductBasicDataFromErp(Product $product, object $subiektProduct, ERPConnection $connection): array
    {
        $updated = false;
        $fields = [];

        // Update ProductErpData with basic fields
        $erpData = $product->erpData()->where('erp_connection_id', $connection->id)->first();
        if ($erpData) {
            $updateData = [];

            // Name
            if (!empty($subiektProduct->name) && $erpData->name !== $subiektProduct->name) {
                $updateData['name'] = $subiektProduct->name;
                $fields[] = 'name';
            }

            // Short description (fiscal name)
            if (isset($subiektProduct->fiscal_name) && $erpData->short_description !== $subiektProduct->fiscal_name) {
                $updateData['short_description'] = $subiektProduct->fiscal_name;
                $fields[] = 'short_description';
            }

            // Long description
            if (isset($subiektProduct->description) && $erpData->long_description !== $subiektProduct->description) {
                $updateData['long_description'] = $subiektProduct->description;
                $fields[] = 'long_description';
            }

            // Weight
            if (isset($subiektProduct->weight) && $erpData->weight != $subiektProduct->weight) {
                $updateData['weight'] = $subiektProduct->weight;
                $fields[] = 'weight';
            }

            // EAN
            if (!empty($subiektProduct->ean) && $erpData->ean !== $subiektProduct->ean) {
                $updateData['ean'] = $subiektProduct->ean;
                $fields[] = 'ean';
            }

            // Is active
            if (isset($subiektProduct->is_active)) {
                $isActive = (bool) $subiektProduct->is_active;
                if ($erpData->is_active !== $isActive) {
                    $updateData['is_active'] = $isActive;
                    $fields[] = 'is_active';
                }
            }

            if (!empty($updateData)) {
                $updateData['last_pull_at'] = now();
                $erpData->update($updateData);
                $updated = true;
            }
        }

        // Extended fields from Subiekt GT (tw_Pole1-5, tw_Uwagi) -> Product model
        $extendedMappings = [
            ['subiekt' => 'Pole1', 'alt' => 'pole1', 'ppm' => 'material'],
            ['subiekt' => 'Pole3', 'alt' => 'pole3', 'ppm' => 'defect_symbol'],
            ['subiekt' => 'Pole4', 'alt' => 'pole4', 'ppm' => 'application'],
            ['subiekt' => 'Pole5', 'alt' => 'pole5', 'ppm' => 'cn_code'],
            ['subiekt' => 'Notes', 'alt' => 'notes', 'ppm' => 'notes'],
            // ETAP_08 FAZA 7: Additional fields from Subiekt GT API
            ['subiekt' => 'ManufacturerName', 'alt' => 'manufacturerName', 'ppm' => 'manufacturer'],
            ['subiekt' => 'SupplierCode', 'alt' => 'supplierCode', 'ppm' => 'supplier_code'],
        ];

        $productUpdateData = [];
        foreach ($extendedMappings as $mapping) {
            $value = $subiektProduct->{$mapping['subiekt']} ?? $subiektProduct->{$mapping['alt']} ?? null;
            if ($value !== null && $product->{$mapping['ppm']} !== $value) {
                $productUpdateData[$mapping['ppm']] = $value;
                $fields[] = $mapping['ppm'];
            }
        }

        // ETAP_08 FAZA 7: Boolean fields from Subiekt GT (tw_SklepInternet, tw_MechanizmPodzielonejPlatnosci)
        $shopInternet = $subiektProduct->ShopInternet ?? $subiektProduct->shopInternet ?? null;
        if ($shopInternet !== null) {
            $shopInternetBool = (bool) $shopInternet;
            if ($product->shop_internet !== $shopInternetBool) {
                $productUpdateData['shop_internet'] = $shopInternetBool;
                $fields[] = 'shop_internet';
            }
        }

        $splitPayment = $subiektProduct->SplitPayment ?? $subiektProduct->splitPayment ?? null;
        if ($splitPayment !== null) {
            $splitPaymentBool = (bool) $splitPayment;
            if ($product->split_payment !== $splitPaymentBool) {
                $productUpdateData['split_payment'] = $splitPaymentBool;
                $fields[] = 'split_payment';
            }
        }

        if (!empty($productUpdateData)) {
            $product->update($productUpdateData);
            $updated = true;
        }

        // ETAP_08 FAZA 8: Parse warehouse locations from tw_Pole2
        // Format: "MAGAZYN1:A-12-3, MAGAZYN2:B-05-1" or simple "A-12-3"
        $pole2Value = $subiektProduct->Pole2 ?? $subiektProduct->pole2 ?? null;
        if (!empty($pole2Value)) {
            $warehouseMappings = $config['warehouse_mappings'] ?? [];
            $defaultWarehouseId = $config['default_warehouse_id'] ?? null;
            $copyLocationToAll = $config['copy_location_to_all'] ?? false;

            $updatedWarehouses = $this->updateStockLocationsFromErp(
                $product,
                $pole2Value,
                $warehouseMappings,
                $defaultWarehouseId,
                $copyLocationToAll
            );

            if (!empty($updatedWarehouses)) {
                $fields[] = 'stock_locations';
                $updated = true;
            }
        }

        Log::debug('updateProductBasicDataFromErp: Updated', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'fields' => $fields,
        ]);

        return ['updated' => $updated, 'fields' => $fields];
    }

    // ========================================================================
    // End of ETAP_08 FAZA 7 selective update methods
    // ========================================================================

    // ========================================================================
    // ETAP_08 FAZA 8: Warehouse Location Parsing
    // ========================================================================

    /**
     * Parse stock locations from Subiekt GT tw_Pole2 field.
     *
     * Supported formats:
     * 1. Multi-warehouse: "MAGAZYN1:A-12-3, MAGAZYN2:B-05-1"
     * 2. Single location (no colon): "AH_02_04" - assigns to Default Warehouse
     *
     * Output: [ppm_warehouse_id => 'location', ...]
     *
     * @param string|null $pole2Csv The tw_Pole2 value from Subiekt GT
     * @param array $warehouseMappings PPM warehouse mappings from ERP config
     * @param int|null $defaultWarehouseId Default warehouse ID from ERP config
     * @param bool $copyToAll If true, copy location to all warehouses (for simple format)
     * @param array $stockData Optional stock data (list of warehouse IDs for copyToAll)
     * @return array Parsed locations keyed by PPM warehouse ID
     */
    protected function parseStockLocationsFromErp(
        ?string $pole2Csv,
        array $warehouseMappings,
        ?int $defaultWarehouseId = null,
        bool $copyToAll = false,
        array $stockData = []
    ): array {
        if (empty($pole2Csv)) {
            return [];
        }

        $locations = [];
        $pole2Csv = trim($pole2Csv);

        // Check if this is a simple location (no colon = assigns to Default Warehouse)
        if (strpos($pole2Csv, ':') === false) {
            Log::debug('[Subiekt] parseStockLocationsFromErp: Simple location format (no colon)', [
                'location' => $pole2Csv,
                'default_warehouse_id' => $defaultWarehouseId,
                'copy_to_all' => $copyToAll,
            ]);

            // Simple format: assign to Default Warehouse
            if ($defaultWarehouseId !== null) {
                $locations[$defaultWarehouseId] = $pole2Csv;

                // If copyToAll is enabled, also assign to all other warehouses
                if ($copyToAll && !empty($stockData)) {
                    foreach (array_keys($stockData) as $warehouseId) {
                        if ($warehouseId !== $defaultWarehouseId) {
                            $locations[$warehouseId] = $pole2Csv;
                        }
                    }
                }
            } else {
                // Fallback: return special key '_default' for caller to handle
                $locations['_default'] = $pole2Csv;
            }

            Log::debug('[Subiekt] Parsed simple location from Pole2', [
                'raw' => $pole2Csv,
                'parsed' => $locations,
            ]);

            return $locations;
        }

        // Multi-warehouse format: "MAGAZYN1:A-12-3, MAGAZYN2:B-05-1"
        $pairs = explode(',', $pole2Csv);

        foreach ($pairs as $pair) {
            $parts = explode(':', trim($pair), 2);
            if (count($parts) !== 2) {
                continue;
            }

            $warehouseName = strtoupper(trim($parts[0]));
            $location = trim($parts[1]);

            if (empty($warehouseName) || empty($location)) {
                continue;
            }

            // Find PPM warehouse ID by Subiekt warehouse name
            $ppmWarehouseId = $this->findPpmWarehouseIdByErpName($warehouseName, $warehouseMappings);

            if ($ppmWarehouseId !== null) {
                $locations[$ppmWarehouseId] = $location;
            }
        }

        Log::debug('[Subiekt] Parsed stock locations from Pole2', [
            'raw' => $pole2Csv,
            'parsed' => $locations,
        ]);

        return $locations;
    }

    /**
     * Find PPM warehouse ID by ERP warehouse name/code.
     *
     * @param string $erpWarehouseName Name/code of warehouse in ERP (e.g., "MPPTRADE", "PITBIKE")
     * @param array $warehouseMappings Warehouse mappings from ERP config
     * @return int|null PPM warehouse ID or null if not found
     */
    protected function findPpmWarehouseIdByErpName(string $erpWarehouseName, array $warehouseMappings): ?int
    {
        // Normalize input
        $erpWarehouseName = strtoupper(trim($erpWarehouseName));

        // Search in warehouse mappings
        foreach ($warehouseMappings as $mapping) {
            // Check by ERP warehouse name/code
            $mappedErpName = strtoupper(trim($mapping['erp_name'] ?? $mapping['subiekt_name'] ?? ''));
            if ($mappedErpName === $erpWarehouseName) {
                return (int) ($mapping['ppm_id'] ?? $mapping['warehouse_id'] ?? null);
            }

            // Check by ERP warehouse symbol
            $mappedErpSymbol = strtoupper(trim($mapping['erp_symbol'] ?? $mapping['subiekt_symbol'] ?? ''));
            if (!empty($mappedErpSymbol) && $mappedErpSymbol === $erpWarehouseName) {
                return (int) ($mapping['ppm_id'] ?? $mapping['warehouse_id'] ?? null);
            }
        }

        // Fallback: Try to match by PPM warehouse name (direct match)
        $warehouse = \App\Models\Warehouse::whereRaw('UPPER(name) = ?', [$erpWarehouseName])
            ->orWhereRaw('UPPER(code) = ?', [$erpWarehouseName])
            ->first();

        if ($warehouse) {
            return $warehouse->id;
        }

        Log::warning('[Subiekt] Could not find PPM warehouse for ERP name', [
            'erp_name' => $erpWarehouseName,
            'available_mappings' => count($warehouseMappings),
        ]);

        return null;
    }

    /**
     * Update product stock locations from Subiekt GT Pole2 field.
     *
     * @param Product $product
     * @param string|null $pole2Value
     * @param array $warehouseMappings
     * @param int|null $defaultWarehouseId Default warehouse ID from ERP config
     * @param bool $copyToAll If true, copy location to all warehouses
     * @return array List of updated warehouse IDs
     */
    public function updateStockLocationsFromErp(
        Product $product,
        ?string $pole2Value,
        array $warehouseMappings = [],
        ?int $defaultWarehouseId = null,
        bool $copyToAll = false
    ): array {
        // Get existing stock entries for copyToAll functionality
        $existingStock = $product->stock()->pluck('warehouse_id')->toArray();
        $stockData = array_fill_keys($existingStock, ['quantity' => 0]);

        $locations = $this->parseStockLocationsFromErp(
            $pole2Value,
            $warehouseMappings,
            $defaultWarehouseId,
            $copyToAll,
            $stockData
        );

        // Handle '_default' fallback
        if (isset($locations['_default'])) {
            $defaultLocation = $locations['_default'];
            unset($locations['_default']);
            if ($defaultWarehouseId !== null) {
                $locations[$defaultWarehouseId] = $defaultLocation;
                if ($copyToAll) {
                    foreach ($existingStock as $warehouseId) {
                        if ($warehouseId !== $defaultWarehouseId) {
                            $locations[$warehouseId] = $defaultLocation;
                        }
                    }
                }
            }
        }

        if (empty($locations)) {
            return [];
        }

        $updatedWarehouses = [];

        foreach ($locations as $ppmWarehouseId => $location) {
            $updated = \App\Models\ProductStock::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $ppmWarehouseId,
                    'product_variant_id' => null, // Master product stock
                ],
                [
                    'location' => $location,
                    'is_active' => true,
                ]
            );

            if ($updated->wasRecentlyCreated || $updated->wasChanged()) {
                $updatedWarehouses[] = $ppmWarehouseId;
            }
        }

        Log::info('[Subiekt] Updated stock locations from Pole2', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'default_warehouse_id' => $defaultWarehouseId,
            'copy_to_all' => $copyToAll,
            'updated_warehouses' => $updatedWarehouses,
            'locations' => $locations,
        ]);

        return $updatedWarehouses;
    }

    // ========================================================================
    // End of ETAP_08 FAZA 8
    // ========================================================================

    /**
     * Get product mapping for Subiekt GT.
     *
     * @param Product $product
     * @param ERPConnection $connection
     * @return IntegrationMapping|null
     */
    protected function getProductMapping(Product $product, ERPConnection $connection): ?IntegrationMapping
    {
        return IntegrationMapping::where('mappable_type', Product::class)
            ->where('mappable_id', $product->id)
            ->where('integration_type', 'subiekt_gt')
            ->where('integration_identifier', $connection->instance_name)
            ->first();
    }

    /**
     * Validate product for Subiekt GT (sql_direct mode).
     *
     * @param ERPConnection $connection
     * @param Product $product
     * @return array Validation result
     */
    protected function validateProductForSubiekt(ERPConnection $connection, Product $product): array
    {
        try {
            $this->initializeForConnection($connection);

            // Check if product exists in Subiekt by SKU
            $subiektProduct = $this->queryBuilder->getProductBySKU($product->sku);

            if ($subiektProduct) {
                // Product exists - update mapping
                $this->updateIntegrationMapping($product, $connection, (string) $subiektProduct->id);
                $this->updateProductErpData($product, $connection, $subiektProduct);

                return [
                    'success' => true,
                    'message' => 'Produkt znaleziony w Subiekt GT i zmapowany',
                    'external_id' => (string) $subiektProduct->id,
                    'action' => 'mapped',
                ];
            }

            return [
                'success' => false,
                'message' => 'Produkt nie istnieje w Subiekt GT. Tryb sql_direct nie pozwala na tworzenie produktow.',
                'external_id' => null,
                'action' => 'not_found',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Blad walidacji: ' . $e->getMessage(),
                'external_id' => null,
            ];
        }
    }

    /**
     * Validate all products for Subiekt GT (sql_direct mode).
     *
     * @param ERPConnection $connection
     * @param array $filters
     * @return array Validation results
     */
    protected function validateAllProductsForSubiekt(ERPConnection $connection, array $filters = []): array
    {
        $results = [
            'success' => true,
            'total' => 0,
            'synced' => 0,
            'failed' => 0,
            'not_found' => 0,
            'errors' => [],
        ];

        $query = Product::where('is_active', true);

        if (!empty($filters['product_ids'])) {
            $query->whereIn('id', $filters['product_ids']);
        }

        $products = $query->get();
        $results['total'] = $products->count();

        foreach ($products as $product) {
            $result = $this->validateProductForSubiekt($connection, $product);

            if ($result['success']) {
                $results['synced']++;
            } elseif ($result['action'] ?? '' === 'not_found') {
                $results['not_found']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'product_sku' => $product->sku,
                    'error' => $result['message'],
                ];
            }
        }

        return $results;
    }

    /**
     * Sync product via REST API.
     *
     * In REST API mode, we can:
     * - Read product data from Subiekt GT
     * - Map existing products by SKU
     * - UPDATE existing products (basic fields + prices if Sfera enabled)
     * - CREATE new products (requires Sfera enabled on API server)
     *
     * @param ERPConnection $connection
     * @param Product $product
     * @param array $syncOptions Optional sync options for selective field synchronization
     * @return array
     */
    protected function syncProductViaRestApi(ERPConnection $connection, Product $product, array $syncOptions = []): array
    {
        try {
            $config = $connection->connection_config;

            // CRITICAL FIX: Reconstruct mappings from PriceGroup/Warehouse models
            // because connection_config may not have them (they're stored in model.erp_mapping)
            $config = $this->enrichConfigWithMappingsFromModels($config, $connection->erp_type);

            // Merge syncOptions into config for downstream methods
            $config['_sync_options'] = $syncOptions;

            $client = $this->createRestApiClient($config);
            $syncDirection = $config['sync_direction'] ?? 'pull'; // pull, push, bidirectional

            // Try to find product in Subiekt GT by SKU
            try {
                $response = $client->getProductBySku($product->sku);
                $subiektProduct = $response['data'] ?? null;
            } catch (SubiektApiException $e) {
                if ($e->isNotFound()) {
                    $subiektProduct = null;
                } else {
                    throw $e;
                }
            }

            // === PRODUCT EXISTS IN SUBIEKT GT ===
            if ($subiektProduct) {
                $externalId = (string) ($subiektProduct['Id'] ?? $subiektProduct['id'] ?? null);

                // Convert to object if array
                if (is_array($subiektProduct)) {
                    $subiektProduct = (object) $subiektProduct;
                }

                // Check if we should push changes to Subiekt GT
                $shouldPush = in_array($syncDirection, ['push', 'bidirectional']);

                if ($shouldPush) {
                    // Try to update product in Subiekt GT
                    $updateResult = $this->pushProductToSubiekt($client, $product, $externalId, $config, $connection->id);

                    if ($updateResult['success']) {
                        // Update mapping
                        $this->updateIntegrationMapping($product, $connection, $externalId);
                        $this->updateProductErpDataFromRestApi($product, $connection, $subiektProduct);

                        IntegrationLog::info(
                            'product_sync',
                            'Product updated in Subiekt GT via REST API',
                            [
                                'product_sku' => $product->sku,
                                'subiekt_id' => $externalId,
                                'action' => $updateResult['action'] ?? 'updated',
                            ],
                            IntegrationLog::INTEGRATION_SUBIEKT_GT,
                            (string) $connection->id
                        );

                        // === SYNC VARIANTS IF PRODUCT IS VARIANT MASTER ===
                        $variantsSynced = 0;
                        $variantsFailed = 0;
                        if ($product->is_variant_master && $product->variants()->count() > 0) {
                            Log::info('SubiektGTService: Product is variant master, syncing variants', [
                                'product_id' => $product->id,
                                'sku' => $product->sku,
                                'variant_count' => $product->variants()->count(),
                            ]);

                            $variantResult = $this->syncProductVariantsToSubiekt($connection, $product);
                            $variantsSynced = $variantResult['synced'] ?? 0;
                            $variantsFailed = $variantResult['failed'] ?? 0;

                            Log::info('SubiektGTService: Variants sync completed', [
                                'product_id' => $product->id,
                                'synced' => $variantsSynced,
                                'failed' => $variantsFailed,
                            ]);
                        }

                        return [
                            'success' => true,
                            'message' => $updateResult['message'] ?? 'Produkt zaktualizowany w Subiekt GT',
                            'external_id' => $externalId,
                            'action' => $updateResult['action'] ?? 'updated',
                            'rows_affected' => $updateResult['rows_affected'] ?? null,
                            'updated_fields' => $updateResult['updated_fields'] ?? [],
                            'prices_updated' => $updateResult['prices_updated'] ?? 0,
                            'sku' => $product->sku,
                            'variants_synced' => $variantsSynced,
                            'variants_failed' => $variantsFailed,
                        ];
                    }

                    // Update failed but we can still map
                    Log::warning('SubiektGTService: Update failed, falling back to mapping only', [
                        'sku' => $product->sku,
                        'error' => $updateResult['message'] ?? 'Unknown error',
                    ]);
                }

                // Just map (pull mode or update failed)
                $this->updateIntegrationMapping($product, $connection, $externalId);
                $this->updateProductErpDataFromRestApi($product, $connection, $subiektProduct);

                IntegrationLog::info(
                    'product_sync',
                    'Product mapped via REST API',
                    [
                        'product_sku' => $product->sku,
                        'subiekt_id' => $externalId,
                    ],
                    IntegrationLog::INTEGRATION_SUBIEKT_GT,
                    (string) $connection->id
                );

                return [
                    'success' => true,
                    'message' => 'Produkt znaleziony w Subiekt GT i zmapowany',
                    'external_id' => $externalId,
                    'action' => 'mapped',
                ];
            }

            // === PRODUCT NOT FOUND IN SUBIEKT GT ===
            // Check if we should create it
            $shouldCreate = in_array($syncDirection, ['push', 'bidirectional'])
                && ($config['create_in_erp'] ?? false);

            if ($shouldCreate) {
                $createResult = $this->createProductInSubiekt($client, $product, $config, $connection->id);

                if ($createResult['success']) {
                    $externalId = (string) ($createResult['external_id'] ?? $createResult['product_id'] ?? null);

                    // Create mapping
                    if ($externalId) {
                        $this->updateIntegrationMapping($product, $connection, $externalId);

                        // Fetch fresh data from Subiekt GT
                        try {
                            $freshResponse = $client->getProductById((int) $externalId);
                            $freshProduct = (object) ($freshResponse['data'] ?? []);
                            $this->updateProductErpDataFromRestApi($product, $connection, $freshProduct);
                        } catch (\Exception $e) {
                            Log::warning('SubiektGTService: Failed to fetch fresh data after create', [
                                'sku' => $product->sku,
                                'external_id' => $externalId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    IntegrationLog::info(
                        'product_sync',
                        'Product created in Subiekt GT via REST API',
                        [
                            'product_sku' => $product->sku,
                            'subiekt_id' => $externalId,
                        ],
                        IntegrationLog::INTEGRATION_SUBIEKT_GT,
                        (string) $connection->id
                    );

                    return [
                        'success' => true,
                        'message' => $createResult['message'] ?? 'Produkt utworzony w Subiekt GT',
                        'external_id' => $externalId,
                        'action' => 'created',
                    ];
                }

                // Create failed
                return [
                    'success' => false,
                    'message' => $createResult['message'] ?? 'Nie udalo sie utworzyc produktu w Subiekt GT',
                    'external_id' => null,
                    'action' => 'create_failed',
                    'error_code' => $createResult['error_code'] ?? null,
                ];
            }

            // Product not found and we're not configured to create
            return [
                'success' => false,
                'message' => 'Produkt nie istnieje w Subiekt GT. Tworzenie produktow wymaga wlaczenia opcji "create_in_erp".',
                'external_id' => null,
                'action' => 'not_found',
            ];

        } catch (SubiektApiException $e) {
            IntegrationLog::error(
                'product_sync',
                'REST API sync failed',
                [
                    'product_sku' => $product->sku,
                    'error' => $e->getMessage(),
                    'http_status' => $e->getHttpStatusCode(),
                ],
                IntegrationLog::INTEGRATION_SUBIEKT_GT,
                (string) $connection->id,
                $e
            );

            return [
                'success' => false,
                'message' => 'Blad REST API: ' . $e->getMessage(),
                'external_id' => null,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Blad synchronizacji: ' . $e->getMessage(),
                'external_id' => null,
            ];
        }
    }

    /**
     * Push product data TO Subiekt GT (UPDATE).
     *
     * @param SubiektRestApiClient $client
     * @param Product $product
     * @param string $externalId Subiekt GT product ID
     * @param array $config Connection config
     * @return array
     */
    protected function pushProductToSubiekt(SubiektRestApiClient $client, Product $product, string $externalId, array $config, ?int $connectionId = null): array
    {
        try {
            // Get sync options from config (passed from syncProductViaRestApi)
            $syncOptions = $config['_sync_options'] ?? [];

            // Build update data from PPM product (using ERP data if available)
            $updateData = $this->mapPpmProductToSubiekt($product, $config, false, $connectionId);

            // ====== SELECTIVE SYNC: Filter based on syncOptions ======
            // If sync_stock is explicitly false, don't sync stock at all
            if (isset($syncOptions['sync_stock']) && $syncOptions['sync_stock'] === false) {
                unset($updateData['stock']);
                Log::debug('pushProductToSubiekt: Stock sync disabled by syncOptions');
            }

            // If sync_prices is explicitly false, don't sync prices at all
            if (isset($syncOptions['sync_prices']) && $syncOptions['sync_prices'] === false) {
                unset($updateData['prices']);
                Log::debug('pushProductToSubiekt: Price sync disabled by syncOptions');
            }

            // If stock_columns are specified, filter stock data to only include those columns
            if (!empty($syncOptions['stock_columns']) && isset($updateData['stock'])) {
                $allowedColumns = $syncOptions['stock_columns'];
                $columnMapping = [
                    'quantity' => 'quantity',
                    'minimum' => 'min',
                    'reserved' => 'reserved',
                ];

                foreach ($updateData['stock'] as $warehouseId => &$stockEntry) {
                    foreach (array_keys($stockEntry) as $key) {
                        // Find if this key corresponds to an allowed column
                        $isAllowed = false;
                        foreach ($allowedColumns as $allowedCol) {
                            if (isset($columnMapping[$allowedCol]) && $columnMapping[$allowedCol] === $key) {
                                $isAllowed = true;
                                break;
                            }
                        }
                        if (!$isAllowed && $key !== 'max') {
                            unset($stockEntry[$key]);
                        }
                    }
                }

                Log::debug('pushProductToSubiekt: Stock columns filtered by syncOptions', [
                    'allowed_columns' => $allowedColumns,
                    'filtered_stock' => $updateData['stock'],
                ]);
            }

            // DEBUG: Log mapped data before sending to API
            Log::debug('pushProductToSubiekt: Mapped data for Subiekt GT', [
                'sku' => $product->sku,
                'external_id' => $externalId,
                'mapped_fields' => array_keys($updateData),
                'mapped_data' => $updateData,
                'sync_options' => $syncOptions,
            ]);

            if (empty($updateData)) {
                Log::debug('pushProductToSubiekt: No data to send', [
                    'sku' => $product->sku,
                ]);
                return [
                    'success' => true,
                    'message' => 'Brak zmian do wyslania',
                    'action' => 'no_changes',
                ];
            }

            Log::info('SubiektGTService: Pushing product to Subiekt GT', [
                'sku' => $product->sku,
                'external_id' => $externalId,
                'fields' => array_keys($updateData),
                'sync_options' => $syncOptions,
            ]);

            // ====== UPDATE PRODUCT (name, description, prices, etc.) ======
            // Extract stock from updateData - it goes to separate API endpoint
            $stockData = $updateData['stock'] ?? [];
            unset($updateData['stock']); // Don't send stock to product update endpoint

            $productResult = null;
            $stockResult = null;

            // 1. Update product basic fields and prices
            if (!empty($updateData)) {
                $productResult = $client->updateProductBySku($product->sku, $updateData);

                Log::debug('pushProductToSubiekt: Product update API response', [
                    'sku' => $product->sku,
                    'success' => $productResult['success'] ?? false,
                    'result' => $productResult,
                ]);
            }

            // 2. Update stock (separate endpoint) - ONLY if we have stock data after filtering
            if (!empty($stockData)) {
                $stockResult = $client->updateProductStockBySku($product->sku, $stockData);

                Log::debug('pushProductToSubiekt: Stock update API response', [
                    'sku' => $product->sku,
                    'success' => $stockResult['success'] ?? false,
                    'result' => $stockResult,
                ]);
            }

            // Combine results
            $productSuccess = empty($updateData) || ($productResult['success'] ?? false);
            $stockSuccess = empty($stockData) || ($stockResult['success'] ?? false);

            if ($productSuccess && $stockSuccess) {
                $rowsAffected = ($productResult['data']['rows_affected'] ?? 0) + ($stockResult['data']['rows_affected'] ?? 0);
                $messages = [];

                if (!empty($updateData)) {
                    $messages[] = $productResult['data']['message'] ?? 'Produkt zaktualizowany';
                }
                if (!empty($stockData)) {
                    $messages[] = $stockResult['data']['message'] ?? 'Stany zaktualizowane';
                }

                return [
                    'success' => true,
                    'message' => implode('. ', $messages) ?: 'Zaktualizowano',
                    'action' => 'updated',
                    'rows_affected' => $rowsAffected,
                    'updated_fields' => array_keys($updateData),
                    'prices_updated' => count($updateData['prices'] ?? []),
                    'stock_updated' => count($stockData),
                ];
            }

            // Error handling - combine errors from both operations
            $errors = [];

            if (!$productSuccess && $productResult) {
                $errors[] = $productResult['error'] ?? 'Blad aktualizacji produktu';
            }
            if (!$stockSuccess && $stockResult) {
                $errors[] = $stockResult['error'] ?? 'Blad aktualizacji stanow';
            }

            // Check for specific error codes
            $errorCode = $productResult['error_code'] ?? $stockResult['error_code'] ?? null;

            // SFERA_REQUIRED means server doesn't have Sfera enabled
            if ($errorCode === 'SFERA_REQUIRED') {
                Log::info('SubiektGTService: Sfera not available, using DirectSQL fallback');
            }

            return [
                'success' => false,
                'message' => implode('. ', $errors) ?: 'Blad aktualizacji',
                'error_code' => $errorCode,
            ];

        } catch (SubiektApiException $e) {
            return [
                'success' => false,
                'message' => 'Blad API: ' . $e->getMessage(),
                'error_code' => 'API_ERROR',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Blad: ' . $e->getMessage(),
                'error_code' => 'UNKNOWN',
            ];
        }
    }

    /**
     * Create product IN Subiekt GT (POST).
     *
     * @param SubiektRestApiClient $client
     * @param Product $product
     * @param array $config Connection config
     * @return array
     */
    protected function createProductInSubiekt(SubiektRestApiClient $client, Product $product, array $config, ?int $connectionId = null): array
    {
        try {
            // NOTE: DirectSQL now supports CREATE operations without Sfera!
            // Removed Sfera health check - let the API endpoint handle it.

            // Build create data from PPM product (using ERP data if available)
            $createData = $this->mapPpmProductToSubiekt($product, $config, true, $connectionId);

            if (empty($createData['sku'])) {
                return [
                    'success' => false,
                    'message' => 'SKU jest wymagane do utworzenia produktu',
                    'error_code' => 'VALIDATION_ERROR',
                ];
            }

            Log::info('SubiektGTService: Creating product in Subiekt GT', [
                'sku' => $product->sku,
                'name' => $product->name,
            ]);

            // Use POST endpoint
            $result = $client->createProduct($createData);

            if ($result['success'] ?? false) {
                return [
                    'success' => true,
                    'message' => $result['data']['message'] ?? 'Produkt utworzony',
                    'external_id' => $result['data']['product_id'] ?? null,
                    'product_id' => $result['data']['product_id'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => $result['error'] ?? 'Blad tworzenia produktu',
                'error_code' => $result['error_code'] ?? null,
            ];

        } catch (SubiektApiException $e) {
            return [
                'success' => false,
                'message' => 'Blad API: ' . $e->getMessage(),
                'error_code' => 'API_ERROR',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Blad: ' . $e->getMessage(),
                'error_code' => 'UNKNOWN',
            ];
        }
    }

    /**
     * Map PPM Product to Subiekt GT format.
     *
     * @param Product $product
     * @param array $config Connection config (for price/warehouse mappings)
     * @param bool $isCreate Whether this is for create (include SKU)
     * @return array Data for Subiekt GT API
     */
    protected function mapPpmProductToSubiekt(Product $product, array $config, bool $isCreate = false, ?int $connectionId = null): array
    {
        $data = [];

        // FIX: Load ProductErpData to get ERP-specific field values (edited in ERP TAB)
        $erpData = null;
        if ($connectionId) {
            $erpData = $product->erpData()
                ->where('erp_connection_id', $connectionId)
                ->first();
        }

        // Helper function to get value from ERP data or fallback to product
        $getValue = function(string $erpField, ?string $productField = null) use ($erpData, $product) {
            $productField = $productField ?? $erpField;
            // Prefer ERP data value if exists and not empty
            if ($erpData && !empty($erpData->$erpField)) {
                return $erpData->$erpField;
            }
            return $product->$productField ?? null;
        };

        // DEBUG: Log input data
        Log::debug('mapPpmProductToSubiekt: Starting mapping', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'product_name' => $product->name,
            'erp_data_name' => $erpData?->name ?? 'NO_ERP_DATA',
            'using_erp_data' => $erpData !== null,
            'is_create' => $isCreate,
            'config_price_mappings' => $config['price_group_mappings'] ?? [],
            'config_warehouse_mappings' => $config['warehouse_mappings'] ?? [],
        ]);

        // SKU (only for create)
        if ($isCreate) {
            $data['sku'] = $product->sku;
        }

        // Basic fields - USE ERP DATA IF AVAILABLE
        $name = $getValue('name');
        if (!empty($name)) {
            $data['name'] = mb_substr($name, 0, 50); // Subiekt limit
        }

        $description = $getValue('long_description', 'description');
        if ($description !== null) {
            // Subiekt GT tw_Opis = varchar(255), strip HTML and truncate
            $plainDescription = strip_tags($description);
            $plainDescription = html_entity_decode($plainDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $plainDescription = preg_replace('/\s+/', ' ', $plainDescription); // Normalize whitespace
            $plainDescription = trim($plainDescription);

            if (mb_strlen($plainDescription) > 255) {
                Log::debug('mapPpmProductToSubiekt: Description truncated', [
                    'sku' => $product->sku,
                    'original_length' => mb_strlen($plainDescription),
                    'truncated_to' => 255,
                ]);
                $plainDescription = mb_substr($plainDescription, 0, 252) . '...';
            }

            $data['description'] = $plainDescription;
        }

        $ean = $getValue('ean');
        if (!empty($ean)) {
            $data['ean'] = mb_substr($ean, 0, 20);
        }

        $weight = $getValue('weight');
        if ($weight !== null && $weight > 0) {
            $data['weight'] = (float) $weight;
        }

        // Unit mapping
        $unitMapping = $config['unit_mapping'] ?? [];
        if (!empty($product->unit)) {
            $data['unit'] = $unitMapping[$product->unit] ?? $product->unit;
        }

        // VAT rate mapping
        $vatRateMapping = $config['vat_rate_mapping'] ?? [];
        if (!empty($product->tax_rate) && isset($vatRateMapping[$product->tax_rate])) {
            $data['vat_rate_id'] = (int) $vatRateMapping[$product->tax_rate];
        }

        // Price group mapping - map PPM price groups to Subiekt price levels
        // IMPORTANT: Config stores ERP_LEVEL => PPM_GROUP (for PULL direction)
        // For PUSH we need PPM_GROUP => ERP_LEVEL (inverted)
        $erpToPpmMapping = $config['price_group_mappings'] ?? [];
        $ppmToErpMapping = array_flip($erpToPpmMapping); // Invert for PUSH direction
        $prices = [];

        Log::debug('mapPpmProductToSubiekt: Price mapping', [
            'erpToPpmMapping' => $erpToPpmMapping,
            'ppmToErpMapping' => $ppmToErpMapping,
        ]);

        // Get product prices from PPM
        $productPrices = $product->prices ?? [];
        if ($productPrices instanceof \Illuminate\Database\Eloquent\Collection) {
            $productPrices = $productPrices->toArray();
        }

        foreach ($productPrices as $priceData) {
            $ppmGroupId = $priceData['price_group_id'] ?? $priceData['group_id'] ?? null;
            if ($ppmGroupId && isset($ppmToErpMapping[$ppmGroupId])) {
                $subiektLevel = (int) $ppmToErpMapping[$ppmGroupId];
                $prices[$subiektLevel] = [
                    'net' => (float) ($priceData['price_net'] ?? $priceData['price'] ?? 0),
                    'gross' => isset($priceData['price_gross']) ? (float) $priceData['price_gross'] : null,
                ];
                Log::debug('mapPpmProductToSubiekt: Mapped price', [
                    'ppmGroupId' => $ppmGroupId,
                    'subiektLevel' => $subiektLevel,
                    'net' => $prices[$subiektLevel]['net'],
                ]);
            }
        }

        // Also map base price if available
        // IMPORTANT: Level 0 is UNUSED in Subiekt GT with price groups - use level 1 as default
        if (!empty($product->price_net)) {
            $defaultPriceLevel = $config['default_price_level'] ?? 1; // Changed: 0 → 1 (level 0 is unused)
            if (!isset($prices[$defaultPriceLevel])) {
                $prices[$defaultPriceLevel] = [
                    'net' => (float) $product->price_net,
                    'gross' => !empty($product->price_gross) ? (float) $product->price_gross : null,
                ];
            }
        }

        if (!empty($prices)) {
            $data['prices'] = $prices;
        }

        // Active status
        if (isset($product->is_active)) {
            $data['is_active'] = (bool) $product->is_active;
        }

        // ====== STOCK MAPPING ======
        // Maps PPM warehouse IDs to Subiekt GT warehouse IDs
        // Config stores: ERP_WAREHOUSE_ID => PPM_WAREHOUSE_ID (for PULL)
        // For PUSH we need: PPM_WAREHOUSE_ID => ERP_WAREHOUSE_ID (inverted)
        $erpToPpmWarehouseMapping = $config['warehouse_mappings'] ?? [];
        $ppmToErpWarehouseMapping = array_flip($erpToPpmWarehouseMapping);
        $stock = [];

        // Get product stock from relationship (via ProductStock model)
        // FIX: Use $product->stock instead of $product->warehouses
        $productStock = $product->stock ?? collect();
        if ($productStock instanceof \Illuminate\Database\Eloquent\Collection) {
            $productStock = $productStock->toArray();
        }

        Log::debug('mapPpmProductToSubiekt: Stock mapping', [
            'erpToPpmWarehouseMapping' => $erpToPpmWarehouseMapping,
            'ppmToErpWarehouseMapping' => $ppmToErpWarehouseMapping,
            'productStock_count' => count($productStock),
        ]);

        foreach ($productStock as $stockData) {
            // ProductStock model data
            $ppmWarehouseId = $stockData['warehouse_id'] ?? null;

            if ($ppmWarehouseId && isset($ppmToErpWarehouseMapping[$ppmWarehouseId])) {
                $erpWarehouseId = (int) $ppmToErpWarehouseMapping[$ppmWarehouseId];

                // Get stock values from ProductStock model fields
                $quantity = (float) ($stockData['quantity'] ?? 0);
                $minimum = (float) ($stockData['minimum_stock'] ?? $stockData['minimum'] ?? 0);
                $maximum = (float) ($stockData['maximum_stock'] ?? $stockData['maximum'] ?? 0);

                $stock[$erpWarehouseId] = [
                    'quantity' => $quantity,
                    'min' => $minimum,
                    'max' => $maximum,
                ];

                Log::debug('mapPpmProductToSubiekt: Mapped stock', [
                    'ppmWarehouseId' => $ppmWarehouseId,
                    'erpWarehouseId' => $erpWarehouseId,
                    'quantity' => $quantity,
                    'min' => $minimum,
                    'max' => $maximum,
                ]);
            }
        }

        if (!empty($stock)) {
            $data['stock'] = $stock;

            // ====== PRODUCT-LEVEL MINIMUM STOCK (tw_StanMin) ======
            // In Subiekt GT, tw__Towar.tw_StanMin is GLOBAL for all warehouses.
            // PPM stores minimum per warehouse, so we send the LOWEST value.
            // This ensures Subiekt GT alerts when ANY warehouse falls below minimum.
            $allMinimums = array_filter(array_column($stock, 'min'), fn($v) => $v > 0);
            if (!empty($allMinimums)) {
                $lowestMinimum = min($allMinimums);
                $data['minimum_stock'] = $lowestMinimum;
                $data['minimum_stock_unit'] = $product->unit ?? 'szt.';

                Log::debug('mapPpmProductToSubiekt: Product-level minimum stock', [
                    'all_warehouse_minimums' => $allMinimums,
                    'lowest_minimum' => $lowestMinimum,
                    'unit' => $data['minimum_stock_unit'],
                ]);
            }
        }

        // ====== EXTENDED FIELDS MAPPING (ETAP_08 FAZA 3.4) ======
        // Map Subiekt GT extended fields: tw_SklepInternet, tw_MechanizmPodzielonejPlatnosci,
        // tw_Pole1-5, tw_DostSymbol, tw_IdPodstDostawca
        $extendedFields = $this->mapExtendedFields($product, $config);
        if (!empty($extendedFields)) {
            $data = array_merge($data, $extendedFields);

            Log::debug('mapPpmProductToSubiekt: Extended fields mapped', [
                'sku' => $product->sku,
                'extended_fields' => array_keys($extendedFields),
            ]);
        }

        // ====== STOCK LOCATIONS MAPPING (tw_Pole2 = CSV of locations) ======
        $stockLocations = $this->mapStockLocations($product, $config);
        if (!empty($stockLocations)) {
            $data['stock_location'] = $stockLocations;

            Log::debug('mapPpmProductToSubiekt: Stock locations mapped', [
                'sku' => $product->sku,
                'locations' => $stockLocations,
            ]);
        }

        // DEBUG: Log final mapped data
        Log::debug('mapPpmProductToSubiekt: Final mapped data', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'mapped_fields' => array_keys($data),
            'prices_count' => count($data['prices'] ?? []),
            'stock_count' => count($data['stock'] ?? []),
            'has_extended_fields' => !empty($extendedFields),
            'data' => $data,
        ]);

        return $data;
    }

    /**
     * Map extended fields from PPM Product to Subiekt GT format.
     *
     * ETAP_08 FAZA 3.4: New field mappings
     *
     * | PPM Field       | Subiekt GT Field                  | Type    |
     * |-----------------|-----------------------------------|---------|
     * | shop_internet   | tw_SklepInternet                 | bit     |
     * | split_payment   | tw_MechanizmPodzielonejPlatnosci | bit     |
     * | material        | tw_Pole1                         | varchar |
     * | defect_symbol   | tw_Pole3                         | varchar |
     * | application     | tw_Pole4                         | varchar |
     * | cn_code         | tw_Pole5                         | varchar |
     * | supplier_code   | tw_DostSymbol                    | varchar |
     * | manufacturer    | tw_IdPodstDostawca               | int FK  |
     *
     * @param Product $product
     * @param array $config Connection config (for manufacturer mappings)
     * @return array Extended fields data for Subiekt GT API
     */
    protected function mapExtendedFields(Product $product, array $config): array
    {
        $data = [];

        // Boolean flags
        // tw_SklepInternet - Internet shop visibility
        if ($product->shop_internet !== null) {
            $data['shop_internet'] = $product->shop_internet ? 1 : 0;
        }

        // tw_MechanizmPodzielonejPlatnosci - Split payment mechanism (MPP)
        if ($product->split_payment !== null) {
            $data['split_payment'] = $product->split_payment ? 1 : 0;
        }

        // Custom fields (tw_Pole1-5) - max 50 chars each
        // tw_Pole1 = Material
        if (!empty($product->material)) {
            $data['pole1'] = mb_substr($product->material, 0, 50);
        }

        // tw_Pole3 = Defect symbol
        if (!empty($product->defect_symbol)) {
            $data['pole3'] = mb_substr($product->defect_symbol, 0, 50);
        }

        // tw_Pole4 = Application
        if (!empty($product->application)) {
            $data['pole4'] = mb_substr($product->application, 0, 50);
        }

        // tw_Pole5 = CN Code (Combined Nomenclature for customs)
        if (!empty($product->cn_code)) {
            $data['pole5'] = mb_substr($product->cn_code, 0, 50);
        }

        // tw_DostSymbol = Supplier code (max 20 chars)
        if (!empty($product->supplier_code)) {
            $data['supplier_code'] = mb_substr($product->supplier_code, 0, 20);
        }

        // tw_IdPodstDostawca = Manufacturer ID (FK to kh__Kontrahent)
        // Try to resolve manufacturer by name
        if (!empty($product->manufacturer)) {
            $manufacturerId = $this->findManufacturerIdByName($product->manufacturer, $config);
            if ($manufacturerId !== null) {
                $data['manufacturer_id'] = $manufacturerId;

                Log::debug('mapExtendedFields: Manufacturer resolved', [
                    'sku' => $product->sku,
                    'manufacturer_name' => $product->manufacturer,
                    'manufacturer_id' => $manufacturerId,
                ]);
            } else {
                Log::debug('mapExtendedFields: Manufacturer not found in Subiekt', [
                    'sku' => $product->sku,
                    'manufacturer_name' => $product->manufacturer,
                ]);
            }
        }

        return $data;
    }

    /**
     * Find manufacturer ID in Subiekt GT by name.
     *
     * Searches kh__Kontrahent table for matching name.
     * Results are cached for 1 hour to avoid repeated API calls.
     *
     * @param string $name Manufacturer name to search
     * @param array $config Connection config (for REST API client)
     * @return int|null Manufacturer ID (kh_Id) or null if not found
     */
    protected function findManufacturerIdByName(string $name, array $config): ?int
    {
        if (empty($name)) {
            return null;
        }

        // Normalize name for comparison
        $normalizedName = mb_strtolower(trim($name));
        $cacheKey = "subiekt_manufacturer_map_" . md5($normalizedName);

        // Check cache first
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached === 0 ? null : $cached;
        }

        try {
            // Try REST API client if available
            if ($this->restApiClient) {
                $response = $this->restApiClient->getManufacturers();
                $manufacturers = $response['data'] ?? [];

                foreach ($manufacturers as $manufacturer) {
                    $mfName = $manufacturer['name'] ?? $manufacturer['Name'] ?? $manufacturer['kh_Nazwa'] ?? '';
                    if (mb_strtolower(trim($mfName)) === $normalizedName) {
                        $mfId = (int) ($manufacturer['id'] ?? $manufacturer['Id'] ?? $manufacturer['kh_Id']);

                        // Cache for 1 hour
                        \Illuminate\Support\Facades\Cache::put($cacheKey, $mfId, now()->addHour());

                        return $mfId;
                    }
                }

                // Not found - cache as 0 to avoid repeated lookups
                \Illuminate\Support\Facades\Cache::put($cacheKey, 0, now()->addHour());
                return null;
            }

            // SQL Direct mode fallback (if queryBuilder available)
            if ($this->queryBuilder) {
                $result = \Illuminate\Support\Facades\DB::connection($this->connectionName)
                    ->table('kh__Kontrahent')
                    ->where('kh_Nazwa', 'LIKE', '%' . $name . '%')
                    ->where('kh_Aktywny', 1)
                    ->orderByRaw("CASE WHEN kh_Nazwa = ? THEN 0 ELSE 1 END", [$name])
                    ->first(['kh_Id', 'kh_Nazwa']);

                if ($result) {
                    $mfId = (int) $result->kh_Id;
                    \Illuminate\Support\Facades\Cache::put($cacheKey, $mfId, now()->addHour());
                    return $mfId;
                }
            }

            // Not found
            \Illuminate\Support\Facades\Cache::put($cacheKey, 0, now()->addHour());
            return null;

        } catch (\Exception $e) {
            Log::warning('findManufacturerIdByName: Error searching manufacturer', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Map stock locations from PPM to Subiekt GT format.
     *
     * In Subiekt GT, tw_Pole2 is used to store location information.
     * This method aggregates locations from all warehouses into a CSV string.
     *
     * Format: "LOCATION1,LOCATION2,LOCATION3"
     * Example: "A-12-3,B-05-1,C-07"
     *
     * Note: Warehouse prefix is NOT included - only raw location values
     * separated by comma. This matches user requirement for simple format.
     *
     * @param Product $product
     * @param array $config Connection config (for warehouse mappings)
     * @return string|null CSV of locations or null if no locations
     */
    protected function mapStockLocations(Product $product, array $config): ?string
    {
        $locations = [];

        // Get product stock entries
        $productStock = $product->stock ?? collect();
        if ($productStock instanceof \Illuminate\Database\Eloquent\Collection) {
            $productStock = $productStock->toArray();
        }

        foreach ($productStock as $stockData) {
            $location = $stockData['location'] ?? null;

            if (!empty($location)) {
                // Add location without warehouse prefix - simple comma-separated format
                $trimmedLocation = trim(mb_substr($location, 0, 30));
                if (!empty($trimmedLocation) && !in_array($trimmedLocation, $locations)) {
                    $locations[] = $trimmedLocation;
                }
            }
        }

        if (empty($locations)) {
            return null;
        }

        // Join locations into CSV, max 50 chars for tw_Pole2
        $csvLocations = implode(',', $locations);
        if (mb_strlen($csvLocations) > 50) {
            // Truncate intelligently at last comma before limit
            $csvLocations = mb_substr($csvLocations, 0, 50);
            $lastComma = mb_strrpos($csvLocations, ',');
            if ($lastComma !== false && $lastComma > 10) {
                $csvLocations = mb_substr($csvLocations, 0, $lastComma);
            }
        }

        return $csvLocations;
    }

    /**
     * Update ProductErpData from REST API response.
     *
     * TASK 1 FIX: Now fetches ALL prices (11 levels) and ALL stock per warehouse
     * Note: API returns camelCase field names (priceNet, priceGross, isActive)
     *
     * @param Product $product
     * @param ERPConnection $connection
     * @param object $subiektProduct
     * @return ProductErpData
     */
    protected function updateProductErpDataFromRestApi(Product $product, ERPConnection $connection, object $subiektProduct): ProductErpData
    {
        $subiektId = $subiektProduct->id ?? null;

        Log::debug('updateProductErpDataFromRestApi: Starting', [
            'product_id' => $product->id,
            'subiekt_id' => $subiektId,
            'sku' => $subiektProduct->sku ?? null,
        ]);

        // === FETCH ALL PRICES (11 levels) ===
        $allPrices = [];
        if ($subiektId && $this->restApiClient) {
            try {
                $pricesResponse = $this->restApiClient->getProductPrices((int) $subiektId);
                $pricesData = $pricesResponse['data'] ?? [];

                Log::debug('updateProductErpDataFromRestApi: Prices fetched', [
                    'subiekt_id' => $subiektId,
                    'prices_count' => count($pricesData),
                    'raw_prices' => $pricesData,
                ]);

                // Transform to format: [priceLevel => ['net' => X, 'gross' => Y, 'name' => Z]]
                // REST API returns PascalCase: PriceLevel, PriceLevelName, PriceNet, PriceGross
                foreach ($pricesData as $price) {
                    $level = $price['PriceLevel'] ?? $price['priceLevel'] ?? $price['price_level'] ?? null;
                    if ($level !== null) {
                        $allPrices[$level] = [
                            'net' => (float) ($price['PriceNet'] ?? $price['priceNet'] ?? $price['price_net'] ?? 0),
                            'gross' => (float) ($price['PriceGross'] ?? $price['priceGross'] ?? $price['price_gross'] ?? 0),
                            'name' => $price['PriceLevelName'] ?? $price['priceLevelName'] ?? $price['name'] ?? $price['priceName'] ?? "Cena {$level}",
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('updateProductErpDataFromRestApi: Failed to fetch prices', [
                    'subiekt_id' => $subiektId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // === FETCH ALL STOCK PER WAREHOUSE ===
        $allStock = [];
        if ($subiektId && $this->restApiClient) {
            try {
                $stockResponse = $this->restApiClient->getProductStock((int) $subiektId);
                $stockData = $stockResponse['data'] ?? [];

                Log::debug('updateProductErpDataFromRestApi: Stock fetched', [
                    'subiekt_id' => $subiektId,
                    'stock_count' => count($stockData),
                    'raw_stock' => $stockData,
                ]);

                // Transform to format: [warehouseId => ['quantity' => X, 'reserved' => Y, 'name' => Z]]
                // REST API returns PascalCase: WarehouseId, WarehouseName, Quantity, Reserved
                foreach ($stockData as $stock) {
                    $warehouseId = $stock['WarehouseId'] ?? $stock['warehouseId'] ?? $stock['warehouse_id'] ?? null;
                    if ($warehouseId !== null) {
                        $allStock[$warehouseId] = [
                            'quantity' => (float) ($stock['Quantity'] ?? $stock['quantity'] ?? $stock['stock'] ?? 0),
                            'reserved' => (float) ($stock['Reserved'] ?? $stock['reserved'] ?? $stock['stockReserved'] ?? 0),
                            'available' => (float) ($stock['available'] ?? (($stock['Quantity'] ?? $stock['quantity'] ?? 0) - ($stock['Reserved'] ?? $stock['reserved'] ?? 0))),
                            'name' => $stock['WarehouseName'] ?? $stock['warehouseName'] ?? $stock['warehouse_name'] ?? "Magazyn {$warehouseId}",
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('updateProductErpDataFromRestApi: Failed to fetch stock', [
                    'subiekt_id' => $subiektId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Handle both camelCase (new API) and snake_case (legacy) field names
        $externalData = [
            'subiekt_id' => $subiektId,
            'sku' => $subiektProduct->sku ?? null,
            'name' => $subiektProduct->name ?? null,
            'ean' => $subiektProduct->ean ?? null,
            // Single price (default level) - for backwards compatibility
            'price_net' => $subiektProduct->priceNet ?? $subiektProduct->price_net ?? null,
            'price_gross' => $subiektProduct->priceGross ?? $subiektProduct->price_gross ?? null,
            'stock_quantity' => $subiektProduct->stock ?? $subiektProduct->stock_quantity ?? 0,
            'stock_reserved' => $subiektProduct->stockReserved ?? $subiektProduct->stock_reserved ?? 0,
            // Other fields
            'is_active' => $subiektProduct->isActive ?? $subiektProduct->is_active ?? true,
            'vat_rate' => $subiektProduct->vatRate ?? $subiektProduct->vat_rate ?? null,
            'group_name' => $subiektProduct->groupName ?? $subiektProduct->group_name ?? null,
            'manufacturer_name' => $subiektProduct->manufacturerName ?? $subiektProduct->manufacturer_name ?? null,
            'unit' => $subiektProduct->unit ?? null,
            'weight' => $subiektProduct->weight ?? null,
            // === NEW: ALL PRICES AND STOCK ===
            'prices' => $allPrices,  // [priceLevel => ['net' => X, 'gross' => Y, 'name' => Z]]
            'stock' => $allStock,     // [warehouseId => ['quantity' => X, 'reserved' => Y, 'name' => Z]]
            // === ETAP_08 FAZA 7: Extended fields from Subiekt GT ===
            // Text fields (tw_Pole1-5, tw_Uwagi)
            'Pole1' => $subiektProduct->Pole1 ?? $subiektProduct->pole1 ?? null,
            'Pole3' => $subiektProduct->Pole3 ?? $subiektProduct->pole3 ?? null,
            'Pole4' => $subiektProduct->Pole4 ?? $subiektProduct->pole4 ?? null,
            'Pole5' => $subiektProduct->Pole5 ?? $subiektProduct->pole5 ?? null,
            'Notes' => $subiektProduct->Notes ?? $subiektProduct->notes ?? null,
            // Boolean fields (tw_SklepInternet, tw_MechanizmPodzielonejPlatnosci)
            'ShopInternet' => $subiektProduct->ShopInternet ?? $subiektProduct->shopInternet ?? null,
            'SplitPayment' => $subiektProduct->SplitPayment ?? $subiektProduct->splitPayment ?? null,
            // Additional identification fields
            'ManufacturerName' => $subiektProduct->ManufacturerName ?? $subiektProduct->manufacturerName ?? null,
            'SupplierCode' => $subiektProduct->SupplierCode ?? $subiektProduct->supplierCode ?? null,
            // Metadata
            'fetched_via' => 'rest_api',
            'fetched_at' => now()->toIso8601String(),
            'prices_fetched_at' => !empty($allPrices) ? now()->toIso8601String() : null,
            'stock_fetched_at' => !empty($allStock) ? now()->toIso8601String() : null,
        ];

        // ETAP C.1: Generate hash for change detection
        $syncHash = $this->generateExternalDataHash($externalData);

        Log::debug('updateProductErpDataFromRestApi: Saving external_data', [
            'product_id' => $product->id,
            'subiekt_id' => $subiektId,
            'prices_count' => count($allPrices),
            'stock_count' => count($allStock),
            'sync_hash' => $syncHash,
        ]);

        return ProductErpData::updateOrCreate(
            [
                'product_id' => $product->id,
                'erp_connection_id' => $connection->id,
            ],
            [
                'external_id' => (string) ($subiektId ?? ''),
                'external_sku' => $subiektProduct->sku ?? $product->sku,
                'sync_status' => ProductErpData::STATUS_SYNCED,
                'last_sync_at' => now(),
                'last_pull_at' => now(),
                'erp_updated_at' => now(),  // ETAP C.1: Track ERP source timestamp
                'last_sync_hash' => $syncHash,  // ETAP C.1: Track data hash
                'external_data' => $externalData,
            ]
        );
    }

    /**
     * Generate hash from external data for change detection.
     *
     * ETAP C.1: Auto Sync - Hash Generation
     *
     * Uses key fields that indicate actual product changes:
     * - name, sku (identity)
     * - prices (all levels)
     * - stock (all warehouses)
     *
     * @param array $externalData
     * @return string MD5 hash
     */
    protected function generateExternalDataHash(array $externalData): string
    {
        $keyData = [
            'name' => $externalData['name'] ?? null,
            'sku' => $externalData['sku'] ?? null,
            'ean' => $externalData['ean'] ?? null,
            'prices' => $externalData['prices'] ?? [],
            'stock' => $externalData['stock'] ?? [],
        ];

        return md5(json_encode($keyData));
    }

    /**
     * Sync product via Sfera API (placeholder).
     *
     * @param ERPConnection $connection
     * @param Product $product
     * @return array
     */
    protected function syncProductViaSferaApi(ERPConnection $connection, Product $product, array $syncOptions = []): array
    {
        // TODO: Implement Sfera COM bridge
        return [
            'success' => false,
            'message' => 'Sfera API mode nie jest jeszcze zaimplementowany',
            'external_id' => null,
        ];
    }

    /**
     * Enrich config with mappings from PriceGroup and Warehouse models.
     *
     * CRITICAL FIX: ERPManager stores mappings in model.erp_mapping (PriceGroup, Warehouse),
     * but SyncProductToERP job uses connection_config which may not have them.
     * This method reconstructs mappings from the source of truth (models).
     *
     * @param array $config Original connection config
     * @param string $erpType ERP type (e.g., 'subiekt_gt')
     * @return array Enriched config with mappings
     */
    protected function enrichConfigWithMappingsFromModels(array $config, string $erpType): array
    {
        // Reconstruct warehouse mappings from Warehouse.erp_mapping
        $warehouseMappings = [];
        $warehouses = \App\Models\Warehouse::whereNotNull("erp_mapping->{$erpType}")->get();
        foreach ($warehouses as $warehouse) {
            $mapping = $warehouse->erp_mapping[$erpType] ?? null;
            if ($mapping && isset($mapping['id'])) {
                $warehouseMappings[$mapping['id']] = $warehouse->id;
            }
        }

        // Reconstruct price group mappings from PriceGroup.erp_mapping
        $priceGroupMappings = [];
        $priceGroups = \App\Models\PriceGroup::whereNotNull("erp_mapping->{$erpType}")->get();
        foreach ($priceGroups as $priceGroup) {
            $mapping = $priceGroup->erp_mapping[$erpType] ?? null;
            if ($mapping && isset($mapping['id'])) {
                $priceGroupMappings[$mapping['id']] = $priceGroup->id;
            }
        }

        Log::debug('enrichConfigWithMappingsFromModels: Reconstructed mappings', [
            'erp_type' => $erpType,
            'warehouse_mappings' => $warehouseMappings,
            'price_group_mappings' => $priceGroupMappings,
            'original_warehouse_mappings' => $config['warehouse_mappings'] ?? [],
            'original_price_group_mappings' => $config['price_group_mappings'] ?? [],
        ]);

        // Merge reconstructed mappings into config (override empty arrays)
        if (!empty($warehouseMappings)) {
            $config['warehouse_mappings'] = $warehouseMappings;
        }
        if (!empty($priceGroupMappings)) {
            $config['price_group_mappings'] = $priceGroupMappings;
        }

        return $config;
    }

    // ==========================================
    // PUBLIC UTILITIES
    // ==========================================

    /**
     * Create a new product in Subiekt GT ERP.
     *
     * This is a public facade method for creating products directly in Subiekt GT.
     * It handles:
     * - Connection validation
     * - Product data mapping (PPM → Subiekt GT format)
     * - API call via REST client
     * - IntegrationMapping creation on success
     * - Error handling with logging
     *
     * IMPORTANT: Requires Sfera GT to be enabled on the API server!
     *
     * @param Product $product PPM Product model to create in ERP
     * @param ERPConnection|null $connection ERP connection (uses first active if null)
     * @return array{success: bool, message: string, external_id: ?string, action: string, error_code?: string}
     */
    public function createProductInErp(Product $product, ?ERPConnection $connection = null): array
    {
        $startTime = microtime(true);

        try {
            // 1. Get or validate connection
            if (!$connection) {
                $connection = ERPConnection::where('erp_type', ERPConnection::ERP_SUBIEKT_GT)
                    ->where('is_active', true)
                    ->first();

                if (!$connection) {
                    Log::warning('SubiektGTService::createProductInErp - No active Subiekt GT connection found');
                    return [
                        'success' => false,
                        'message' => 'Brak aktywnego polaczenia z Subiekt GT',
                        'external_id' => null,
                        'action' => 'no_connection',
                        'error_code' => 'NO_CONNECTION',
                    ];
                }
            }

            $config = $connection->connection_config;
            $connectionMode = $config['connection_mode'] ?? 'rest_api';

            // 2. Validate connection mode - only REST API supports create
            if ($connectionMode !== 'rest_api') {
                Log::warning('SubiektGTService::createProductInErp - Connection mode does not support create', [
                    'mode' => $connectionMode,
                ]);
                return [
                    'success' => false,
                    'message' => 'Tworzenie produktow wymaga trybu REST API',
                    'external_id' => null,
                    'action' => 'unsupported_mode',
                    'error_code' => 'UNSUPPORTED_MODE',
                ];
            }

            // 3. Validate product has required fields
            if (empty($product->sku)) {
                return [
                    'success' => false,
                    'message' => 'Produkt musi miec SKU aby utworzyc go w Subiekt GT',
                    'external_id' => null,
                    'action' => 'validation_error',
                    'error_code' => 'MISSING_SKU',
                ];
            }

            // 4. Check if product already exists in Subiekt GT
            $existsResult = $this->findProductBySku($connection, $product->sku);
            if ($existsResult['found']) {
                Log::info('SubiektGTService::createProductInErp - Product already exists', [
                    'sku' => $product->sku,
                    'external_id' => $existsResult['external_id'],
                ]);
                return [
                    'success' => false,
                    'message' => 'Produkt o SKU ' . $product->sku . ' juz istnieje w Subiekt GT (ID: ' . $existsResult['external_id'] . ')',
                    'external_id' => $existsResult['external_id'],
                    'action' => 'already_exists',
                    'error_code' => 'ALREADY_EXISTS',
                ];
            }

            // 5. Create REST API client and call create
            $client = $this->createRestApiClient($config);
            $createResult = $this->createProductInSubiekt($client, $product, $config, $connection->id);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($createResult['success']) {
                $externalId = (string) ($createResult['external_id'] ?? $createResult['product_id'] ?? null);

                // 6. Create IntegrationMapping
                if ($externalId) {
                    $this->updateIntegrationMapping($product, $connection, $externalId);

                    // 7. Fetch fresh data and update ProductErpData
                    try {
                        $this->initializeForConnection($connection);
                        $freshResponse = $client->getProductById((int) $externalId);
                        if ($freshResponse['success'] ?? false) {
                            $freshProduct = (object) ($freshResponse['data'] ?? []);
                            $this->updateProductErpDataFromRestApi($product, $connection, $freshProduct);
                        }
                    } catch (\Exception $e) {
                        Log::warning('SubiektGTService::createProductInErp - Failed to fetch fresh data', [
                            'sku' => $product->sku,
                            'external_id' => $externalId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                IntegrationLog::info(
                    'product_create',
                    'Product created in Subiekt GT',
                    [
                        'product_id' => $product->id,
                        'product_sku' => $product->sku,
                        'external_id' => $externalId,
                        'response_time_ms' => $responseTime,
                    ],
                    IntegrationLog::INTEGRATION_SUBIEKT_GT,
                    (string) $connection->id
                );

                return [
                    'success' => true,
                    'message' => $createResult['message'] ?? 'Produkt utworzony w Subiekt GT',
                    'external_id' => $externalId,
                    'action' => 'created',
                    'response_time_ms' => $responseTime,
                ];
            }

            // Create failed
            IntegrationLog::error(
                'product_create',
                'Failed to create product in Subiekt GT',
                [
                    'product_id' => $product->id,
                    'product_sku' => $product->sku,
                    'error' => $createResult['message'] ?? 'Unknown error',
                    'error_code' => $createResult['error_code'] ?? null,
                    'response_time_ms' => $responseTime,
                ],
                IntegrationLog::INTEGRATION_SUBIEKT_GT,
                (string) $connection->id
            );

            return [
                'success' => false,
                'message' => $createResult['message'] ?? 'Nie udalo sie utworzyc produktu w Subiekt GT',
                'external_id' => null,
                'action' => 'create_failed',
                'error_code' => $createResult['error_code'] ?? 'CREATE_FAILED',
                'response_time_ms' => $responseTime,
            ];

        } catch (SubiektApiException $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            IntegrationLog::error(
                'product_create',
                'Subiekt API error during product create',
                [
                    'product_id' => $product->id,
                    'product_sku' => $product->sku,
                    'error' => $e->getMessage(),
                    'http_status' => $e->getHttpStatusCode(),
                ],
                IntegrationLog::INTEGRATION_SUBIEKT_GT,
                (string) ($connection?->id ?? 'unknown'),
                $e
            );

            return [
                'success' => false,
                'message' => 'Blad API Subiekt GT: ' . $e->getMessage(),
                'external_id' => null,
                'action' => 'api_error',
                'error_code' => 'API_ERROR',
                'response_time_ms' => $responseTime,
            ];

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('SubiektGTService::createProductInErp - Unexpected error', [
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Nieoczekiwany blad: ' . $e->getMessage(),
                'external_id' => null,
                'action' => 'error',
                'error_code' => 'UNKNOWN_ERROR',
                'response_time_ms' => $responseTime,
            ];
        }
    }

    /**
     * Get warehouses from Subiekt GT for mapping UI.
     *
     * @param ERPConnection $connection
     * @return array
     */
    public function getWarehouses(ERPConnection $connection): array
    {
        try {
            $this->initializeForConnection($connection);
            return $this->queryBuilder->getWarehouses()->toArray();
        } catch (\Exception $e) {
            Log::error('SubiektGTService: Failed to get warehouses', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get price types from Subiekt GT for mapping UI.
     *
     * @param ERPConnection $connection
     * @return array
     */
    public function getPriceTypes(ERPConnection $connection): array
    {
        try {
            $this->initializeForConnection($connection);
            return $this->queryBuilder->getPriceTypes()->toArray();
        } catch (\Exception $e) {
            Log::error('SubiektGTService: Failed to get price types', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get count of modified products since timestamp.
     *
     * @param ERPConnection $connection
     * @param string $since Timestamp
     * @return int
     */
    public function getModifiedProductsCount(ERPConnection $connection, string $since): int
    {
        try {
            $this->initializeForConnection($connection);

            // For REST API mode, use REST client or return 0 (not supported via REST)
            $config = $connection->connection_config;
            $connectionMode = $config['connection_mode'] ?? 'rest_api';

            if ($connectionMode === 'rest_api') {
                // REST API doesn't have a modified count endpoint
                // Return 0 to indicate unknown count (will process all linked products)
                Log::debug('SubiektGTService: getModifiedProductsCount not available in REST API mode');
                return 0;
            }

            // SQL Direct mode - use queryBuilder
            if ($this->queryBuilder === null) {
                Log::warning('SubiektGTService: queryBuilder not initialized');
                return 0;
            }

            return $this->queryBuilder->getModifiedProductsCount($since);
        } catch (\Exception $e) {
            Log::error('SubiektGTService: Failed to get modified count', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VARIANT SYNC METHODS
    |--------------------------------------------------------------------------
    | Subiekt GT does not have native variant support - each variant is
    | a separate product. We use tw_Pole8 to store parent_sku, creating
    | a parent-child relationship.
    */

    /**
     * Sync product variants to Subiekt GT.
     *
     * Each PPM variant becomes a separate product in Subiekt with Pole8 = parent_sku.
     * This allows grouping variants by parent while maintaining separate stock/prices.
     *
     * @param ERPConnection $connection The ERP connection to use
     * @param Product $product The parent product (must be variant master)
     * @return array Sync result with counts and errors
     */
    public function syncProductVariantsToSubiekt(ERPConnection $connection, Product $product): array
    {
        if (!$product->is_variant_master) {
            return [
                'success' => false,
                'message' => 'Product is not a variant master',
                'synced' => 0,
                'failed' => 0,
                'errors' => [],
            ];
        }

        $this->initializeForConnection($connection);

        $results = [
            'success' => true,
            'synced' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $parentSku = $product->sku;
        $variants = $product->variants()->with(['prices', 'stock'])->get();

        Log::info('SubiektGTService: syncing variants to Subiekt', [
            'parent_sku' => $parentSku,
            'variant_count' => $variants->count(),
            'connection_id' => $connection->id,
        ]);

        foreach ($variants as $variant) {
            try {
                $variantData = $this->buildVariantSyncData($variant, $parentSku);

                // Check if variant already exists in Subiekt
                $existsResult = $this->restApiClient->productExists($variant->sku);

                if ($existsResult['exists']) {
                    // Update existing product
                    $this->restApiClient->updateProductBySku($variant->sku, $variantData);
                    Log::debug('SubiektGTService: variant updated', [
                        'variant_sku' => $variant->sku,
                        'parent_sku' => $parentSku,
                    ]);
                } else {
                    // Create new product with SKU
                    $variantData['sku'] = $variant->sku;
                    $this->restApiClient->createProduct($variantData);
                    Log::debug('SubiektGTService: variant created', [
                        'variant_sku' => $variant->sku,
                        'parent_sku' => $parentSku,
                    ]);
                }

                $results['synced']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'variant_sku' => $variant->sku,
                    'error' => $e->getMessage(),
                ];
                Log::error('SubiektGTService: variant sync failed', [
                    'variant_sku' => $variant->sku,
                    'parent_sku' => $parentSku,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $results['success'] = $results['failed'] === 0;

        Log::info('SubiektGTService: variant sync completed', [
            'parent_sku' => $parentSku,
            'synced' => $results['synced'],
            'failed' => $results['failed'],
        ]);

        return $results;
    }

    /**
     * Builds sync data for a variant product.
     *
     * Uses ONLY tw_Pole8 for parent_sku (no Pole6/Pole7 references).
     *
     * @param ProductVariant $variant The variant to sync
     * @param string $parentSku Parent product SKU
     * @return array Data to send to Subiekt API
     */
    protected function buildVariantSyncData(ProductVariant $variant, string $parentSku): array
    {
        $resolver = $this->getVariantResolver();

        return [
            'name' => $variant->name,
            'pole8' => $resolver->buildPole8Value($parentSku),  // TYLKO Pole8!
            'is_active' => $variant->is_active,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | VARIANT PULL METHODS (Subiekt GT -> PPM)
    |--------------------------------------------------------------------------
    | Pull prices and stock for product variants from Subiekt GT.
    | Variants in Subiekt are separate products with Pole8 = parent_sku.
    */

    /**
     * Pull variant data from Subiekt GT for a variant master product.
     *
     * Finds all Subiekt products where Pole8 = parent_sku, matches them
     * to PPM ProductVariant by SKU, and updates prices/stock.
     *
     * @param ERPConnection $connection
     * @param Product $product The variant master product
     * @return array Result with counts
     */
    public function pullProductVariantsFromSubiekt(ERPConnection $connection, Product $product): array
    {
        if (!$product->is_variant_master) {
            return [
                'success' => false,
                'message' => 'Product is not a variant master',
                'updated' => 0,
                'errors' => [],
            ];
        }

        $this->initializeForConnection($connection);
        $config = $connection->connection_config ?? [];

        $results = [
            'success' => true,
            'updated' => 0,
            'skipped' => 0,
            'not_found' => 0,
            'errors' => [],
        ];

        $parentSku = $product->sku;
        $variants = $product->variants()->get();

        if ($variants->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No variants to pull',
                'updated' => 0,
            ];
        }

        Log::info('pullProductVariantsFromSubiekt: Starting', [
            'parent_sku' => $parentSku,
            'variant_count' => $variants->count(),
            'connection_id' => $connection->id,
        ]);

        // Get warehouse and price group mappings
        $warehouseMappings = $config['warehouse_mappings'] ?? [];
        $priceGroupMappings = $config['price_group_mappings'] ?? [];

        foreach ($variants as $variant) {
            try {
                // Find variant in Subiekt by SKU
                $subiektProduct = $this->restApiClient->getProductBySku($variant->sku);

                if (!$subiektProduct || !isset($subiektProduct['data'])) {
                    $results['not_found']++;
                    Log::debug('pullProductVariantsFromSubiekt: Variant not found in Subiekt', [
                        'variant_sku' => $variant->sku,
                    ]);
                    continue;
                }

                $subiektData = $subiektProduct['data'];

                // Verify this is actually a variant of our parent (Pole8 check)
                $pole8Value = $subiektData['pole8'] ?? $subiektData['Pole8'] ?? null;
                if ($pole8Value && $pole8Value !== $parentSku) {
                    Log::warning('pullProductVariantsFromSubiekt: Pole8 mismatch', [
                        'variant_sku' => $variant->sku,
                        'expected_parent' => $parentSku,
                        'actual_pole8' => $pole8Value,
                    ]);
                }

                // Get prices for variant
                $pricesResponse = $this->restApiClient->getProductPricesBySku($variant->sku);
                $subiektPrices = $pricesResponse['data'] ?? [];

                // Get stock for variant
                $stockResponse = $this->restApiClient->getProductStockBySku($variant->sku);
                $subiektStock = $stockResponse['data'] ?? [];

                // Update variant prices
                $pricesUpdated = $this->updateVariantPricesFromErp($variant, $subiektPrices, $priceGroupMappings);

                // Update variant stock
                $stockUpdated = $this->updateVariantStockFromErp($variant, $subiektStock, $warehouseMappings);

                if ($pricesUpdated || $stockUpdated) {
                    $results['updated']++;
                    Log::debug('pullProductVariantsFromSubiekt: Variant updated', [
                        'variant_sku' => $variant->sku,
                        'prices_updated' => $pricesUpdated,
                        'stock_updated' => $stockUpdated,
                    ]);
                } else {
                    $results['skipped']++;
                }

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'variant_sku' => $variant->sku,
                    'error' => $e->getMessage(),
                ];
                Log::error('pullProductVariantsFromSubiekt: Error', [
                    'variant_sku' => $variant->sku,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $results['success'] = empty($results['errors']);

        Log::info('pullProductVariantsFromSubiekt: Completed', [
            'parent_sku' => $parentSku,
            'updated' => $results['updated'],
            'skipped' => $results['skipped'],
            'not_found' => $results['not_found'],
            'errors_count' => count($results['errors']),
        ]);

        return $results;
    }

    /**
     * Update variant prices from Subiekt GT data.
     *
     * @param ProductVariant $variant
     * @param array $subiektPrices Prices from Subiekt API
     * @param array $priceGroupMappings Subiekt level -> PPM price_group_id
     * @return bool True if any prices were updated
     */
    protected function updateVariantPricesFromErp(ProductVariant $variant, array $subiektPrices, array $priceGroupMappings): bool
    {
        $updated = false;

        Log::debug('updateVariantPricesFromErp: Starting', [
            'variant_sku' => $variant->sku,
            'subiekt_prices_count' => count($subiektPrices),
            'subiekt_prices_sample' => array_slice($subiektPrices, 0, 3),
            'price_group_mappings_count' => count($priceGroupMappings),
            'price_group_mappings' => $priceGroupMappings,
        ]);

        foreach ($subiektPrices as $priceData) {
            $subiektLevel = $priceData['level'] ?? $priceData['Level'] ?? $priceData['priceLevel'] ?? null;
            $priceNet = $priceData['priceNet'] ?? $priceData['PriceNet'] ?? null;

            if ($subiektLevel === null || $priceNet === null) {
                continue;
            }

            // Skip level 0 (unused in Subiekt GT)
            if ($subiektLevel == 0) {
                continue;
            }

            // Find PPM price group ID from mapping
            // Mappings format: {subiektLevel: ppmId} e.g. {"1":81,"2":82}
            $ppmPriceGroupId = null;

            // Try both int and string keys
            if (isset($priceGroupMappings[$subiektLevel])) {
                $ppmPriceGroupId = $priceGroupMappings[$subiektLevel];
            } elseif (isset($priceGroupMappings[(string)$subiektLevel])) {
                $ppmPriceGroupId = $priceGroupMappings[(string)$subiektLevel];
            }

            if (!$ppmPriceGroupId) {
                Log::debug('updateVariantPricesFromErp: No mapping for level', [
                    'variant_sku' => $variant->sku,
                    'subiekt_level' => $subiektLevel,
                ]);
                continue;
            }

            // Update or create variant price
            $variantPrice = VariantPrice::updateOrCreate(
                [
                    'variant_id' => $variant->id,
                    'price_group_id' => $ppmPriceGroupId,
                ],
                [
                    'price' => (float) $priceNet,
                ]
            );

            if ($variantPrice->wasRecentlyCreated || $variantPrice->wasChanged()) {
                $updated = true;
            }
        }

        return $updated;
    }

    /**
     * Update variant stock from Subiekt GT data.
     *
     * @param ProductVariant $variant
     * @param array $subiektStock Stock from Subiekt API
     * @param array $warehouseMappings Subiekt warehouse_id -> PPM warehouse_id
     * @return bool True if any stock was updated
     */
    protected function updateVariantStockFromErp(ProductVariant $variant, array $subiektStock, array $warehouseMappings): bool
    {
        $updated = false;

        Log::debug('updateVariantStockFromErp: Starting', [
            'variant_sku' => $variant->sku,
            'subiekt_stock_count' => count($subiektStock),
            'subiekt_stock' => $subiektStock,
            'warehouse_mappings_count' => count($warehouseMappings),
            'warehouse_mappings' => $warehouseMappings,
        ]);

        foreach ($subiektStock as $stockData) {
            $subiektWarehouseId = $stockData['warehouseId'] ?? $stockData['WarehouseId'] ?? $stockData['warehouse_id'] ?? null;
            $quantity = $stockData['quantity'] ?? $stockData['Quantity'] ?? $stockData['stock'] ?? 0;

            if ($subiektWarehouseId === null) {
                continue;
            }

            // Find PPM warehouse ID from mapping
            // Mappings format: {subiektWarehouseId: ppmId} e.g. {"1":86,"4":88}
            $ppmWarehouseId = null;

            // Try both int and string keys
            if (isset($warehouseMappings[$subiektWarehouseId])) {
                $ppmWarehouseId = $warehouseMappings[$subiektWarehouseId];
            } elseif (isset($warehouseMappings[(string)$subiektWarehouseId])) {
                $ppmWarehouseId = $warehouseMappings[(string)$subiektWarehouseId];
            }

            if (!$ppmWarehouseId) {
                Log::debug('updateVariantStockFromErp: No mapping for warehouse', [
                    'variant_sku' => $variant->sku,
                    'subiekt_warehouse_id' => $subiektWarehouseId,
                ]);
                continue;
            }

            // Update or create variant stock
            $variantStock = VariantStock::updateOrCreate(
                [
                    'variant_id' => $variant->id,
                    'warehouse_id' => $ppmWarehouseId,
                ],
                [
                    'quantity' => (int) $quantity,
                ]
            );

            if ($variantStock->wasRecentlyCreated || $variantStock->wasChanged()) {
                $updated = true;
            }
        }

        return $updated;
    }
}
