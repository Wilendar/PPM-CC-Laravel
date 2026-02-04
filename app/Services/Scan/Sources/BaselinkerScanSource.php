<?php

namespace App\Services\Scan\Sources;

use App\Models\ERPConnection;
use App\Services\ERP\BaselinkerService;
use App\Services\Scan\Contracts\ScanSourceInterface;
use App\Exceptions\ScanSourceException;
use Illuminate\Support\Facades\Log;

/**
 * BaselinkerScanSource
 *
 * Adapter for scanning products from Baselinker ERP system.
 * Uses existing BaselinkerService for API communication.
 *
 * Usage:
 * ```php
 * $connection = ERPConnection::where('erp_type', 'baselinker')->first();
 * $source = new BaselinkerScanSource($connection);
 * $skus = $source->getAllSkus();
 * ```
 *
 * @package App\Services\Scan\Sources
 * @version 1.0.0
 */
class BaselinkerScanSource implements ScanSourceInterface
{
    protected ERPConnection $connection;
    protected BaselinkerService $service;
    protected ?int $cachedProductCount = null;
    protected string $inventoryId;

    /**
     * Constructor
     *
     * @param ERPConnection $connection Active Baselinker ERP connection
     * @throws ScanSourceException When connection is invalid or inactive
     */
    public function __construct(ERPConnection $connection)
    {
        $this->validateConnection($connection);
        $this->connection = $connection;
        $this->service = new BaselinkerService();
        $this->inventoryId = $connection->connection_config['inventory_id'] ?? '';
    }

