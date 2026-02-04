<?php

namespace App\Services\Scan\Sources;

use App\Models\ERPConnection;
use App\Services\ERP\SubiektGT\SubiektRestApiClient;
use App\Services\Scan\Contracts\ScanSourceInterface;
use App\Exceptions\SubiektApiException;
use App\Exceptions\ScanSourceException;
use Illuminate\Support\Facades\Log;

/**
 * SubiektGTScanSource
 *
 * Adapter for scanning products from Subiekt GT ERP system.
 * Uses existing SubiektRestApiClient for API communication.
 *
 * Usage:
 * ```php
 * $connection = ERPConnection::find(1);
 * $source = new SubiektGTScanSource($connection);
 * $skus = $source->getAllSkus();
 * ```
 *
 * @package App\Services\Scan\Sources
 * @version 1.0.0
 */
class SubiektGTScanSource implements ScanSourceInterface
{
    protected ERPConnection $connection;
    protected SubiektRestApiClient $client;
    protected ?int $cachedProductCount = null;

    /**
     * Constructor
     *
     * @param ERPConnection $connection Active Subiekt GT ERP connection
     * @throws ScanSourceException When connection is invalid or inactive
     */
    public function __construct(ERPConnection $connection)
    {
        $this->validateConnection($connection);
        $this->connection = $connection;
        $this->client = $this->buildClient($connection);
    }

    /**
     * Validate the ERP connection.
     *
     * @param ERPConnection $connection
     * @throws ScanSourceException
     */
    protected function validateConnection(ERPConnection $connection): void
    {
        if ($connection->erp_type !== ERPConnection::ERP_SUBIEKT_GT) {
            throw new ScanSourceException(
                "Invalid ERP type: expected 'subiekt_gt', got '{$connection->erp_type}'"
            );
        }

        if (!$connection->is_active) {
            throw new ScanSourceException(
                "ERP connection '{$connection->instance_name}' is not active"
            );
        }
    }

