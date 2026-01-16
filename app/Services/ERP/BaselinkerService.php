<?php

namespace App\Services\ERP;

use App\Models\ERPConnection;
use App\Models\Product;
use App\Models\IntegrationLog;
use App\Models\IntegrationMapping;
use App\Models\JobProgress;
use App\Services\ERP\Contracts\ERPSyncServiceInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Media;
use App\Models\ProductVariant;
use Carbon\Carbon;

/**
 * BaselinkerService
 * 
 * FAZA B: Shop & ERP Management - Baselinker Integration Service (PRIORYTET #1)
 * 
 * Kompleksowa obsługa API Baselinker z features:
 * - Multi-inventory support
 * - Real-time stock synchronization  
 * - Price group management (8 grup cenowych PPM)
 * - Order management integration
 * - Warehouse mapping between PPM i Baselinker
 * 
 * Enterprise Features:
 * - Rate limiting respect (60 req/min standard)
 * - Automatic retry logic z exponential backoff
 * - Comprehensive error handling i logging
 * - Performance metrics tracking
 * - Webhook support dla real-time updates
 * 
 * Baselinker API Documentation: https://api.baselinker.com/
 */
class BaselinkerService implements ERPSyncServiceInterface
{
    protected $baseUrl = 'https://api.baselinker.com/connector.php';
    protected $timeout = 30;
    protected $retryAttempts = 3;
    protected $retryDelay = 1; // seconds
    protected $rateLimit = 60; // requests per minute

    /**
     * Test authentication with Baselinker.
     */
    public function testAuthentication(array $config): array
    {
        $startTime = microtime(true);
        
        try {
            // Test basic API connectivity
            $response = $this->makeRequest($config, 'getInventories', []);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($response['status'] === 'SUCCESS') {
                $inventories = $response['inventories'] ?? [];
                $features = $this->detectSupportedFeatures($inventories);
                
                IntegrationLog::info(
                    'auth_test',
                    'Baselinker authentication test successful',
                    [
                        'response_time' => $responseTime,
                        'inventories_count' => count($inventories),
                        'supported_features' => $features,
                    ],
                    IntegrationLog::INTEGRATION_BASELINKER,
                    $config['api_token']
                );

                return [
                    'success' => true,
                    'message' => 'Uwierzytelnienie pomyślne',
                    'response_time' => $responseTime,
                    'details' => [
                        'inventories_available' => count($inventories),
                        'inventories' => $inventories,
                    ],
                    'supported_features' => $features,
                ];
            } else {
                $errorMessage = $response['error_message'] ?? 'Unknown error';
                
                IntegrationLog::error(
                    'auth_test',
                    'Baselinker authentication test failed: ' . $errorMessage,
                    [
                        'response_time' => $responseTime,
                        'error_code' => $response['error_code'] ?? null,
                        'error_message' => $errorMessage,
                    ],
                    IntegrationLog::INTEGRATION_BASELINKER,
                    $config['api_token']
                );

                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'response_time' => $responseTime,
                    'details' => [
                        'error_code' => $response['error_code'] ?? null,
                    ]
                ];
            }
            
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            IntegrationLog::error(
                'auth_test',
                'Baselinker authentication test exception',
                [
                    'response_time' => $responseTime,
                    'error_message' => $e->getMessage(),
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                $config['api_token'],
                $e
            );

            return [
                'success' => false,
                'message' => 'Błąd połączenia: ' . $e->getMessage(),
                'response_time' => $responseTime,
                'details' => [
                    'exception_type' => get_class($e),
                    'exception_message' => $e->getMessage(),
                ]
            ];
        }
    }

    /**
     * Test connection to Baselinker.
     */
    public function testConnection(array $config): array
    {
        return $this->testAuthentication($config);
    }

