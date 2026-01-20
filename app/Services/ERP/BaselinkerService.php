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
     *
     * ETAP_08.5: Enhanced logging for CREATE vs UPDATE decision.
     * ETAP_08.6: Added variant sync for is_variant_master products.
     */
    protected function syncSingleProduct(ERPConnection $connection, Product $product, string $inventoryId): array
    {
        try {
            // Get or create Baselinker product mapping
            $mapping = $product->integrationMappings()
                ->where('integration_type', 'baselinker')
                ->where('integration_identifier', $connection->instance_name)
                ->first();

            $result = null;
            $baselinkerProductId = null;

            if ($mapping && $mapping->external_id) {
                // ETAP_08.5: Log UPDATE decision with mapping details
                Log::info('Baselinker syncSingleProduct: UPDATE existing product', [
                    'product_id' => $product->id,
                    'product_sku' => $product->sku,
                    'product_name' => $product->name,
                    'external_id' => $mapping->external_id,
                    'mapping_id' => $mapping->id,
                    'inventory_id' => $inventoryId,
                    'connection_name' => $connection->instance_name,
                    'last_sync_at' => $mapping->last_sync_at?->toDateTimeString(),
                ]);

                $result = $this->updateBaselinkerProduct($connection, $product, $inventoryId, $mapping->external_id);
                $baselinkerProductId = $mapping->external_id;
            } else {
                // ETAP_08.5: Log CREATE decision
                Log::info('Baselinker syncSingleProduct: CREATE new product (no mapping found)', [
                    'product_id' => $product->id,
                    'product_sku' => $product->sku,
                    'product_name' => $product->name,
                    'inventory_id' => $inventoryId,
                    'connection_name' => $connection->instance_name,
                    'mapping_exists' => $mapping !== null,
                    'mapping_external_id' => $mapping?->external_id,
                ]);

                $result = $this->createBaselinkerProduct($connection, $product, $inventoryId);
                $baselinkerProductId = $result['baselinker_id'] ?? null;
            }

            // ETAP_08.6: Sync variants if product is variant master
            if ($result['success'] && $baselinkerProductId && $product->is_variant_master) {
                // Ensure variants are loaded
                if (!$product->relationLoaded('variants')) {
                    $product->load('variants');
                }

                if ($product->variants->isNotEmpty()) {
                    Log::info('syncSingleProduct: Syncing variants', [
                        'product_id' => $product->id,
                        'product_sku' => $product->sku,
                        'variants_count' => $product->variants->count(),
                        'parent_baselinker_id' => $baselinkerProductId,
                    ]);

                    $variantResults = $this->syncProductVariants(
                        $connection,
                        $product,
                        $inventoryId,
                        $baselinkerProductId
                    );

                    // Add variant results to response
                    $result['variants'] = $variantResults;
                }
            }

            // ETAP_08.6: Mark product media as synced to ERP (for Gallery checkboxes)
            if ($result['success'] && $baselinkerProductId) {
                $mediaSynced = $this->markProductMediaAsSyncedToErp($connection, $product, $baselinkerProductId);
                $result['media_synced'] = $mediaSynced;
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Baselinker syncSingleProduct: Exception', [
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'skipped' => false,
                'message' => 'Sync exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create new product in Baselinker.
     *
     * ETAP_08.5 FIX: Uses buildBaselinkerProductData() to get ERP-specific data
     * from ProductErpData table (where user edits are stored in ERP TAB).
     */
    protected function createBaselinkerProduct(ERPConnection $connection, Product $product, string $inventoryId): array
    {
        // ETAP_08.5 FIX: Get ERP-specific data (with fallback to Product defaults)
        $productData = $this->buildBaselinkerProductData($connection, $product);

        Log::info('Baselinker createBaselinkerProduct: Using ERP data', [
            'product_id' => $product->id,
            'product_sku' => $product->sku,
            'product_name_from_model' => $product->name,
            'name_being_sent' => $productData['name'],
            'connection_id' => $connection->id,
        ]);

        try {
            // ETAP_08.5 FIX: text_fields as PHP array - makeRequest() calls json_encode($parameters)
            // so passing array here results in proper nested JSON: {"text_fields":{"name":"..."}}
            // NOT double-encoded: {"text_fields":"{\"name\":\"...\"}"}
            $textFields = [
                'name' => $productData['name'],
                'description' => $productData['description'],
                'description_extra1' => $productData['description_extra1'],
            ];

            // ETAP_08.5 FIX: For CREATE, product_id MUST be empty!
            // Baselinker API: product_id = empty → CREATE new product (BL assigns ID)
            //                 product_id = existing ID → UPDATE existing product
            // Passing SKU as product_id caused ERROR_PRODUCT_ID because BL
            // tried to find product with that ID (which doesn't exist yet!)
            $requestParams = [
                'inventory_id' => $inventoryId,
                // NO product_id for CREATE - let Baselinker assign it!
                'parent_id' => 0,
                'is_bundle' => false,
                'text_fields' => $textFields,  // PHP array - proper format!
                'sku' => $productData['sku'],
                'ean' => $productData['ean'],
                'tax_rate' => $productData['tax_rate'],
                'weight' => $productData['weight'],
                'height' => $productData['height'],
                'width' => $productData['width'],
                'length' => $productData['length'],
            ];

            // ETAP_08.5: Add images if available (stdClass requires get_object_vars check)
            if (isset($productData['images']) && count(get_object_vars($productData['images'])) > 0) {
                $requestParams['images'] = $productData['images'];
            }

            $response = $this->makeRequest(
                $connection->connection_config,
                'addInventoryProduct',
                $requestParams
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
     *
     * ETAP_08.5 FIX: Uses buildBaselinkerProductData() to get ERP-specific data
     * from ProductErpData table (where user edits are stored in ERP TAB).
     */
    protected function updateBaselinkerProduct(ERPConnection $connection, Product $product, string $inventoryId, string $baselinkerProductId): array
    {
        // ETAP_08.5 FIX: Get ERP-specific data (with fallback to Product defaults)
        $productData = $this->buildBaselinkerProductData($connection, $product);

        Log::info('Baselinker updateBaselinkerProduct: Using ERP data', [
            'product_id' => $product->id,
            'product_sku' => $product->sku,
            'product_name_from_model' => $product->name,
            'name_being_sent' => $productData['name'],
            'baselinker_product_id' => $baselinkerProductId,
            'connection_id' => $connection->id,
        ]);

        try {
            // ETAP_08.5 FIX: text_fields as PHP array - makeRequest() calls json_encode($parameters)
            // so passing array here results in proper nested JSON: {"text_fields":{"name":"..."}}
            // NOT double-encoded: {"text_fields":"{\"name\":\"...\"}"}
            $textFields = [
                'name' => $productData['name'],
                'description' => $productData['description'],
                'description_extra1' => $productData['description_extra1'],
            ];

            // ETAP_08.5: Build request params - same pattern as createBaselinkerProduct
            $requestParams = [
                'inventory_id' => $inventoryId,
                'product_id' => $baselinkerProductId,  // For UPDATE - use existing BL product ID
                'sku' => $productData['sku'],
                'ean' => $productData['ean'],
                'text_fields' => $textFields,  // PHP array - proper format!
                'tax_rate' => $productData['tax_rate'],
                'weight' => $productData['weight'],
                'height' => $productData['height'],
                'width' => $productData['width'],
                'length' => $productData['length'],
            ];

            // ETAP_08.5: Add images if available (stdClass requires get_object_vars check)
            if (isset($productData['images']) && count(get_object_vars($productData['images'])) > 0) {
                $requestParams['images'] = $productData['images'];
            }

            $response = $this->makeRequest(
                $connection->connection_config,
                'addInventoryProduct',
                $requestParams
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
                // ETAP_08.5: Handle ERROR_PRODUCT_ID - product was deleted in Baselinker
                // Auto-recreate: delete stale mapping and create new product
                $errorCode = $response['error_code'] ?? '';

                if ($errorCode === 'ERROR_PRODUCT_ID') {
                    Log::warning('Baselinker updateBaselinkerProduct: Product not found in BL - auto-recreating', [
                        'product_id' => $product->id,
                        'product_sku' => $product->sku,
                        'stale_external_id' => $baselinkerProductId,
                        'error_message' => $response['error_message'] ?? 'Unknown',
                    ]);

                    // Delete stale mapping
                    $staleMapping = $product->integrationMappings()
                        ->where('integration_type', 'baselinker')
                        ->where('integration_identifier', $connection->instance_name)
                        ->first();

                    if ($staleMapping) {
                        $staleMapping->delete();
                        Log::info('Baselinker: Deleted stale mapping', [
                            'mapping_id' => $staleMapping->id,
                            'external_id' => $staleMapping->external_id,
                        ]);
                    }

                    // Recreate product in Baselinker
                    Log::info('Baselinker: Recreating product after stale mapping cleanup', [
                        'product_id' => $product->id,
                        'product_sku' => $product->sku,
                    ]);

                    return $this->createBaselinkerProduct($connection, $product, $inventoryId);
                }

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

    /*
    |--------------------------------------------------------------------------
    | ETAP_08.6: VARIANT SYNC METHODS (PUSH)
    |--------------------------------------------------------------------------
    */

    /**
     * Sync product variants to Baselinker.
     *
     * ETAP_08.6: Creates/updates variants as child products linked to main product.
     * Baselinker API uses parent_id to link variants to their parent product.
     *
     * @param ERPConnection $connection
     * @param Product $product Parent product (must be variant master)
     * @param string $inventoryId
     * @param string $parentBaselinkerProductId Main product's Baselinker ID
     * @return array Results summary
     */
    protected function syncProductVariants(
        ERPConnection $connection,
        Product $product,
        string $inventoryId,
        string $parentBaselinkerProductId
    ): array {
        $results = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        if (!$product->is_variant_master || $product->variants->isEmpty()) {
            return $results;
        }

        $results['total'] = $product->variants->count();

        foreach ($product->variants as $variant) {
            try {
                // Check if variant has existing Baselinker mapping
                $variantMapping = $variant->getIntegrationMapping('baselinker', $connection->instance_name);

                if ($variantMapping && $variantMapping->external_id) {
                    // UPDATE existing variant
                    $updateResult = $this->updateVariantInBaselinker(
                        $connection,
                        $product,
                        $variant,
                        $inventoryId,
                        $variantMapping->external_id
                    );

                    if ($updateResult['success']) {
                        $results['updated']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Variant {$variant->sku}: " . $updateResult['message'];
                    }
                } else {
                    // CREATE new variant
                    $createResult = $this->createVariantInBaselinker(
                        $connection,
                        $product,
                        $variant,
                        $inventoryId,
                        $parentBaselinkerProductId
                    );

                    if ($createResult['success']) {
                        $results['created']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Variant {$variant->sku}: " . $createResult['message'];
                    }
                }

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Variant {$variant->sku}: Exception - " . $e->getMessage();

                Log::error('syncProductVariants: Exception for variant', [
                    'variant_id' => $variant->id,
                    'variant_sku' => $variant->sku,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('syncProductVariants: Completed', [
            'product_id' => $product->id,
            'product_sku' => $product->sku,
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Create variant in Baselinker as child of parent product.
     *
     * ETAP_08.6: Uses parent_id to link variant to main product.
     * Variant inherits inventory from parent but has own SKU/EAN/stock.
     *
     * @param ERPConnection $connection
     * @param Product $mainProduct Parent product
     * @param ProductVariant $variant Variant to create
     * @param string $inventoryId
     * @param string $parentBaselinkerProductId Parent's Baselinker product ID
     * @return array
     */
    protected function createVariantInBaselinker(
        ERPConnection $connection,
        Product $mainProduct,
        ProductVariant $variant,
        string $inventoryId,
        string $parentBaselinkerProductId
    ): array {
        Log::info('createVariantInBaselinker: Creating variant', [
            'variant_id' => $variant->id,
            'variant_sku' => $variant->sku,
            'parent_product_id' => $mainProduct->id,
            'parent_baselinker_id' => $parentBaselinkerProductId,
        ]);

        try {
            $variantData = $this->buildVariantProductData($mainProduct, $variant);

            $textFields = [
                'name' => $variantData['name'],
                'description' => $variantData['description'],
            ];

            $requestParams = [
                'inventory_id' => $inventoryId,
                // NO product_id for CREATE - Baselinker assigns ID
                'parent_id' => $parentBaselinkerProductId, // KEY: Link to parent!
                'is_bundle' => false,
                'text_fields' => $textFields, // PHP array
                'sku' => $variantData['sku'],
                'ean' => $variantData['ean'],
                'tax_rate' => $variantData['tax_rate'],
                'weight' => $variantData['weight'],
            ];

            // ETAP_08.8 FIX: Always send images (includes empty strings to prevent inheritance)
            if (isset($variantData['images'])) {
                $requestParams['images'] = $variantData['images'];

                // Count actual images (non-empty values)
                $actualImages = 0;
                foreach (get_object_vars($variantData['images']) as $val) {
                    if (!empty($val)) {
                        $actualImages++;
                    }
                }

                Log::info('createVariantInBaselinker: Setting images for new variant', [
                    'variant_sku' => $variant->sku,
                    'actual_images' => $actualImages,
                    'empty_slots' => 16 - $actualImages,
                ]);
            }

            $response = $this->makeRequest(
                $connection->connection_config,
                'addInventoryProduct',
                $requestParams
            );

            if ($response['status'] === 'SUCCESS') {
                $baselinkerVariantId = $response['product_id'];

                // Save IntegrationMapping for variant
                $variant->findOrCreateIntegrationMapping(
                    'baselinker',
                    $connection->instance_name,
                    [
                        'external_id' => $baselinkerVariantId,
                        'external_reference' => $variant->sku,
                        'external_data' => $response,
                        'sync_status' => 'synced',
                        'last_sync_at' => Carbon::now(),
                    ]
                );

                // Sync variant stock
                $this->syncVariantStock($connection, $variant, $inventoryId, $baselinkerVariantId);

                // Sync variant prices
                $this->syncVariantPrices($connection, $variant, $inventoryId, $baselinkerVariantId);

                Log::info('createVariantInBaselinker: Created successfully', [
                    'variant_id' => $variant->id,
                    'variant_sku' => $variant->sku,
                    'baselinker_variant_id' => $baselinkerVariantId,
                ]);

                return [
                    'success' => true,
                    'message' => 'Variant created successfully',
                    'baselinker_id' => $baselinkerVariantId,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'API error: ' . ($response['error_message'] ?? 'Unknown'),
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update existing variant in Baselinker.
     *
     * @param ERPConnection $connection
     * @param Product $mainProduct Parent product
     * @param ProductVariant $variant Variant to update
     * @param string $inventoryId
     * @param string $baselinkerVariantId Existing Baselinker variant ID
     * @return array
     */
    protected function updateVariantInBaselinker(
        ERPConnection $connection,
        Product $mainProduct,
        ProductVariant $variant,
        string $inventoryId,
        string $baselinkerVariantId
    ): array {
        Log::info('updateVariantInBaselinker: Updating variant', [
            'variant_id' => $variant->id,
            'variant_sku' => $variant->sku,
            'baselinker_variant_id' => $baselinkerVariantId,
        ]);

        try {
            $variantData = $this->buildVariantProductData($mainProduct, $variant);

            $textFields = [
                'name' => $variantData['name'],
                'description' => $variantData['description'],
            ];

            $requestParams = [
                'inventory_id' => $inventoryId,
                'product_id' => $baselinkerVariantId, // UPDATE existing
                'text_fields' => $textFields, // PHP array
                'sku' => $variantData['sku'],
                'ean' => $variantData['ean'],
                'tax_rate' => $variantData['tax_rate'],
                'weight' => $variantData['weight'],
            ];

            // ETAP_08.8 FIX: Always send images to clear old ones and set new ones
            // buildVariantProductData() fills all 16 positions (real images + empty strings for deletion)
            if (isset($variantData['images'])) {
                $requestParams['images'] = $variantData['images'];

                // Count actual images (non-empty values)
                $actualImages = 0;
                foreach (get_object_vars($variantData['images']) as $val) {
                    if (!empty($val)) {
                        $actualImages++;
                    }
                }

                Log::info('updateVariantInBaselinker: Sending images to Baselinker', [
                    'variant_sku' => $variant->sku,
                    'actual_images' => $actualImages,
                    'empty_slots' => 16 - $actualImages,
                    'purpose' => 'Clear old images and set new ones',
                ]);
            }

            $response = $this->makeRequest(
                $connection->connection_config,
                'addInventoryProduct',
                $requestParams
            );

            if ($response['status'] === 'SUCCESS') {
                // Update mapping
                $mapping = $variant->getIntegrationMapping('baselinker', $connection->instance_name);
                if ($mapping) {
                    $mapping->update([
                        'external_data' => $response,
                        'sync_status' => 'synced',
                        'last_sync_at' => Carbon::now(),
                        'error_message' => null,
                        'error_count' => 0,
                    ]);
                }

                // Sync variant stock and prices
                $this->syncVariantStock($connection, $variant, $inventoryId, $baselinkerVariantId);
                $this->syncVariantPrices($connection, $variant, $inventoryId, $baselinkerVariantId);

                return [
                    'success' => true,
                    'message' => 'Variant updated successfully',
                ];
            } else {
                // Handle deleted variant in Baselinker (ERROR_PRODUCT_ID)
                if (($response['error_code'] ?? '') === 'ERROR_PRODUCT_ID') {
                    Log::warning('updateVariantInBaselinker: Variant not found, recreating', [
                        'variant_sku' => $variant->sku,
                        'stale_id' => $baselinkerVariantId,
                    ]);

                    // Delete stale mapping
                    $staleMapping = $variant->getIntegrationMapping('baselinker', $connection->instance_name);
                    if ($staleMapping) {
                        $staleMapping->delete();
                    }

                    // Get parent Baselinker ID
                    $parentMapping = $mainProduct->integrationMappings()
                        ->where('integration_type', 'baselinker')
                        ->where('integration_identifier', $connection->instance_name)
                        ->first();

                    if ($parentMapping && $parentMapping->external_id) {
                        return $this->createVariantInBaselinker(
                            $connection,
                            $mainProduct,
                            $variant,
                            $inventoryId,
                            $parentMapping->external_id
                        );
                    }
                }

                return [
                    'success' => false,
                    'message' => 'API error: ' . ($response['error_message'] ?? 'Unknown'),
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build variant product data for Baselinker API.
     *
     * @param Product $mainProduct Parent product
     * @param ProductVariant $variant
     * @return array
     */
    protected function buildVariantProductData(Product $mainProduct, ProductVariant $variant): array
    {
        // Get variant attributes for name suffix
        $attributeSuffix = '';
        if ($variant->attributes && $variant->attributes->isNotEmpty()) {
            $attrs = $variant->attributes->pluck('value')->toArray();
            $attributeSuffix = ' - ' . implode(', ', $attrs);
        }

        // ETAP_08.8 FIX: Build images for variant
        // Uses same format as main product: stdClass with "url:" prefixed URLs
        $imagesObject = new \stdClass();
        $imageIndex = 0;
        $maxImages = 16; // Baselinker limit

        // Load variant images if not already loaded
        if (!$variant->relationLoaded('images')) {
            $variant->load('images');
        }

        foreach ($variant->images as $variantImage) {
            if ($imageIndex >= $maxImages) {
                break;
            }

            // Get full URL from VariantImage accessor
            $imageUrl = $variantImage->url;

            // DEBUG: Log actual URL for troubleshooting
            Log::info('buildVariantProductData: Processing variant image', [
                'variant_id' => $variant->id,
                'variant_sku' => $variant->sku,
                'image_id' => $variantImage->id,
                'image_path' => $variantImage->image_path,
                'image_url_raw' => $imageUrl,
                'is_cover' => $variantImage->is_cover,
            ]);

            if ($imageUrl && !empty($imageUrl) && !str_contains($imageUrl, 'placeholder')) {
                // CRITICAL: Baselinker requires "url:" prefix for URL format images!
                $imagesObject->{(string)$imageIndex} = 'url:' . $imageUrl;
                $imageIndex++;
            }
        }

        // ETAP_08.8 FIX: Fill remaining positions with empty strings to CLEAR old images!
        // Baselinker documentation: "delete images by sending an empty string at a specific position"
        // This ensures old inherited/stale images are removed when variant has fewer images
        for ($i = $imageIndex; $i < $maxImages; $i++) {
            $imagesObject->{(string)$i} = '';
        }

        Log::info('buildVariantProductData: Images prepared for variant', [
            'variant_id' => $variant->id,
            'variant_sku' => $variant->sku,
            'new_images_count' => $imageIndex,
            'empty_slots_count' => $maxImages - $imageIndex,
            'images_json' => json_encode($imagesObject),
        ]);

        return [
            'name' => $variant->name ?: ($mainProduct->name . $attributeSuffix),
            'description' => $mainProduct->description ?: '',
            'sku' => $variant->sku,
            'ean' => '', // Variants may have own EAN in future
            'tax_rate' => $mainProduct->tax_rate ?: 23,
            'weight' => $mainProduct->weight ?: 0,
            'images' => $imagesObject, // ETAP_08.8: Variant images
        ];
    }

    /**
     * Sync variant stock to Baselinker.
     *
     * @param ERPConnection $connection
     * @param ProductVariant $variant
     * @param string $inventoryId
     * @param string $baselinkerVariantId
     */
    protected function syncVariantStock(
        ERPConnection $connection,
        ProductVariant $variant,
        string $inventoryId,
        string $baselinkerVariantId
    ): void {
        try {
            $warehouseMapping = $connection->connection_config['warehouse_mappings'] ?? [];
            $stockData = [];

            foreach ($variant->stock as $stock) {
                $baselinkerWarehouseId = $warehouseMapping[$stock->warehouse_id] ?? null;

                if ($baselinkerWarehouseId) {
                    $stockData[] = [
                        'product_id' => $baselinkerVariantId,
                        'variant_id' => 0, // Variant IS the product in BL
                        'warehouse_id' => $baselinkerWarehouseId,
                        'stock' => $stock->quantity,
                    ];
                }
            }

            if (!empty($stockData)) {
                $this->makeRequest(
                    $connection->connection_config,
                    'updateInventoryProductsStock',
                    [
                        'inventory_id' => $inventoryId,
                        'products' => $stockData,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::warning('syncVariantStock: Failed', [
                'variant_sku' => $variant->sku,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync variant prices to Baselinker.
     *
     * @param ERPConnection $connection
     * @param ProductVariant $variant
     * @param string $inventoryId
     * @param string $baselinkerVariantId
     */
    protected function syncVariantPrices(
        ERPConnection $connection,
        ProductVariant $variant,
        string $inventoryId,
        string $baselinkerVariantId
    ): void {
        try {
            $priceMapping = $this->getPriceGroupMapping();
            $priceData = [];

            foreach ($variant->prices as $price) {
                $baselinkerPriceType = $priceMapping[$price->price_group_id] ?? null;

                if ($baselinkerPriceType) {
                    $priceData[] = [
                        'product_id' => $baselinkerVariantId,
                        'price_type' => $baselinkerPriceType,
                        'price' => $price->price_gross ?? $price->price ?? 0,
                    ];
                }
            }

            if (!empty($priceData)) {
                $this->makeRequest(
                    $connection->connection_config,
                    'updateInventoryProductsPrices',
                    [
                        'inventory_id' => $inventoryId,
                        'products' => $priceData,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::warning('syncVariantPrices: Failed', [
                'variant_sku' => $variant->sku,
                'error' => $e->getMessage(),
            ]);
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
     *
     * ETAP_08.5: Full request/response logging for debugging.
     */
    protected function makeRequest(array $config, string $method, array $parameters): array
    {
        $startTime = microtime(true);

        // DEBUG: Check images format before json_encode
        if (isset($parameters['images'])) {
            Log::info('makeRequest: images being sent to Baselinker API', [
                'method' => $method,
                'product_id' => $parameters['product_id'] ?? 'NEW',
                'sku' => $parameters['sku'] ?? 'N/A',
                'images_type' => gettype($parameters['images']),
                'images_count' => is_object($parameters['images']) ? count(get_object_vars($parameters['images'])) : 0,
                'images_json' => json_encode($parameters['images']),
            ]);
        }

        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post($this->baseUrl, [
                    'token' => $config['api_token'],
                    'method' => $method,
                    'parameters' => json_encode($parameters)
                ]);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $httpStatus = $response->status();
            $data = $response->json() ?? [];

            // ETAP_08.5: Full logging with request_data and response_data
            $isSuccess = $response->successful() && ($data['status'] ?? '') === 'SUCCESS';
            $logLevel = $isSuccess ? 'info' : 'warning';

            // Create detailed log entry
            // NOTE: IntegrationLog model has 'array' cast for request_data/response_data
            // so we pass arrays directly - Laravel handles json_encode automatically
            IntegrationLog::create([
                'integration_type' => IntegrationLog::INTEGRATION_BASELINKER,
                'log_type' => IntegrationLog::TYPE_API_CALL,
                'operation' => 'api_call_' . $method,  // e.g. api_call_addInventoryProduct
                'log_level' => $logLevel,
                'description' => "Baselinker API: {$method}",
                'request_data' => [
                    'method' => $method,
                    'parameters' => $this->sanitizeParameters($parameters),
                    'endpoint' => $this->baseUrl,
                ],
                'response_data' => [
                    'status' => $data['status'] ?? 'UNKNOWN',
                    'product_id' => $data['product_id'] ?? null,
                    'error_code' => $data['error_code'] ?? null,
                    'error_message' => $data['error_message'] ?? null,
                    'warnings' => $data['warnings'] ?? null,
                    'products_count' => isset($data['products']) ? count($data['products']) : null,
                    'inventories_count' => isset($data['inventories']) ? count($data['inventories']) : null,
                ],
                'http_status' => $httpStatus,
                'duration_ms' => $duration,
                'logged_at' => now(),
            ]);

            if ($response->successful()) {
                return $data;
            } else {
                throw new \Exception('HTTP Error ' . $httpStatus . ': ' . $response->reason());
            }

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // ETAP_08.5: Full error logging
            // NOTE: IntegrationLog model has 'array' cast - pass arrays directly
            IntegrationLog::create([
                'integration_type' => IntegrationLog::INTEGRATION_BASELINKER,
                'log_type' => IntegrationLog::TYPE_API_CALL,
                'operation' => 'api_call_' . $method,
                'log_level' => 'error',
                'description' => "Baselinker API FAILED: {$method}",
                'request_data' => [
                    'method' => $method,
                    'parameters' => $this->sanitizeParameters($parameters),
                    'endpoint' => $this->baseUrl,
                ],
                'response_data' => null,
                'http_status' => isset($response) ? $response->status() : null,
                'duration_ms' => $duration,
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'logged_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Sanitize parameters for logging (remove sensitive data).
     *
     * ETAP_08.5: Never log API tokens!
     */
    protected function sanitizeParameters(array $params): array
    {
        // Never log API token
        unset($params['token']);

        // Truncate very large arrays (e.g., product lists)
        foreach ($params as $key => $value) {
            if (is_array($value) && count($value) > 10) {
                $params[$key] = [
                    '_truncated' => true,
                    '_count' => count($value),
                    '_sample' => array_slice($value, 0, 3),
                ];
            }
        }

        return $params;
    }

    /**
     * Build Baselinker product data structure.
     *
     * ETAP_08.5 FIX: Uses ProductErpData if exists (for ERP TAB edits),
     * with fallback to main Product data.
     *
     * Priority: ProductErpData columns > Product model defaults
     */
    protected function buildBaselinkerProductData(ERPConnection $connection, Product $product): array
    {
        // ETAP_08.5: Check for ERP-specific data from ProductErpData
        $erpData = $product->erpData()
            ->where('erp_connection_id', $connection->id)
            ->first();

        // Log data source for debugging
        Log::debug('buildBaselinkerProductData: Data source', [
            'product_id' => $product->id,
            'product_sku' => $product->sku,
            'connection_id' => $connection->id,
            'has_erp_data' => $erpData !== null,
            'erp_data_name' => $erpData?->name,
            'product_name' => $product->name,
            'using_erp_name' => $erpData?->name !== null,
        ]);

        // ETAP_08.5: Get product images from Media gallery
        // CRITICAL: Baselinker API images format:
        // 1. Object with numeric string keys (0-15): {"0": "...", "1": "..."}
        // 2. Values MUST have prefix "url:" for URL format: "url:https://example.com/img.jpg"
        // 3. Or "data:" prefix for base64 encoded images
        // Documentation: https://api.baselinker.com/index.php?method=addInventoryProduct
        $imagesObject = new \stdClass();
        $mediaCollection = $product->media()->active()->forGallery()->get();
        $imageIndex = 0;

        // LIMIT: Baselinker accepts max 16 images (positions 0-15)
        $maxImages = 16;

        foreach ($mediaCollection as $media) {
            // Stop if we've reached the limit
            if ($imageIndex >= $maxImages) {
                Log::info('Baselinker: Image limit reached', [
                    'product_sku' => $product->sku,
                    'max_images' => $maxImages,
                    'total_media' => $mediaCollection->count(),
                ]);
                break;
            }

            // Get full URL for Baselinker (must be publicly accessible)
            $imageUrl = $media->url;
            if ($imageUrl && !empty($imageUrl) && !str_contains($imageUrl, 'placeholder')) {
                // CRITICAL: Baselinker requires "url:" prefix for URL format images!
                $imagesObject->{(string)$imageIndex} = 'url:' . $imageUrl;
                $imageIndex++;
            }
        }

        Log::debug('buildBaselinkerProductData: Images collected', [
            'product_id' => $product->id,
            'product_sku' => $product->sku,
            'images_count' => $imageIndex,
            'images_type' => gettype($imagesObject),
        ]);

        // Use ERP data if exists, fallback to Product
        return [
            'name' => $erpData?->name ?? $product->name,
            'description' => $erpData?->long_description ?? $product->description ?: '',
            'description_extra1' => $erpData?->short_description ?? $product->short_description ?: '',
            'sku' => $erpData?->sku ?? $product->sku,
            'ean' => $erpData?->ean ?? $product->ean ?: '',
            'tax_rate' => $erpData?->tax_rate ?? $product->tax_rate ?: 23,
            'weight' => $erpData?->weight ?? $product->weight ?: 0,
            'height' => $erpData?->height ?? $product->height ?: 0,
            'width' => $erpData?->width ?? $product->width ?: 0,
            'length' => $erpData?->length ?? $product->length ?: 0,
            'images' => $imagesObject,  // ETAP_08.5: stdClass for proper JSON object encoding
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

            // Import images from Baselinker (ETAP_08.6: Pass ERP product ID for erp_mapping)
            $imagesImported = $this->importImagesFromBaselinker($product, $blProduct['images'] ?? [], $connection, $erpProductId);
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
     * ETAP_08.6: Sets erp_mapping on Media records for Gallery sync status.
     *
     * @param Product $product The PPM product to attach images to
     * @param array $imageUrls Array of image URLs from Baselinker
     * @param ERPConnection $connection The ERP connection for logging
     * @param string|null $baselinkerProductId Baselinker product ID for erp_mapping
     * @return int Number of successfully imported images
     */
    protected function importImagesFromBaselinker(Product $product, array $imageUrls, ERPConnection $connection, ?string $baselinkerProductId = null): int
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

                // ETAP_08.6: Set erp_mapping for Gallery sync status
                if ($baselinkerProductId) {
                    $media->setErpMapping($connection->id, [
                        'product_id' => $baselinkerProductId,
                        'image_position' => $index,
                        'synced_at' => now()->toIso8601String(),
                        'connection_name' => $connection->instance_name,
                        'erp_type' => $connection->erp_type,
                        'source' => 'imported_from_baselinker',
                        'status' => 'synced',  // CRITICAL: Required for Gallery checkbox display
                    ]);
                }

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

    /**
     * ETAP_08.6: Mark product media as synced to ERP.
     *
     * Updates Media.erp_mapping for all product images after successful sync.
     * This allows GalleryTab to show correct sync status (blue checkboxes).
     *
     * @param ERPConnection $connection The ERP connection
     * @param Product $product The product whose media was synced
     * @param string $baselinkerProductId The Baselinker product ID
     * @return int Number of media records updated
     */
    protected function markProductMediaAsSyncedToErp(
        ERPConnection $connection,
        Product $product,
        string $baselinkerProductId
    ): int {
        $mediaCollection = $product->media()->active()->forGallery()->get();

        if ($mediaCollection->isEmpty()) {
            return 0;
        }

        $updated = 0;
        $imageIndex = 0;

        foreach ($mediaCollection as $media) {
            // Max 16 images (same limit as in buildBaselinkerProductData)
            if ($imageIndex >= 16) {
                break;
            }

            // Update erp_mapping on Media record
            $media->setErpMapping($connection->id, [
                'product_id' => $baselinkerProductId,
                'image_position' => $imageIndex,
                'synced_at' => now()->toIso8601String(),
                'connection_name' => $connection->instance_name,
                'erp_type' => $connection->erp_type,
                'status' => 'synced',  // CRITICAL: Required for Gallery checkbox display
            ]);

            $updated++;
            $imageIndex++;
        }

        Log::info('markProductMediaAsSyncedToErp: Updated media erp_mapping', [
            'product_id' => $product->id,
            'product_sku' => $product->sku,
            'connection_id' => $connection->id,
            'connection_name' => $connection->instance_name,
            'baselinker_product_id' => $baselinkerProductId,
            'media_updated' => $updated,
            'total_media' => $mediaCollection->count(),
        ]);

        return $updated;
    }

    /**
     * ETAP_08.8: Find product in Baselinker by SKU
     *
     * Searches all inventories for a product with matching SKU.
     * Used when linking existing PPM product with Baselinker.
     *
     * @param ERPConnection $connection
     * @param string $sku Product SKU to search for
     * @return array ['success' => bool, 'external_id' => string|null, 'data' => array]
     */
    public function findProductBySku(ERPConnection $connection, string $sku): array
    {
        $config = $connection->connection_config;
        $sku = trim($sku);

        if (empty($sku)) {
            return ['success' => false, 'external_id' => null, 'data' => [], 'message' => 'SKU is empty'];
        }

        try {
            // Get all inventories
            $inventoriesResponse = $this->makeRequest($config, 'getInventories', []);

            if ($inventoriesResponse['status'] !== 'SUCCESS') {
                return [
                    'success' => false,
                    'external_id' => null,
                    'data' => [],
                    'message' => 'Failed to get inventories: ' . ($inventoriesResponse['error_message'] ?? 'Unknown'),
                ];
            }

            $inventories = $inventoriesResponse['inventories'] ?? [];

            // Search each inventory for product with matching SKU
            foreach ($inventories as $inventoryId => $inventoryData) {
                $response = $this->makeRequest(
                    $config,
                    'getInventoryProductsList',
                    [
                        'inventory_id' => $inventoryId,
                        'filter_sku' => $sku,
                        'filter_limit' => 5,
                    ]
                );

                if ($response['status'] === 'SUCCESS' && !empty($response['products'])) {
                    // Check for exact SKU match
                    foreach ($response['products'] as $productId => $productData) {
                        $productSku = $productData['sku'] ?? '';
                        if (strtolower(trim($productSku)) === strtolower($sku)) {
                            Log::info('BaselinkerService::findProductBySku: Product found', [
                                'sku' => $sku,
                                'external_id' => $productId,
                                'inventory_id' => $inventoryId,
                                'connection_id' => $connection->id,
                            ]);

                            return [
                                'success' => true,
                                'external_id' => (string) $productId,
                                'data' => $productData,
                                'inventory_id' => $inventoryId,
                            ];
                        }
                    }
                }

                // Rate limiting between inventory searches
                usleep(100000); // 0.1 second
            }

            // Product not found in any inventory
            Log::info('BaselinkerService::findProductBySku: Product not found', [
                'sku' => $sku,
                'connection_id' => $connection->id,
                'inventories_searched' => count($inventories),
            ]);

            return ['success' => false, 'external_id' => null, 'data' => [], 'message' => 'Product not found'];

        } catch (\Exception $e) {
            Log::error('BaselinkerService::findProductBySku exception', [
                'sku' => $sku,
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'external_id' => null,
                'data' => [],
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }
}