    /**
     * Build SubiektRestApiClient from ERPConnection config.
     *
     * @param ERPConnection $connection
     * @return SubiektRestApiClient
     * @throws ScanSourceException
     */
    protected function buildClient(ERPConnection $connection): SubiektRestApiClient
    {
        $config = $connection->connection_config ?? [];

        $requiredFields = ['rest_api_url', 'rest_api_key'];
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                throw new ScanSourceException(
                    "Missing required config field '{$field}' for Subiekt GT connection"
                );
            }
        }

        return new SubiektRestApiClient([
            'base_url' => $config['rest_api_url'],
            'api_key' => $config['rest_api_key'],
            'timeout' => $config['rest_api_timeout'] ?? 30,
            'connect_timeout' => $config['rest_api_connect_timeout'] ?? 10,
            'retry_times' => $config['rest_api_retry_times'] ?? 3,
            'retry_delay' => $config['rest_api_retry_delay'] ?? 100,
            'verify_ssl' => $config['rest_api_verify_ssl'] ?? false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllSkus(): array
    {
        Log::info('SubiektGTScanSource::getAllSkus - Starting', [
            'connection_id' => $this->connection->id,
            'connection_name' => $this->connection->instance_name,
        ]);

        $allSkus = [];
        $page = 1;
        $perPage = 500; // Max allowed by API
        $startTime = microtime(true);

        try {
            do {
                $response = $this->client->getProducts([
                    'page' => $page,
                    'pageSize' => $perPage,
                ]);

                $products = $response['data'] ?? [];
                foreach ($products as $product) {
                    $sku = $product['sku'] ?? $product['symbol'] ?? null;
                    if ($sku !== null && $sku !== '') {
                        $allSkus[] = (string) $sku;
                    }
                }

                $pagination = $response['pagination'] ?? [];
                $hasMore = $pagination['has_next'] ?? false;
                $page++;

                // Safety limit to prevent infinite loops
                if ($page > 1000) {
                    Log::warning('SubiektGTScanSource::getAllSkus - Safety limit reached', [
                        'pages_fetched' => $page - 1,
                        'skus_collected' => count($allSkus),
                    ]);
                    break;
                }

            } while ($hasMore);

            $duration = round(microtime(true) - $startTime, 2);

            Log::info('SubiektGTScanSource::getAllSkus - Completed', [
                'connection_id' => $this->connection->id,
                'total_skus' => count($allSkus),
                'pages_fetched' => $page - 1,
                'duration_seconds' => $duration,
            ]);

            return array_unique($allSkus);

        } catch (SubiektApiException $e) {
            Log::error('SubiektGTScanSource::getAllSkus - API Error', [
                'connection_id' => $this->connection->id,
                'error' => $e->getMessage(),
                'http_status' => $e->getHttpStatusCode(),
            ]);

            throw new ScanSourceException(
                "Failed to fetch SKUs from Subiekt GT: {$e->getMessage()}",
                $e->getHttpStatusCode(),
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
            $response = $this->client->getProductBySku($sku);
            $productData = $response['data'] ?? $response;

            if (empty($productData) || !isset($productData['id'])) {
                return null;
            }

            return $this->normalizeProduct($productData);

        } catch (SubiektApiException $e) {
            if ($e->isNotFound()) {
                return null;
            }

            Log::error('SubiektGTScanSource::getProductBySku - API Error', [
                'sku' => $sku,
                'error' => $e->getMessage(),
            ]);

            throw new ScanSourceException(
                "Failed to fetch product '{$sku}' from Subiekt GT: {$e->getMessage()}",
                $e->getHttpStatusCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProductsBatch(int $page, int $perPage = 100): array
    {
        $perPage = min($perPage, 500); // API max limit

        try {
            $response = $this->client->getProducts([
                'page' => $page,
                'pageSize' => $perPage,
            ]);

            $products = $response['data'] ?? [];
            $pagination = $response['pagination'] ?? [];

            $normalizedProducts = array_map(
                fn($product) => $this->normalizeProduct($product),
                $products
            );

            return [
                'data' => $normalizedProducts,
                'total' => $pagination['total'] ?? count($products),
                'page' => $page,
                'per_page' => $perPage,
                'has_more' => $pagination['has_next'] ?? false,
            ];

        } catch (SubiektApiException $e) {
            Log::error('SubiektGTScanSource::getProductsBatch - API Error', [
                'page' => $page,
                'per_page' => $perPage,
                'error' => $e->getMessage(),
            ]);

            throw new ScanSourceException(
                "Failed to fetch products batch from Subiekt GT: {$e->getMessage()}",
                $e->getHttpStatusCode(),
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
            $stats = $this->client->getStats();
            $this->cachedProductCount = $stats['total_products'] ?? 0;

            return $this->cachedProductCount;

        } catch (SubiektApiException $e) {
            Log::error('SubiektGTScanSource::getProductCount - API Error', [
                'error' => $e->getMessage(),
            ]);

            throw new ScanSourceException(
                "Failed to get product count from Subiekt GT: {$e->getMessage()}",
                $e->getHttpStatusCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceType(): string
    {
        return ERPConnection::ERP_SUBIEKT_GT;
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
        return "Subiekt GT - {$this->connection->instance_name}";
    }

    /**
     * {@inheritdoc}
     */
    public function testConnection(): array
    {
        try {
            return $this->client->testConnection();
        } catch (SubiektApiException $e) {
            return [
                'success' => false,
                'message' => "Connection failed: {$e->getMessage()}",
                'response_time_ms' => null,
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeProduct(array $rawProduct): array
    {
        return [
            'external_id' => (string) ($rawProduct['id'] ?? ''),
            'sku' => (string) ($rawProduct['sku'] ?? $rawProduct['symbol'] ?? ''),
            'name' => (string) ($rawProduct['name'] ?? $rawProduct['nazwa'] ?? ''),
            'description' => $rawProduct['description'] ?? $rawProduct['opis'] ?? null,
            'ean' => $rawProduct['ean'] ?? null,
            'price_net' => isset($rawProduct['priceNet'])
                ? (float) $rawProduct['priceNet']
                : null,
            'price_gross' => isset($rawProduct['priceGross'])
                ? (float) $rawProduct['priceGross']
                : null,
            'stock' => isset($rawProduct['stock'])
                ? (float) $rawProduct['stock']
                : null,
            'unit' => $rawProduct['unit'] ?? $rawProduct['jednostka'] ?? null,
            'weight' => isset($rawProduct['weight'])
                ? (float) $rawProduct['weight']
                : null,
            'vat_rate' => isset($rawProduct['vatRate'])
                ? (float) $rawProduct['vatRate']
                : null,
            'is_active' => (bool) ($rawProduct['isActive'] ?? $rawProduct['aktywny'] ?? true),
            'manufacturer' => $rawProduct['manufacturer'] ?? $rawProduct['producent'] ?? null,
            'group' => $rawProduct['group'] ?? $rawProduct['grupa'] ?? null,
            'source_type' => $this->getSourceType(),
            'source_id' => $this->getSourceId(),
            'raw_data' => $rawProduct,
        ];
    }

    /**
     * Get the underlying API client.
     *
     * @return SubiektRestApiClient
     */
    public function getClient(): SubiektRestApiClient
    {
        return $this->client;
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
