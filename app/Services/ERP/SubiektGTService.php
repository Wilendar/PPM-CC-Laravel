<?php

namespace App\Services\ERP;

use App\Models\ERPConnection;
use App\Models\Product;
use App\Models\ProductErpData;
use App\Models\IntegrationLog;
use App\Models\IntegrationMapping;
use App\Services\ERP\Contracts\ERPSyncServiceInterface;
use App\Services\ERP\SubiektGT\SubiektQueryBuilder;
use App\Services\ERP\SubiektGT\SubiektDataTransformer;
use App\Services\ERP\SubiektGT\SubiektRestApiClient;
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
    protected string $connectionName = 'subiekt';

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
     * @return array Sync result
     */
    public function syncProductToERP(ERPConnection $connection, Product $product): array
    {
        $config = $connection->connection_config;
        $connectionMode = $config['connection_mode'] ?? 'rest_api';

        // SQL Direct mode = read-only
        if ($connectionMode === 'sql_direct') {
            return $this->validateProductForSubiekt($connection, $product);
        }

        // REST API mode - would require external endpoint
        if ($connectionMode === 'rest_api') {
            return $this->syncProductViaRestApi($connection, $product);
        }

        // Sfera API mode - would require COM bridge
        if ($connectionMode === 'sfera_api') {
            return $this->syncProductViaSferaApi($connection, $product);
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

                // Create object for transformer
                $subiektProduct = (object) [
                    'id' => $productId,
                    'sku' => $subiektProductData['sku'] ?? $subiektProductData['Sku'] ?? $subiektProductData['tw_Symbol'] ?? '',
                    'name' => $subiektProductData['name'] ?? $subiektProductData['Name'] ?? $subiektProductData['tw_Nazwa'] ?? '',
                    'ean' => $subiektProductData['ean'] ?? $subiektProductData['Ean'] ?? $subiektProductData['tw_SWW'] ?? '',
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

            return [
                'success' => true,
                'message' => 'Produkt pobrany pomyslnie',
                'product' => $product,
                'erp_data' => $ppmData,
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
            ];

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
                    } elseif ($result['updated']) {
                        $results['updated']++;
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

            // Fetch stock from Subiekt GT
            $stockData = $this->queryBuilder->getProductStock($subiektId);

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

            // Fetch prices from Subiekt GT
            $priceData = $this->queryBuilder->getProductPrices($subiektId);

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
     * @return array
     */
    protected function syncProductViaRestApi(ERPConnection $connection, Product $product): array
    {
        try {
            $config = $connection->connection_config;
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

                        return [
                            'success' => true,
                            'message' => $updateResult['message'] ?? 'Produkt zaktualizowany w Subiekt GT',
                            'external_id' => $externalId,
                            'action' => $updateResult['action'] ?? 'updated',
                            'rows_affected' => $updateResult['rows_affected'] ?? null,
                            'updated_fields' => $updateResult['updated_fields'] ?? [],
                            'prices_updated' => $updateResult['prices_updated'] ?? 0,
                            'sku' => $product->sku,
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
            // Build update data from PPM product (using ERP data if available)
            $updateData = $this->mapPpmProductToSubiekt($product, $config, false, $connectionId);

            // DEBUG: Log mapped data before sending to API
            Log::debug('pushProductToSubiekt: Mapped data for Subiekt GT', [
                'sku' => $product->sku,
                'external_id' => $externalId,
                'mapped_fields' => array_keys($updateData),
                'mapped_data' => $updateData,
                'config_keys' => array_keys($config),
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
            ]);

            // Use PUT endpoint
            $result = $client->updateProductBySku($product->sku, $updateData);

            // DEBUG: Log API response
            Log::debug('pushProductToSubiekt: API response', [
                'sku' => $product->sku,
                'success' => $result['success'] ?? false,
                'result' => $result,
            ]);

            if ($result['success'] ?? false) {
                return [
                    'success' => true,
                    'message' => $result['data']['message'] ?? 'Produkt zaktualizowany',
                    'action' => $result['data']['action'] ?? 'updated',
                    'rows_affected' => $result['data']['rows_affected'] ?? null,
                    'updated_fields' => array_keys($updateData),
                    'prices_updated' => count($updateData['prices'] ?? []),
                ];
            }

            // Check for specific error codes
            $errorCode = $result['error_code'] ?? null;

            // SFERA_REQUIRED means server doesn't have Sfera enabled
            // We can still do basic field updates in DirectSQL mode
            if ($errorCode === 'SFERA_REQUIRED') {
                Log::info('SubiektGTService: Sfera not available, using DirectSQL fallback');
                // The API will use DirectSQL fallback automatically
            }

            return [
                'success' => false,
                'message' => $result['error'] ?? 'Blad aktualizacji produktu',
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
        $priceGroupMapping = $config['price_group_mappings'] ?? [];
        $prices = [];

        // Get product prices from PPM
        $productPrices = $product->prices ?? [];
        if ($productPrices instanceof \Illuminate\Database\Eloquent\Collection) {
            $productPrices = $productPrices->toArray();
        }

        foreach ($productPrices as $priceData) {
            $ppmGroupId = $priceData['price_group_id'] ?? $priceData['group_id'] ?? null;
            if ($ppmGroupId && isset($priceGroupMapping[$ppmGroupId])) {
                $subiektLevel = (int) $priceGroupMapping[$ppmGroupId];
                $prices[$subiektLevel] = [
                    'net' => (float) ($priceData['price_net'] ?? $priceData['price'] ?? 0),
                    'gross' => isset($priceData['price_gross']) ? (float) $priceData['price_gross'] : null,
                ];
            }
        }

        // Also map base price if available
        if (!empty($product->price_net)) {
            $defaultPriceLevel = $config['default_price_level'] ?? 0;
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

        // DEBUG: Log final mapped data
        Log::debug('mapPpmProductToSubiekt: Final mapped data', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'mapped_fields' => array_keys($data),
            'prices_count' => count($data['prices'] ?? []),
            'data' => $data,
        ]);

        return $data;
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
    protected function syncProductViaSferaApi(ERPConnection $connection, Product $product): array
    {
        // TODO: Implement Sfera COM bridge
        return [
            'success' => false,
            'message' => 'Sfera API mode nie jest jeszcze zaimplementowany',
            'external_id' => null,
        ];
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
            return $this->queryBuilder->getModifiedProductsCount($since);
        } catch (\Exception $e) {
            Log::error('SubiektGTService: Failed to get modified count', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }
}