    /**
     * Validate the ERP connection.
     *
     * @param ERPConnection $connection
     * @throws ScanSourceException
     */
    protected function validateConnection(ERPConnection $connection): void
    {
        if ($connection->erp_type !== ERPConnection::ERP_BASELINKER) {
            throw new ScanSourceException(
                "Invalid ERP type: expected 'baselinker', got '{$connection->erp_type}'"
            );
        }

        if (!$connection->is_active) {
            throw new ScanSourceException(
                "ERP connection '{$connection->instance_name}' is not active"
            );
        }

        $config = $connection->connection_config ?? [];
        if (empty($config['api_token'])) {
            throw new ScanSourceException(
                "Missing required config field 'api_token' for Baselinker connection"
            );
        }

        if (empty($config['inventory_id'])) {
            throw new ScanSourceException(
                "Missing required config field 'inventory_id' for Baselinker connection"
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAllSkus(): array
    {
        Log::info('BaselinkerScanSource::getAllSkus - Starting', [
            'connection_id' => $this->connection->id,
            'connection_name' => $this->connection->instance_name,
            'inventory_id' => $this->inventoryId,
        ]);

        $allSkus = [];
        $page = 1;
        $startTime = microtime(true);

        try {
            do {
                $response = $this->makeRequest('getInventoryProductsList', [
                    'inventory_id' => $this->inventoryId,
                    'filter_limit' => 1000,
                    'filter_page' => $page,
                ]);

                if ($response['status'] !== 'SUCCESS') {
                    throw new ScanSourceException(
                        "Baselinker API error: " . ($response['error_message'] ?? 'Unknown error')
                    );
                }

                $products = $response['products'] ?? [];

                foreach ($products as $productId => $productData) {
                    $sku = $productData['sku'] ?? null;
                    if ($sku !== null && $sku !== '') {
                        $allSkus[] = (string) $sku;
                    }
                }

                $hasMore = count($products) === 1000;
                $page++;

                // Safety limit
                if ($page > 100) {
                    Log::warning('BaselinkerScanSource::getAllSkus - Safety limit reached', [
                        'pages_fetched' => $page - 1,
                        'skus_collected' => count($allSkus),
                    ]);
                    break;
                }

                // Rate limiting - Baselinker: 60 req/min
                if ($hasMore) {
                    usleep(1100000); // 1.1 seconds
                }

            } while ($hasMore);

            $duration = round(microtime(true) - $startTime, 2);

            Log::info('BaselinkerScanSource::getAllSkus - Completed', [
                'connection_id' => $this->connection->id,
                'total_skus' => count($allSkus),
                'pages_fetched' => $page - 1,
                'duration_seconds' => $duration,
            ]);

            return array_unique($allSkus);

        } catch (\Exception $e) {
            Log::error('BaselinkerScanSource::getAllSkus - Error', [
                'connection_id' => $this->connection->id,
                'error' => $e->getMessage(),
            ]);

            throw new ScanSourceException(
                "Failed to fetch SKUs from Baselinker: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProductBySku(string $sku): ?array
    {
        try {
            $response = $this->makeRequest('getInventoryProductsList', [
                'inventory_id' => $this->inventoryId,
                'filter_sku' => $sku,
            ]);

            if ($response['status'] !== 'SUCCESS') {
                throw new ScanSourceException(
                    "Baselinker API error: " . ($response['error_message'] ?? 'Unknown error')
                );
            }

            $products = $response['products'] ?? [];

            if (empty($products)) {
                return null;
            }

            // Get first matching product
            $productId = array_key_first($products);
            $productData = $products[$productId];
            $productData['baselinker_id'] = $productId;

            return $this->normalizeProduct($productData);

        } catch (\Exception $e) {
            Log::error('BaselinkerScanSource::getProductBySku - Error', [
                'sku' => $sku,
                'error' => $e->getMessage(),
            ]);

            throw new ScanSourceException(
                "Failed to fetch product '{$sku}' from Baselinker: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProductsBatch(int $page, int $perPage = 100): array
    {
        $perPage = min($perPage, 1000); // Baselinker max

        try {
            $response = $this->makeRequest('getInventoryProductsList', [
                'inventory_id' => $this->inventoryId,
                'filter_limit' => $perPage,
                'filter_page' => $page,
            ]);

            if ($response['status'] !== 'SUCCESS') {
                throw new ScanSourceException(
                    "Baselinker API error: " . ($response['error_message'] ?? 'Unknown error')
                );
            }

            $products = $response['products'] ?? [];

            $normalizedProducts = [];
            foreach ($products as $productId => $productData) {
                $productData['baselinker_id'] = $productId;
                $normalizedProducts[] = $this->normalizeProduct($productData);
            }

            return [
                'data' => $normalizedProducts,
                'total' => $this->getProductCount(),
                'page' => $page,
                'per_page' => $perPage,
                'has_more' => count($products) === $perPage,
            ];

        } catch (\Exception $e) {
            Log::error('BaselinkerScanSource::getProductsBatch - Error', [
                'page' => $page,
                'per_page' => $perPage,
                'error' => $e->getMessage(),
            ]);

            throw new ScanSourceException(
                "Failed to fetch products batch from Baselinker: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProductCount(): int
    {
        if ($this->cachedProductCount !== null) {
            return $this->cachedProductCount;
        }

        try {
            // Baselinker doesn't have a direct count endpoint
            // We need to fetch all products to count them
            $count = 0;
            $page = 1;

            do {
                $response = $this->makeRequest('getInventoryProductsList', [
                    'inventory_id' => $this->inventoryId,
                    'filter_limit' => 1000,
                    'filter_page' => $page,
                ]);

                if ($response['status'] !== 'SUCCESS') {
                    break;
                }

                $products = $response['products'] ?? [];
                $count += count($products);

                $hasMore = count($products) === 1000;
                $page++;

                if ($hasMore) {
                    usleep(1100000);
                }

            } while ($hasMore && $page <= 100);

            $this->cachedProductCount = $count;
            return $this->cachedProductCount;

        } catch (\Exception $e) {
            Log::error('BaselinkerScanSource::getProductCount - Error', [
                'error' => $e->getMessage(),
            ]);

            throw new ScanSourceException(
                "Failed to get product count from Baselinker: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceType(): string
    {
        return ERPConnection::ERP_BASELINKER;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceId(): ?int
    {
        return $this->connection->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceName(): string
    {
        return "Baselinker - {$this->connection->instance_name}";
    }

    /**
     * {@inheritdoc}
     */
    public function testConnection(): array
    {
        $result = $this->service->testConnection($this->connection->connection_config);

        return [
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'Unknown',
            'response_time_ms' => $result['response_time'] ?? null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeProduct(array $rawProduct): array
    {
        // Extract text fields if available
        $textFields = $rawProduct['text_fields'] ?? [];
        $name = $textFields['name'] ?? $rawProduct['name'] ?? '';
        $description = $textFields['description'] ?? $rawProduct['description'] ?? '';

        // Extract prices - Baselinker has multiple price formats
        $prices = $rawProduct['prices'] ?? [];
        $priceNet = null;
        if (!empty($prices)) {
            $firstPrice = reset($prices);
            $priceNet = is_numeric($firstPrice) ? (float) $firstPrice : null;
        }

        return [
            'external_id' => (string) ($rawProduct['baselinker_id'] ?? $rawProduct['id'] ?? ''),
            'sku' => (string) ($rawProduct['sku'] ?? ''),
            'name' => (string) $name,
            'description' => (string) $description,
            'ean' => $rawProduct['ean'] ?? null,
            'price_net' => $priceNet,
            'price_gross' => null, // Would need tax rate calculation
            'stock' => isset($rawProduct['stock'])
                ? (float) (is_array($rawProduct['stock']) ? array_sum($rawProduct['stock']) : $rawProduct['stock'])
                : null,
            'unit' => null, // Baselinker doesn't have unit field
            'weight' => isset($rawProduct['weight'])
                ? (float) $rawProduct['weight']
                : null,
            'vat_rate' => isset($rawProduct['tax_rate'])
                ? (float) $rawProduct['tax_rate']
                : null,
            'is_active' => true, // Baselinker doesn't have active flag in product list
            'manufacturer' => $rawProduct['manufacturer'] ?? null,
            'group' => $rawProduct['category_id'] ?? null,
            'source_type' => $this->getSourceType(),
            'source_id' => $this->getSourceId(),
            'raw_data' => $rawProduct,
        ];
    }

    /**
     * Make API request to Baselinker.
     *
     * @param string $method Baselinker API method
     * @param array $params Request parameters
     * @return array Response data
     */
    protected function makeRequest(string $method, array $params = []): array
    {
        $config = $this->connection->connection_config;

        $response = \Illuminate\Support\Facades\Http::asForm()
            ->timeout(30)
            ->post('https://api.baselinker.com/connector.php', [
                'token' => $config['api_token'],
                'method' => $method,
                'parameters' => json_encode($params),
            ]);

        if (!$response->successful()) {
            throw new ScanSourceException(
                "Baselinker HTTP error: {$response->status()}"
            );
        }

        return $response->json() ?? [];
    }

    /**
     * Get the underlying service.
     *
     * @return BaselinkerService
     */
    public function getService(): BaselinkerService
    {
        return $this->service;
    }

    /**
     * Get the ERP connection model.
     *
     * @return ERPConnection
     */
    public function getConnection(): ERPConnection
    {
        return $this->connection;
    }
}