    /**
     * Sync products to Baselinker.
     */
    public function syncProducts(ERPConnection $connection, array $products = []): array
    {
        $results = [
            'success' => true,
            'total_products' => 0,
            'synced_products' => 0,
            'skipped_products' => 0,
            'error_products' => 0,
            'errors' => [],
            'performance' => [
                'start_time' => Carbon::now(),
                'memory_start' => memory_get_usage(true),
            ]
        ];

        try {
            // If no specific products provided, get all active products
            if (empty($products)) {
                $products = Product::where('is_active', true)->get();
            }

            $results['total_products'] = count($products);
            $inventoryId = $connection->connection_config['inventory_id'];

            foreach ($products as $product) {
                try {
                    $syncResult = $this->syncSingleProduct($connection, $product, $inventoryId);
                    
                    if ($syncResult['success']) {
                        $results['synced_products']++;
                    } elseif ($syncResult['skipped']) {
                        $results['skipped_products']++;
                    } else {
                        $results['error_products']++;
                        $results['errors'][] = [
                            'product_sku' => $product->sku,
                            'error' => $syncResult['message']
                        ];
                    }

                } catch (\Exception $e) {
                    $results['error_products']++;
                    $results['errors'][] = [
                        'product_sku' => $product->sku,
                        'error' => 'Exception: ' . $e->getMessage()
                    ];

                    IntegrationLog::error(
                        'product_sync',
                        'Product sync exception for SKU: ' . $product->sku,
                        [
                            'connection_id' => $connection->id,
                            'product_id' => $product->id,
                            'product_sku' => $product->sku,
                        ],
                        IntegrationLog::INTEGRATION_BASELINKER,
                        (string) $connection->id,
                        $e
                    );
                }

                // Rate limiting - Baselinker standard: 60 req/min
                usleep(1000000); // 1 second between requests to stay safe
            }

        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = 'Global sync error: ' . $e->getMessage();

            IntegrationLog::error(
                'products_sync',
                'Products sync global exception',
                [
                    'connection_id' => $connection->id,
                    'total_products' => $results['total_products'],
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                (string) $connection->id,
                $e
            );
        }

        // Performance metrics
        $results['performance']['end_time'] = Carbon::now();
        $results['performance']['memory_end'] = memory_get_usage(true);
        $results['performance']['duration_seconds'] = $results['performance']['end_time']->diffInSeconds($results['performance']['start_time']);
        $results['performance']['memory_usage_mb'] = round(($results['performance']['memory_end'] - $results['performance']['memory_start']) / 1024 / 1024, 2);

        IntegrationLog::info(
            'products_sync',
            'Products sync completed',
            [
                'connection_id' => $connection->id,
                'results' => $results,
            ],
            IntegrationLog::INTEGRATION_BASELINKER,
            (string) $connection->id
        );

        return $results;
    }

    /**
     * Sync single product to Baselinker.
     */
    protected function syncSingleProduct(ERPConnection $connection, Product $product, string $inventoryId): array
    {
        try {
            // Get or create Baselinker product mapping
            $mapping = $product->integrationMappings()
                ->where('integration_type', 'baselinker')
                ->where('integration_identifier', $connection->instance_name)
                ->first();

            if ($mapping && $mapping->external_id) {
                // Update existing product
                return $this->updateBaselinkerProduct($connection, $product, $inventoryId, $mapping->external_id);
            } else {
                // Create new product
                return $this->createBaselinkerProduct($connection, $product, $inventoryId);
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'skipped' => false,
                'message' => 'Sync exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create new product in Baselinker.
     */
    protected function createBaselinkerProduct(ERPConnection $connection, Product $product, string $inventoryId): array
    {
        $productData = $this->buildBaselinkerProductData($connection, $product);
        
        try {
            $response = $this->makeRequest(
                $connection->connection_config,
                'addInventoryProduct',
                [
                    'inventory_id' => $inventoryId,
                    'product_id' => $product->sku, // Use SKU as product_id
                    'parent_id' => 0,
                    'is_bundle' => false,
                    'name' => $product->name,
                    'description' => $product->description ?: '',
                    'description_extra1' => $product->short_description ?: '',
                    'sku' => $product->sku,
                    'ean' => $product->ean ?: '',
                    'tax_rate' => $product->tax_rate ?: 23,
                    'weight' => $product->weight ?: 0,
                    'height' => $product->height ?: 0,
                    'width' => $product->width ?: 0,
                    'length' => $product->length ?: 0,
                ]
            );

            if ($response['status'] === 'SUCCESS') {
                $baselinkerProductId = $response['product_id'] ?? $product->sku;

                // Create integration mapping
                $product->integrationMappings()->create([
                    'integration_type' => 'baselinker',
                    'integration_identifier' => $connection->instance_name,
                    'external_id' => $baselinkerProductId,
                    'external_reference' => $product->sku,
                    'external_data' => $response,
                    'sync_status' => 'synced',
                    'last_sync_at' => Carbon::now(),
                ]);

                // Sync stock and prices
                $this->syncProductStock($connection, $product, $inventoryId, $baselinkerProductId);
                $this->syncProductPrices($connection, $product, $inventoryId, $baselinkerProductId);

                return [
                    'success' => true,
                    'skipped' => false,
                    'message' => 'Product created successfully',
                    'baselinker_id' => $baselinkerProductId
                ];
            } else {
                return [
                    'success' => false,
                    'skipped' => false,
                    'message' => 'Baselinker API error: ' . ($response['error_message'] ?? 'Unknown error')
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'skipped' => false,
                'message' => 'Request exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update existing product in Baselinker.
     */
    protected function updateBaselinkerProduct(ERPConnection $connection, Product $product, string $inventoryId, string $baselinkerProductId): array
    {
        try {
            // ETAP_08: Use addInventoryProduct for updates (updateInventoryProductsData doesn't exist!)
            // Baselinker API uses addInventoryProduct with product_id for both CREATE and UPDATE
            $response = $this->makeRequest(
                $connection->connection_config,
                'addInventoryProduct',
                [
                    'inventory_id' => $inventoryId,
                    'product_id' => $baselinkerProductId,
                    'sku' => $product->sku,
                    'ean' => $product->ean ?: '',
                    'name' => $product->name,
                    'description' => $product->description ?: '',
                    'description_extra1' => $product->short_description ?: '',
                    'tax_rate' => $product->tax_rate ?: 23,
                    'weight' => $product->weight ?: 0,
                    'height' => $product->height ?: 0,
                    'width' => $product->width ?: 0,
                    'length' => $product->length ?: 0,
                ]
            );

            if ($response['status'] === 'SUCCESS') {
                // Update integration mapping
                $mapping = $product->integrationMappings()
                    ->where('integration_type', 'baselinker')
                    ->where('integration_identifier', $connection->instance_name)
                    ->first();

                if ($mapping) {
                    $mapping->update([
                        'external_data' => $response,
                        'sync_status' => 'synced',
                        'last_sync_at' => Carbon::now(),
                        'error_message' => null,
                        'error_count' => 0,
                    ]);
                }

                // Sync stock and prices
                $this->syncProductStock($connection, $product, $inventoryId, $baselinkerProductId);
                $this->syncProductPrices($connection, $product, $inventoryId, $baselinkerProductId);

                return [
                    'success' => true,
                    'skipped' => false,
                    'message' => 'Product updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'skipped' => false,
                    'message' => 'Baselinker API error: ' . ($response['error_message'] ?? 'Unknown error')
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'skipped' => false,
                'message' => 'Request exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sync product stock to Baselinker.
     */
    protected function syncProductStock(ERPConnection $connection, Product $product, string $inventoryId, string $baselinkerProductId): void
    {
        try {
            $warehouseMapping = $connection->connection_config['warehouse_mappings'] ?? [];
            $stockData = [];

            foreach ($product->stock as $stock) {
                $baselinkerWarehouseId = $warehouseMapping[$stock->warehouse_id] ?? null;
                
                if ($baselinkerWarehouseId) {
                    $stockData[] = [
                        'product_id' => $baselinkerProductId,
                        'variant_id' => 0,
                        'warehouse_id' => $baselinkerWarehouseId,
                        'stock' => $stock->quantity
                    ];
                }
            }

            if (!empty($stockData)) {
                $this->makeRequest(
                    $connection->connection_config,
                    'updateInventoryProductsStock',
                    [
                        'inventory_id' => $inventoryId,
                        'products' => $stockData
                    ]
                );
            }

        } catch (\Exception $e) {
            IntegrationLog::error(
                'stock_sync',
                'Stock sync failed for product: ' . $product->sku,
                [
                    'product_sku' => $product->sku,
                    'error_message' => $e->getMessage(),
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                (string) $connection->id,
                $e
            );
        }
    }

    /**
     * Sync product prices to Baselinker (8 grup cenowych PPM).
     */
    protected function syncProductPrices(ERPConnection $connection, Product $product, string $inventoryId, string $baselinkerProductId): void
    {
        try {
            $priceMapping = $this->getPriceGroupMapping(); // PPM -> Baselinker mapping
            $priceData = [];

            foreach ($product->prices as $price) {
                $baselinkerPriceType = $priceMapping[$price->price_group_id] ?? null;
                
                if ($baselinkerPriceType) {
                    $priceData[] = [
                        'product_id' => $baselinkerProductId,
                        'price_type' => $baselinkerPriceType,
                        'price' => $price->price_gross
                    ];
                }
            }

            if (!empty($priceData)) {
                $this->makeRequest(
                    $connection->connection_config,
                    'updateInventoryProductsPrices',
                    [
                        'inventory_id' => $inventoryId,
                        'products' => $priceData
                    ]
                );
            }

        } catch (\Exception $e) {
            IntegrationLog::error(
                'price_sync',
                'Price sync failed for product: ' . $product->sku,
                [
                    'product_sku' => $product->sku,
                    'error_message' => $e->getMessage(),
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                (string) $connection->id,
                $e
            );
        }
    }

    /**
     * Get orders from Baselinker.
     */
    public function getOrders(ERPConnection $connection, Carbon $dateFrom, Carbon $dateTo): array
    {
        try {
            $response = $this->makeRequest(
                $connection->connection_config,
                'getOrders',
                [
                    'date_confirmed_from' => $dateFrom->timestamp,
                    'date_confirmed_to' => $dateTo->timestamp,
                    'filter_email' => '',
                    'filter_order_source' => '',
                    'filter_order_source_id' => '',
                    'get_unconfirmed_orders' => true
                ]
            );

            if ($response['status'] === 'SUCCESS') {
                return [
                    'success' => true,
                    'orders' => $response['orders'] ?? [],
                    'count' => count($response['orders'] ?? [])
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response['error_message'] ?? 'Unknown error',
                    'orders' => []
                ];
            }

        } catch (\Exception $e) {
            IntegrationLog::error(
                'get_orders',
                'Failed to get orders from Baselinker',
                [
                    'connection_id' => $connection->id,
                    'date_from' => $dateFrom->toDateString(),
                    'date_to' => $dateTo->toDateString(),
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                (string) $connection->id,
                $e
            );

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'orders' => []
            ];
        }
    }

    /**
     * Make HTTP request to Baselinker API.
     */
    protected function makeRequest(array $config, string $method, array $parameters): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post($this->baseUrl, [
                    'token' => $config['api_token'],
                    'method' => $method,
                    'parameters' => json_encode($parameters)
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                IntegrationLog::debug(
                    'api_call',
                    "Baselinker API call: {$method}",
                    [
                        'method' => $method,
                        'parameters' => $parameters,
                        'response_status' => $data['status'] ?? 'UNKNOWN',
                        'response_time' => $response->transferStats?->getTransferTime(),
                    ],
                    IntegrationLog::INTEGRATION_BASELINKER
                );

                return $data;
            } else {
                throw new \Exception('HTTP Error ' . $response->status() . ': ' . $response->reason());
            }

        } catch (\Exception $e) {
            IntegrationLog::error(
                'api_call',
                "Baselinker API call failed: {$method}",
                [
                    'method' => $method,
                    'parameters' => $parameters,
                    'error_message' => $e->getMessage(),
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                null,
                $e
            );

            throw $e;
        }
    }

    /**
     * Build Baselinker product data structure.
     */
    protected function buildBaselinkerProductData(ERPConnection $connection, Product $product): array
    {
        return [
            'name' => $product->name,
            'description' => $product->description ?: '',
            'description_extra1' => $product->short_description ?: '',
            'sku' => $product->sku,
            'ean' => $product->ean ?: '',
            'tax_rate' => $product->tax_rate ?: 23,
            'weight' => $product->weight ?: 0,
            'height' => $product->height ?: 0,
            'width' => $product->width ?: 0,
            'length' => $product->length ?: 0,
        ];
    }

    /**
     * Detect supported features based on available inventories.
     */
    protected function detectSupportedFeatures(array $inventories): array
    {
        $features = ['products', 'stock', 'prices', 'orders'];
        
        // Add features based on inventory capabilities
        if (!empty($inventories)) {
            $features[] = 'multi_inventory';
            $features[] = 'warehouses';
        }

        return $features;
    }

    /**
     * Get price group mapping PPM -> Baselinker.
     */
    protected function getPriceGroupMapping(): array
    {
        return [
            1 => 'retail',        // Detaliczna
            2 => 'wholesale_std', // Dealer Standard
            3 => 'wholesale_prem',// Dealer Premium
            4 => 'workshop',      // Warsztat
            5 => 'workshop_prem', // Warsztat Premium
            6 => 'school',        // Szkółka
            7 => 'commission',    // Komis
            8 => 'employee',      // Pracownik
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ERPSyncServiceInterface Implementation (ETAP_08)
    |--------------------------------------------------------------------------
    */

    /**
     * Sync single product TO Baselinker (PUSH).
     * Wrapper for syncSingleProduct with ERPConnection context.
     */
    public function syncProductToERP(ERPConnection $connection, Product $product): array
    {
        $startTime = microtime(true);

        try {
            $inventoryId = $connection->connection_config['inventory_id'] ?? null;

            if (!$inventoryId) {
                return [
                    'success' => false,
                    'message' => 'Inventory ID not configured in connection',
                    'external_id' => null,
                    'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                ];
            }

            $result = $this->syncSingleProduct($connection, $product, $inventoryId);

            return [
                'success' => $result['success'],
                'message' => $result['message'],
                'external_id' => $result['baselinker_id'] ?? null,
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
            ];

        } catch (\Exception $e) {
            IntegrationLog::error(
                'sync_product_to_erp',
                'Failed to sync product to Baselinker: ' . $e->getMessage(),
                [
                    'product_id' => $product->id,
                    'product_sku' => $product->sku,
                    'connection_id' => $connection->id,
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                (string) $connection->id,
                $e
            );

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'external_id' => null,
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    /**
     * Sync single product FROM Baselinker (PULL).
     * Pobiera produkt z Baselinker i tworzy/aktualizuje w PPM.
     */
    public function syncProductFromERP(ERPConnection $connection, string $erpProductId): array
    {
        $startTime = microtime(true);

        try {
            $inventoryId = $connection->connection_config['inventory_id'] ?? null;

            if (!$inventoryId) {
                return [
                    'success' => false,
                    'message' => 'Inventory ID not configured',
                    'product' => null,
                ];
            }

            // Get product data from Baselinker
            $response = $this->makeRequest(
                $connection->connection_config,
                'getInventoryProductsData',
                [
                    'inventory_id' => $inventoryId,
                    'products' => [(int) $erpProductId],
                ]
            );

            if ($response['status'] !== 'SUCCESS' || empty($response['products'])) {
                return [
                    'success' => false,
                    'message' => 'Product not found in Baselinker',
                    'product' => null,
                ];
            }

            // Response keys are integers, but $erpProductId might be string
            // Try both integer and string keys for compatibility
            $blProduct = $response['products'][(int) $erpProductId]
                ?? $response['products'][$erpProductId]
                ?? reset($response['products']) // Fallback to first product if only one
                ?? null;

            if (!$blProduct) {
                Log::warning('syncProductFromERP: Product data empty', [
                    'erpProductId' => $erpProductId,
                    'response_keys' => array_keys($response['products'] ?? []),
                ]);
                return [
                    'success' => false,
                    'message' => 'Product data empty',
                    'product' => null,
                ];
            }

            // Find or create product in PPM by SKU (SKU-First Architecture!)
            $sku = $blProduct['sku'] ?? null;
            $autoGeneratedSku = false;

            if (!$sku) {
                // Auto-generate SKU from Baselinker ID if product has none
                // Format: BL-{inventory_id}-{product_id}
                $sku = 'BL-' . $inventoryId . '-' . $erpProductId;
                $autoGeneratedSku = true;

                Log::info('syncProductFromERP: Auto-generated SKU for product without SKU', [
                    'baselinker_id' => $erpProductId,
                    'generated_sku' => $sku,
                    'product_name' => $blProduct['text_fields']['name'] ?? 'Unknown',
                ]);
            }

            // Extract name from text_fields (Baselinker structure)
            // Ensure we always have a non-empty string for name
            $productName = !empty($blProduct['text_fields']['name'])
                ? $blProduct['text_fields']['name']
                : (!empty($blProduct['name'])
                    ? $blProduct['name']
                    : 'Import Baselinker #' . $erpProductId);

            $productDescription = $blProduct['text_fields']['description']
                ?? $blProduct['text_fields']['extra_field_1']
                ?? $blProduct['description']
                ?? '';

            $product = Product::where('sku', $sku)->first();

            if ($product) {
                // Update existing product
                $product->update([
                    'name' => $productName ?: $product->name,
                    'long_description' => $productDescription ?: $product->long_description,
                    'ean' => $blProduct['ean'] ?: $product->ean,
                    'weight' => $blProduct['weight'] ?: $product->weight,
                ]);

                $action = 'updated';
            } else {
                // Create new product
                $product = Product::create([
                    'sku' => $sku,
                    'name' => $productName,
                    'long_description' => $productDescription,
                    'ean' => $blProduct['ean'] ?? null,
                    'weight' => $blProduct['weight'] ?? 0,
                    'is_active' => true,
                ]);

                $action = 'created';

                if ($autoGeneratedSku) {
                    Log::info('syncProductFromERP: Created product with auto-generated SKU', [
                        'product_id' => $product->id,
                        'sku' => $sku,
                        'name' => $productName,
                        'baselinker_id' => $erpProductId,
                    ]);
                }
            }

            // Import images from Baselinker
            $imagesImported = $this->importImagesFromBaselinker($product, $blProduct['images'] ?? [], $connection);
            if ($imagesImported > 0) {
                Log::info('syncProductFromERP: Imported images', [
                    'product_id' => $product->id,
                    'images_count' => $imagesImported,
                ]);
            }

            // Import variants from Baselinker if product has variants
            $variantsImported = 0;
            if (!empty($blProduct['variants']) && is_array($blProduct['variants'])) {
                // Mark product as variant master (is_variant_master replaced has_variants)
                $product->update(['is_variant_master' => true]);

                $variantsImported = $this->importVariantsFromBaselinker($product, $blProduct['variants'], $connection);
                if ($variantsImported > 0) {
                    Log::info('syncProductFromERP: Imported variants', [
                        'product_id' => $product->id,
                        'variants_count' => $variantsImported,
                    ]);
                }
            }

            // Update or create integration mapping
            $product->integrationMappings()->updateOrCreate(
                [
                    'integration_type' => 'baselinker',
                    'integration_identifier' => $connection->instance_name,
                ],
                [
                    'external_id' => $erpProductId,
                    'external_reference' => $sku,
                    'external_data' => $blProduct,
                    'sync_status' => 'synced',
                    'last_sync_at' => Carbon::now(),
                ]
            );

            IntegrationLog::info(
                'sync_product_from_erp',
                "Product {$action} from Baselinker: {$sku}",
                [
                    'product_id' => $product->id,
                    'product_sku' => $sku,
                    'baselinker_id' => $erpProductId,
                    'action' => $action,
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                (string) $connection->id
            );

            return [
                'success' => true,
                'message' => "Product {$action} successfully",
                'product' => $product,
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
            ];

        } catch (\Exception $e) {
            IntegrationLog::error(
                'sync_product_from_erp',
                'Failed to pull product from Baselinker: ' . $e->getMessage(),
                [
                    'baselinker_product_id' => $erpProductId,
                    'connection_id' => $connection->id,
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                (string) $connection->id,
                $e
            );

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'product' => null,
            ];
        }
    }

    /**
     * Import images from Baselinker to PPM Product.
     *
     * Downloads images from Baselinker URLs and saves them to PPM storage/media.
     * Skips import if product already has images (to avoid duplicates on re-sync).
     *
     * @param Product $product The PPM product to attach images to
     * @param array $imageUrls Array of image URLs from Baselinker
     * @param ERPConnection $connection The ERP connection for logging
     * @return int Number of successfully imported images
     */
    protected function importImagesFromBaselinker(Product $product, array $imageUrls, ERPConnection $connection): int
    {
        if (empty($imageUrls)) {
            return 0;
        }

        // Skip if product already has images (avoid duplicates on re-sync)
        $existingMediaCount = $product->media()->count();
        if ($existingMediaCount > 0) {
            Log::info('importImagesFromBaselinker: Skipping - product already has images', [
                'product_id' => $product->id,
                'existing_count' => $existingMediaCount,
                'new_urls_count' => count($imageUrls),
            ]);
            return 0;
        }

        $imported = 0;
        $storagePath = 'products/' . $product->id;

        // Normalize array to 0-based indices (Baselinker uses 1-based keys)
        $imageUrls = array_values($imageUrls);

        foreach ($imageUrls as $index => $imageUrl) {
            try {
                // Download image from Baselinker
                $response = Http::timeout(30)->get($imageUrl);

                if (!$response->successful()) {
                    Log::warning('importImagesFromBaselinker: Failed to download image', [
                        'product_id' => $product->id,
                        'url' => $imageUrl,
                        'status' => $response->status(),
                    ]);
                    continue;
                }

                $imageContent = $response->body();
                $contentType = $response->header('Content-Type') ?? 'image/jpeg';

                // Determine file extension from content type
                $extension = match (true) {
                    str_contains($contentType, 'png') => 'png',
                    str_contains($contentType, 'gif') => 'gif',
                    str_contains($contentType, 'webp') => 'webp',
                    default => 'jpg',
                };

                // Generate unique filename
                $fileName = $product->sku . '_' . ($index + 1) . '_' . uniqid() . '.' . $extension;
                $filePath = $storagePath . '/' . $fileName;

                // Save to storage (public disk for web access)
                Storage::disk('public')->put($filePath, $imageContent);

                // Get image dimensions
                $tempPath = Storage::disk('public')->path($filePath);
                $imageInfo = @getimagesize($tempPath);
                $width = $imageInfo[0] ?? null;
                $height = $imageInfo[1] ?? null;

                // Create Media record
                $media = new Media();
                $media->mediable_type = Product::class;
                $media->mediable_id = $product->id;
                $media->file_name = $fileName;
                $media->original_name = basename(parse_url($imageUrl, PHP_URL_PATH)) ?: $fileName;
                $media->file_path = $filePath;
                $media->file_size = strlen($imageContent);
                $media->mime_type = $contentType;
                $media->context = Media::CONTEXT_PRODUCT_GALLERY;
                $media->width = $width;
                $media->height = $height;
                $media->alt_text = $product->name;
                $media->sort_order = $index;
                $media->is_primary = ($index === 0); // First image is primary
                $media->is_active = true;
                $media->sync_status = 'synced';
                $media->save();

                $imported++;

            } catch (\Exception $e) {
                Log::warning('importImagesFromBaselinker: Exception downloading image', [
                    'product_id' => $product->id,
                    'url' => $imageUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($imported > 0) {
            IntegrationLog::info(
                'import_images_from_erp',
                "Imported {$imported} images for product {$product->sku}",
                [
                    'product_id' => $product->id,
                    'product_sku' => $product->sku,
                    'images_imported' => $imported,
                    'images_total' => count($imageUrls),
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                (string) $connection->id
            );
        }

        return $imported;
    }

    /**
     * Import variants from Baselinker to PPM.
     *
     * Creates ProductVariant records for each Baselinker variant.
     * Uses SKU-first architecture for matching/creating variants.
     *
     * @param Product $product Parent product
     * @param array $variants Variants data from Baselinker API
     * @param ERPConnection $connection Active ERP connection
     * @return int Number of variants imported/updated
     */
    protected function importVariantsFromBaselinker(Product $product, array $variants, ERPConnection $connection): int
    {
        if (empty($variants)) {
            return 0;
        }

        $imported = 0;
        $position = 0;

        foreach ($variants as $variantId => $variantData) {
            $position++;

            // Extract variant SKU - required for SKU-first architecture
            $variantSku = $variantData['sku'] ?? null;

            // If no SKU, generate one based on parent SKU and variant ID
            if (empty($variantSku)) {
                $variantSku = $product->sku . '-VAR-' . $variantId;
            }

            // Extract variant name
            $variantName = $variantData['name'] ?? $variantData['variant_name'] ?? 'Wariant ' . $position;

            try {
                // Find existing variant by SKU (SKU-first)
                $variant = ProductVariant::where('sku', $variantSku)->first();

                if ($variant) {
                    // Update existing variant
                    $variant->update([
                        'product_id' => $product->id, // Ensure correct parent
                        'name' => $variantName,
                        'is_active' => true,
                        'position' => $position,
                    ]);
                    $action = 'updated';
                } else {
                    // Create new variant
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $variantSku,
                        'name' => $variantName,
                        'is_active' => true,
                        'is_default' => ($position === 1), // First variant is default
                        'position' => $position,
                    ]);
                    $action = 'created';
                }

                $imported++;

                Log::info("importVariantsFromBaselinker: Variant {$action}", [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'variant_sku' => $variantSku,
                    'variant_name' => $variantName,
                    'baselinker_variant_id' => $variantId,
                ]);

            } catch (\Exception $e) {
                Log::warning('importVariantsFromBaselinker: Exception creating variant', [
                    'product_id' => $product->id,
                    'variant_sku' => $variantSku,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($imported > 0) {
            IntegrationLog::info(
                'import_variants_from_erp',
                "Imported {$imported} variants for product {$product->sku}",
                [
                    'product_id' => $product->id,
                    'product_sku' => $product->sku,
                    'variants_imported' => $imported,
                    'variants_total' => count($variants),
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                (string) $connection->id
            );
        }

        return $imported;
    }

    /**
     * Sync all products to Baselinker (batch PUSH).
     * Wrapper for syncProducts with filters.
     */
    public function syncAllProducts(ERPConnection $connection, array $filters = []): array
    {
        $query = Product::where('is_active', true);

        // Apply filters
        if (!empty($filters['product_ids'])) {
            $query->whereIn('id', $filters['product_ids']);
        }

        if (!empty($filters['category_ids'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->whereIn('categories.id', $filters['category_ids']);
            });
        }

        if (!empty($filters['sku_pattern'])) {
            $query->where('sku', 'like', $filters['sku_pattern']);
        }

        $products = $query->get();

        return $this->syncProducts($connection, $products->all());
    }

    /**
     * Pull all products from Baselinker inventory (batch PULL).
     *
     * FAZA 10: Added JobProgress parameter for UI progress bar updates.
     *
     * @param ERPConnection $connection
     * @param array $filters
     * @param JobProgress|null $jobProgress Optional JobProgress for UI updates
     * @return array
     */
    public function pullAllProducts(ERPConnection $connection, array $filters = [], ?JobProgress $jobProgress = null): array
    {
        $startTime = microtime(true);
        $results = [
            'success' => true,
            'total' => 0,
            'imported' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            $inventoryId = $connection->connection_config['inventory_id'] ?? null;

            if (!$inventoryId) {
                return [
                    'success' => false,
                    'total' => 0,
                    'imported' => 0,
                    'skipped' => 0,
                    'failed' => 0,
                    'errors' => ['Inventory ID not configured'],
                ];
            }

            // Update JobProgress phase: fetching products list
            if ($jobProgress) {
                $jobProgress->update([
                    'status' => 'running',
                    'metadata' => array_merge($jobProgress->metadata ?? [], [
                        'phase' => 'fetching',
                        'phase_label' => 'Pobieranie listy produktow z Baselinker...',
                    ]),
                ]);
            }

            // OPTIMIZATION: For individual ID import, fetch specific products directly
            // instead of fetching all products and filtering
            if (!empty($filters['product_ids'])) {
                return $this->pullProductsByIds($connection, $filters['product_ids'], $jobProgress);
            }

            // Get products list from Baselinker
            $page = 1;
            $allProducts = [];

            do {
                $response = $this->makeRequest(
                    $connection->connection_config,
                    'getInventoryProductsList',
                    [
                        'inventory_id' => $inventoryId,
                        'filter_limit' => 1000,
                        'filter_page' => $page,
                    ]
                );

                if ($response['status'] !== 'SUCCESS') {
                    $results['errors'][] = 'API Error: ' . ($response['error_message'] ?? 'Unknown');
                    break;
                }

                $products = $response['products'] ?? [];
                $allProducts = array_merge($allProducts, $products);

                // Update phase with page info
                if ($jobProgress) {
                    $jobProgress->update([
                        'metadata' => array_merge($jobProgress->metadata ?? [], [
                            'phase' => 'fetching',
                            'phase_label' => "Pobrano " . count($allProducts) . " produktow (strona {$page})...",
                        ]),
                    ]);
                }

                $page++;

                // Rate limiting
                usleep(1000000); // 1 second

            } while (count($products) === 1000);

            $results['total'] = count($allProducts);

            // Update JobProgress with total count
            if ($jobProgress) {
                $jobProgress->update([
                    'total_count' => $results['total'],
                    'metadata' => array_merge($jobProgress->metadata ?? [], [
                        'phase' => 'processing',
                        'phase_label' => "Przetwarzanie 0/{$results['total']} produktow...",
                    ]),
                ]);
            }

            // Apply filters if provided (FAZA 10: support for ID, SKU, Name search types)
            $productIds = array_keys($allProducts);
            $searchType = $filters['search_type'] ?? null;

            // Filter by product IDs (search_type = 'id' or direct product_ids)
            if (!empty($filters['product_ids'])) {
                $productIds = array_intersect($productIds, array_map('intval', $filters['product_ids']));
                $results['total'] = count($productIds);

                if ($jobProgress) {
                    $jobProgress->update(['total_count' => $results['total']]);
                }
            }

            // Filter by selected products from name search (search_type = 'name')
            // selected_products contains Baselinker product IDs from search results
            if (!empty($filters['selected_products'])) {
                $productIds = array_intersect($productIds, array_map('intval', $filters['selected_products']));
                $results['total'] = count($productIds);

                if ($jobProgress) {
                    $jobProgress->update(['total_count' => $results['total']]);
                }
            }

            // Filter by SKUs (search_type = 'sku')
            if (!empty($filters['product_skus'])) {
                $skuFilter = array_map('trim', $filters['product_skus']);
                $matchedIds = [];

                foreach ($allProducts as $productId => $productData) {
                    $productSku = $productData['sku'] ?? '';
                    if (in_array($productSku, $skuFilter, true)) {
                        $matchedIds[] = $productId;
                    }
                }

                $productIds = $matchedIds;
                $results['total'] = count($productIds);

                if ($jobProgress) {
                    $jobProgress->update(['total_count' => $results['total']]);
                }
            }

            // Process each product
            $currentIndex = 0;
            foreach ($productIds as $productId) {
                $productData = $allProducts[$productId] ?? [];
                $pullResult = $this->syncProductFromERP($connection, (string) $productId);

                if ($pullResult['success']) {
                    $results['imported']++;
                } else {
                    $results['skipped']++;
                    $results['failed']++;
                    if (count($results['errors']) < 10) {
                        $results['errors'][] = "SKU: " . ($productData['sku'] ?? $productId) . " - " . $pullResult['message'];
                    }
                }

                $currentIndex++;

                // Update JobProgress every 5 items or at the end
                if ($jobProgress && ($currentIndex % 5 === 0 || $currentIndex === $results['total'])) {
                    $jobProgress->update([
                        'current_count' => $currentIndex,
                        'error_count' => $results['failed'],
                        'metadata' => array_merge($jobProgress->metadata ?? [], [
                            'phase' => 'processing',
                            'phase_label' => "Przetwarzanie {$currentIndex}/{$results['total']} produktow...",
                            'imported' => $results['imported'],
                            'skipped' => $results['skipped'],
                        ]),
                    ]);
                }

                // Rate limiting
                usleep(500000); // 0.5 second
            }

            IntegrationLog::info(
                'pull_all_products',
                "Pulled {$results['imported']} products from Baselinker",
                [
                    'connection_id' => $connection->id,
                    'total' => $results['total'],
                    'imported' => $results['imported'],
                    'skipped' => $results['skipped'],
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                (string) $connection->id
            );

        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = 'Exception: ' . $e->getMessage();

            IntegrationLog::error(
                'pull_all_products',
                'Failed to pull products from Baselinker: ' . $e->getMessage(),
                [
                    'connection_id' => $connection->id,
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                (string) $connection->id,
                $e
            );
        }

        $results['duration_seconds'] = round((microtime(true) - $startTime), 2);

        return $results;
    }

    /**
     * Pull specific products by their Baselinker IDs.
     *
     * Uses getInventoryProductsData API for efficient direct fetch instead of
     * listing all products and filtering. This is MUCH faster for individual imports.
     *
     * @param ERPConnection $connection
     * @param array $productIds Array of Baselinker product IDs to fetch
     * @param JobProgress|null $jobProgress
     * @return array
     */
    protected function pullProductsByIds(ERPConnection $connection, array $productIds, ?JobProgress $jobProgress = null): array
    {
        $startTime = microtime(true);
        $results = [
            'success' => true,
            'total' => count($productIds),
            'imported' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            $inventoryId = $connection->connection_config['inventory_id'] ?? null;

            if (!$inventoryId) {
                return [
                    'success' => false,
                    'total' => 0,
                    'imported' => 0,
                    'skipped' => 0,
                    'failed' => 0,
                    'errors' => ['Inventory ID not configured'],
                ];
            }

            // Convert to integers
            $productIds = array_map('intval', $productIds);

            Log::info('pullProductsByIds: Starting direct fetch', [
                'connection_id' => $connection->id,
                'inventory_id' => $inventoryId,
                'product_ids' => $productIds,
                'count' => count($productIds),
            ]);

            // Update JobProgress
            if ($jobProgress) {
                $jobProgress->update([
                    'status' => 'running',
                    'total_count' => count($productIds),
                    'metadata' => array_merge($jobProgress->metadata ?? [], [
                        'phase' => 'fetching',
                        'phase_label' => 'Pobieranie danych produktow z Baselinker...',
                    ]),
                ]);
            }

            // Fetch products directly by IDs using getInventoryProductsData
            // API limit: max 1000 products per request
            $allProducts = [];
            $chunks = array_chunk($productIds, 1000);

            foreach ($chunks as $chunk) {
                $response = $this->makeRequest(
                    $connection->connection_config,
                    'getInventoryProductsData',
                    [
                        'inventory_id' => $inventoryId,
                        'products' => $chunk,
                    ]
                );

                if ($response['status'] !== 'SUCCESS') {
                    Log::warning('pullProductsByIds: API error', [
                        'error' => $response['error_message'] ?? 'Unknown',
                        'chunk_size' => count($chunk),
                    ]);
                    $results['errors'][] = 'API Error: ' . ($response['error_message'] ?? 'Unknown');
                    continue;
                }

                $products = $response['products'] ?? [];
                // Use + operator instead of array_merge to preserve numeric keys!
                // array_merge re-indexes numeric keys (176692083 -> 0), breaking the ID reference
                $allProducts = $allProducts + $products;

                Log::info('pullProductsByIds: Fetched chunk', [
                    'requested' => count($chunk),
                    'received' => count($products),
                ]);

                usleep(500000); // 0.5s rate limit
            }

            $results['total'] = count($allProducts);

            if (empty($allProducts)) {
                Log::warning('pullProductsByIds: No products returned from API', [
                    'requested_ids' => $productIds,
                ]);
                $results['errors'][] = 'Nie znaleziono produktow o podanych ID w Baselinker';
            }

            // Update JobProgress
            if ($jobProgress) {
                $jobProgress->update([
                    'total_count' => $results['total'],
                    'metadata' => array_merge($jobProgress->metadata ?? [], [
                        'phase' => 'processing',
                        'phase_label' => "Przetwarzanie 0/{$results['total']} produktow...",
                    ]),
                ]);
            }

            // Process each fetched product
            $currentIndex = 0;
            foreach ($allProducts as $productId => $productData) {
                $pullResult = $this->syncProductFromERP($connection, (string) $productId);

                if ($pullResult['success']) {
                    $results['imported']++;
                    Log::info('pullProductsByIds: Product imported', [
                        'baselinker_id' => $productId,
                        'ppm_product_id' => $pullResult['product']->id ?? null,
                        'sku' => $pullResult['product']->sku ?? null,
                    ]);
                } else {
                    $results['skipped']++;
                    $results['failed']++;
                    if (count($results['errors']) < 10) {
                        $results['errors'][] = "ID: {$productId} - " . $pullResult['message'];
                    }
                    Log::warning('pullProductsByIds: Product import failed', [
                        'baselinker_id' => $productId,
                        'error' => $pullResult['message'],
                    ]);
                }

                $currentIndex++;

                // Update JobProgress
                if ($jobProgress && ($currentIndex % 5 === 0 || $currentIndex === $results['total'])) {
                    $jobProgress->update([
                        'current_count' => $currentIndex,
                        'error_count' => $results['failed'],
                        'metadata' => array_merge($jobProgress->metadata ?? [], [
                            'phase' => 'processing',
                            'phase_label' => "Przetwarzanie {$currentIndex}/{$results['total']} produktow...",
                            'imported' => $results['imported'],
                            'skipped' => $results['skipped'],
                        ]),
                    ]);
                }

                usleep(500000); // 0.5s rate limit
            }

            IntegrationLog::info(
                'pull_products_by_ids',
                "Pulled {$results['imported']}/{$results['total']} products by IDs from Baselinker",
                [
                    'connection_id' => $connection->id,
                    'requested_ids' => $productIds,
                    'total' => $results['total'],
                    'imported' => $results['imported'],
                    'skipped' => $results['skipped'],
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                (string) $connection->id
            );

        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = 'Exception: ' . $e->getMessage();

            Log::error('pullProductsByIds: Exception', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            IntegrationLog::error(
                'pull_products_by_ids',
                'Failed to pull products by IDs: ' . $e->getMessage(),
                [
                    'connection_id' => $connection->id,
                    'product_ids' => $productIds,
                ],
                IntegrationLog::INTEGRATION_BASELINKER,
                (string) $connection->id,
                $e
            );
        }

        $results['duration_seconds'] = round((microtime(true) - $startTime), 2);
        $results['success'] = $results['failed'] === 0 && $results['imported'] > 0;

        return $results;
    }

    /**
     * Sync stock for single product (interface method).
     */
    public function syncStock(ERPConnection $connection, Product $product): array
    {
        try {
            $inventoryId = $connection->connection_config['inventory_id'] ?? null;

            if (!$inventoryId) {
                return [
                    'success' => false,
                    'message' => 'Inventory ID not configured',
                ];
            }

            // Get Baselinker product ID from mapping
            $mapping = $product->integrationMappings()
                ->where('integration_type', 'baselinker')
                ->where('integration_identifier', $connection->instance_name)
                ->first();

            if (!$mapping || !$mapping->external_id) {
                return [
                    'success' => false,
                    'message' => 'Product not mapped to Baselinker',
                ];
            }

            $this->syncProductStock($connection, $product, $inventoryId, $mapping->external_id);

            return [
                'success' => true,
                'message' => 'Stock synced successfully',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync prices for single product (interface method).
     */
    public function syncPrices(ERPConnection $connection, Product $product): array
    {
        try {
            $inventoryId = $connection->connection_config['inventory_id'] ?? null;

            if (!$inventoryId) {
                return [
                    'success' => false,
                    'message' => 'Inventory ID not configured',
                ];
            }

            // Get Baselinker product ID from mapping
            $mapping = $product->integrationMappings()
                ->where('integration_type', 'baselinker')
                ->where('integration_identifier', $connection->instance_name)
                ->first();

            if (!$mapping || !$mapping->external_id) {
                return [
                    'success' => false,
                    'message' => 'Product not mapped to Baselinker',
                ];
            }

            $this->syncProductPrices($connection, $product, $inventoryId, $mapping->external_id);

            return [
                'success' => true,
                'message' => 'Prices synced successfully',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get ERP type identifier.
     */
    public function getERPType(): string
    {
        return ERPConnection::ERP_BASELINKER;
    }

    /**
     * Get supported features for Baselinker.
     */
    public function getSupportedFeatures(): array
    {
        return [
            'products',
            'stock',
            'prices',
            'orders',
            'multi_inventory',
            'warehouses',
            'categories',
            'bidirectional_sync',
        ];
    }

    /**
     * Search products in Baselinker by name/SKU.
     *
     * FAZA 10: Name search for ERP Import in ProductList.
     *
     * @param ERPConnection $connection
     * @param string $query Search query (min 3 chars)
     * @return array ['products' => [['id' => ..., 'name' => ..., 'sku' => ...], ...]]
     */
    public function searchProducts(ERPConnection $connection, string $query): array
    {
        try {
            $inventoryId = $connection->connection_config['inventory_id'] ?? null;

            if (!$inventoryId) {
                return ['products' => [], 'error' => 'Inventory ID not configured'];
            }

            if (strlen($query) < 3) {
                return ['products' => []];
            }

            // Baselinker API: getInventoryProductsList with filter_text_search
            $response = $this->makeRequest(
                $connection->connection_config,
                'getInventoryProductsList',
                [
                    'inventory_id' => $inventoryId,
                    'filter_text_search' => $query,
                    'filter_limit' => 50, // Limit results for UI performance
                ]
            );

            if ($response['status'] !== 'SUCCESS') {
                Log::warning('BaselinkerService::searchProducts API error', [
                    'error' => $response['error_message'] ?? 'Unknown',
                    'query' => $query,
                ]);
                return ['products' => [], 'error' => $response['error_message'] ?? 'API Error'];
            }

            // Transform response to expected format
            $products = [];
            foreach ($response['products'] ?? [] as $productId => $productData) {
                $products[] = [
                    'id' => $productId,
                    'name' => $productData['name'] ?? $productData['text_fields']['name'] ?? 'Unknown',
                    'sku' => $productData['sku'] ?? '',
                    'ean' => $productData['ean'] ?? '',
                ];
            }

            return ['products' => $products];

        } catch (\Exception $e) {
            Log::error('BaselinkerService::searchProducts exception', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return ['products' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get products by specific IDs from Baselinker.
     *
     * FAZA 10: Support for ID-based import.
     *
     * @param ERPConnection $connection
     * @param array $productIds Array of Baselinker product IDs
     * @return array
     */
    public function getProductsByIds(ERPConnection $connection, array $productIds): array
    {
        try {
            $inventoryId = $connection->connection_config['inventory_id'] ?? null;

            if (!$inventoryId) {
                return ['products' => [], 'error' => 'Inventory ID not configured'];
            }

            if (empty($productIds)) {
                return ['products' => []];
            }

            // Baselinker API: getInventoryProductsData for specific IDs
            $response = $this->makeRequest(
                $connection->connection_config,
                'getInventoryProductsData',
                [
                    'inventory_id' => $inventoryId,
                    'products' => array_map('intval', $productIds),
                ]
            );

            if ($response['status'] !== 'SUCCESS') {
                return ['products' => [], 'error' => $response['error_message'] ?? 'API Error'];
            }

            return ['products' => $response['products'] ?? []];

        } catch (\Exception $e) {
            Log::error('BaselinkerService::getProductsByIds exception', [
                'product_ids' => $productIds,
                'error' => $e->getMessage(),
            ]);

            return ['products' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get products by SKUs from Baselinker.
     *
     * FAZA 10: Support for SKU-based import.
     *
     * @param ERPConnection $connection
     * @param array $skus Array of SKUs to search
     * @return array
     */
    public function getProductsBySkus(ERPConnection $connection, array $skus): array
    {
        try {
            $inventoryId = $connection->connection_config['inventory_id'] ?? null;

            if (!$inventoryId) {
                return ['products' => [], 'error' => 'Inventory ID not configured'];
            }

            if (empty($skus)) {
                return ['products' => []];
            }

            $allProducts = [];

            // Search for each SKU (Baselinker doesn't have multi-SKU filter)
            foreach ($skus as $sku) {
                $response = $this->makeRequest(
                    $connection->connection_config,
                    'getInventoryProductsList',
                    [
                        'inventory_id' => $inventoryId,
                        'filter_sku' => trim($sku),
                        'filter_limit' => 10,
                    ]
                );

                if ($response['status'] === 'SUCCESS' && !empty($response['products'])) {
                    foreach ($response['products'] as $productId => $productData) {
                        $allProducts[$productId] = $productData;
                    }
                }

                // Rate limiting
                usleep(100000); // 0.1 second between SKU searches
            }

            return ['products' => $allProducts];

        } catch (\Exception $e) {
            Log::error('BaselinkerService::getProductsBySkus exception', [
                'skus' => $skus,
                'error' => $e->getMessage(),
            ]);

            return ['products' => [], 'error' => $e->getMessage()];
        }
    }
}