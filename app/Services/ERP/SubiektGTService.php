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
 * Komunikacja przez bezposrednie polaczenie SQL Server.
 *
 * Supported Features:
 * - Product synchronization (PULL only in sql_direct mode)
 * - Stock levels per warehouse
 * - Prices for all price types
 * - Change detection via tw_DataMod
 *
 * NOT Supported (Subiekt GT limitations):
 * - Images (Subiekt GT does not store product images)
 * - Webhooks (polling required)
 * - Native variants (each variant = separate product)
 *
 * Connection Modes:
 * - sql_direct: Read-only SQL Server access (default)
 * - sfera_api: Sfera COM/DLL bridge (requires Windows)
 * - rest_api: REST API wrapper (requires external server)
 *
 * @package App\Services\ERP
 * @version 1.0
 */
class SubiektGTService implements ERPSyncServiceInterface
{
    protected ?SubiektQueryBuilder $queryBuilder = null;
    protected ?SubiektDataTransformer $transformer = null;
    protected string $connectionName = 'subiekt';

    /**
     * Test connection to Subiekt GT database.
     *
     * @param array $config Connection configuration
     * @return array Test result
     */
    public function testConnection(array $config): array
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
                'Subiekt GT connection test successful',
                [
                    'response_time' => $responseTime,
                    'stats' => $stats,
                    'config_mode' => $config['connection_mode'] ?? 'sql_direct',
                ],
                IntegrationLog::INTEGRATION_SUBIEKT_GT,
                $config['db_host'] ?? 'unknown'
            );

            return [
                'success' => true,
                'message' => 'Polaczenie z Subiekt GT pomyslne',
                'response_time' => $responseTime,
                'details' => array_merge($connectionResult['details'], [
                    'total_products' => $stats['total_products'] ?? 0,
                    'active_warehouses' => $stats['active_warehouses'] ?? 0,
                    'price_types' => $stats['price_types'] ?? 0,
                    'connection_mode' => $config['connection_mode'] ?? 'sql_direct',
                ]),
            ];

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            IntegrationLog::error(
                'connection_test',
                'Subiekt GT connection test failed',
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
        $connectionResult = $this->testConnection($config);

        if (!$connectionResult['success']) {
            return $connectionResult;
        }

        try {
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
        $connectionMode = $config['connection_mode'] ?? 'sql_direct';

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

            // Try to find product by ID first, then by SKU
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
        $connectionMode = $config['connection_mode'] ?? 'sql_direct';

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
            'bidirectional_sync' => false, // sql_direct = read-only
            'connection_modes' => [
                'sql_direct' => [
                    'name' => 'SQL Direct (Read-Only)',
                    'description' => 'Bezposrednie polaczenie do bazy SQL Server. Tylko odczyt danych.',
                    'available' => true,
                ],
                'rest_api' => [
                    'name' => 'REST API Wrapper',
                    'description' => 'Polaczenie przez REST API (wymaga zewnetrznego serwera).',
                    'available' => false,
                ],
                'sfera_api' => [
                    'name' => 'Sfera API (COM)',
                    'description' => 'Natywne API Subiekt GT (wymaga Windows Server).',
                    'available' => false,
                ],
            ],
        ];
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

        $this->configureConnection($config);

        $this->queryBuilder = new SubiektQueryBuilder($this->connectionName);

        $this->transformer = new SubiektDataTransformer([
            'warehouse_mappings' => $config['warehouse_mappings'] ?? [],
            'price_group_mappings' => $config['price_group_mappings'] ?? [],
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
     * Sync product via REST API (placeholder).
     *
     * @param ERPConnection $connection
     * @param Product $product
     * @return array
     */
    protected function syncProductViaRestApi(ERPConnection $connection, Product $product): array
    {
        // TODO: Implement REST API integration
        return [
            'success' => false,
            'message' => 'REST API mode nie jest jeszcze zaimplementowany',
            'external_id' => null,
        ];
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
